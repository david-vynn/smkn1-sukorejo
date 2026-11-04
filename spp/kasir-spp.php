<?php

session_start();

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
  header('Location: ../index.php');
  exit;
}

if (($_SESSION['role'] ?? '') !== 'kasir') {
  http_response_code(403);
  exit('Akses ditolak. Halaman ini khusus kasir');
}

require_once __DIR__ . '/../database/config.php';

// Statistik transaksi hari ini
$sql = "
  SELECT COUNT(*) AS total_transaksi
  FROM payments
  WHERE status = 'success'
    AND DATE(payment_date) = CURDATE()
";

$result =  $conn->query($sql);
$stats_today = $result->fetch_assoc();

$total_transaction_today = $stats_today["total_transaksi"];

// Total pembayaran hari ini
$sql = "
  SELECT COALESCE(SUM(amount), 0) AS total_pembayaran
  FROM payments
  WHERE status = 'success'
    AND DATE(payment_date) = CURDATE()
";

$result = $conn->query($sql);
$stats_payments_today = $result->fetch_assoc();

$total_payments_today = $stats_payments_today['total_pembayaran'];

// Total pembayaran bulan ini

$sql = "
  SELECT COUNT(*) as total_transaksi
  FROM payments
  WHERE status = 'success'
    AND MONTH(payment_date) = MONTH(CURDATE())
    AND YEAR(payment_date) = YEAR(CURDATE())
";

$result = $conn->query($sql);
$stats_month = $result->fetch_assoc();

$total_payments_month = $stats_month['total_transaksi'];

// Total pemasukan bulan ini

$sql = "
  SELECT COALESCE(SUM(amount), 0) AS total_pemasukan
  FROM payments
  WHERE status = 'success'
    AND MONTH(payment_date) = MONTH(CURDATE())
    AND YEAR(payment_date) = YEAR(CURDATE())
";

$result = $conn->query($sql);
$stats_income_month =  $result->fetch_assoc();

$total_income_month = $stats_income_month["total_pemasukan"];


$success = '';
$errors = [];

$cashier_id = $_SESSION['user_id'];

$students = [];
$keyword = '';

echo date('Y-m-d H:i:s');

if (isset($_GET['q'])) {
  $keyword = trim($_GET['q']);

  if ($keyword !== '') {
    $sql = '
      SELECT
        s.id AS student_id,
        s.nisn,
        s.nama,
        c.grade,
        c.class_name
      FROM students s
      INNER JOIN classes c ON c.id = s.class_id
      WHERE s.nama LIKE ? OR s.nisn LIKE ?
      ORDER BY s.nama ASC
    ';

    $stmt = $conn->prepare($sql);

    $search = "%{$keyword}%";

    $stmt->bind_param("ss", $search, $search);

    $stmt->execute();

    $result = $stmt->get_result();

    $students = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
  }
}

$selected_student = null;
$invoices = [];
$paid_invoices = [];

if (isset($_GET['student_id']) || isset($_POST['student_id'])) {
  $selected_student_id = (int) ($_GET['student_id'] ?? $_POST['student_id']);

  // Mengambil data siswa
  $sql = '
    SELECT
      s.id,
      s.nisn,
      s.nama,
      c.grade,
      c.class_name
    FROM students s
    INNER JOIN classes c ON c.id = s.class_id
    WHERE s.id = ?
  ';

  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $selected_student_id);
  $stmt->execute();

  $result = $stmt->get_result();
  $selected_student = $result->fetch_assoc();

  $stmt->close();

  // Mengambil tagihan yang belum lunas
  if ($selected_student) {
    $sql = "
      SELECT 
        ID,
        period_month,
        period_year,
        due_date,
        amount,
        status
      FROM spp_invoices
      WHERE student_id = ?
      AND status IN ('unpaid','overdue')
      ORDER BY period_year ASC, period_month ASC
    ";


    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_student_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $invoices = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

  }

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $selected_bill_ids = $_POST['bill_ids'] ?? [];

  // Validasi minimal satu tagihan dipilih
  if (empty($selected_bill_ids)) {
    $errors[] = 'Pilih minimal satu tagihan';
  } else {
    $selected_bill_ids = array_map('intval', $selected_bill_ids);

    $available_ids = array_map(
      'intval',
      array_column($invoices, 'ID')
    );

    foreach ($selected_bill_ids as $bill_id) {
      if (!in_array($bill_id, $available_ids, true)) {
        $errors[] = 'Terdapat tagihan yang tidak valid.';
        break;
      }
    }

    // Menghitung ulang total berdasarkan database
    if (empty($errors)) {

      $total_payments = 0;

      foreach ($invoices as $invoice) {
        if (in_array((int) $invoice['ID'], $selected_bill_ids, true)) {
          $total_payments += (float) $invoice['amount'];
        }
      }

      if ($total_payments <= 0) {
        $errors[] = 'Total pembayaran tidak vlid';
      }

      if (empty($errors)) {

        $transaction_code = 'SPP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        
        $conn->begin_transaction();

        try {

          foreach ($selected_bill_ids as $bill_id) {

            $sql = "
              SELECT ID, amount
              FROM spp_invoices
              WHERE ID = ?
                AND student_id = ?
                AND status IN ('unpaid', 'overdue')
              FOR UPDATE
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $bill_id, $selected_student_id);
            $stmt->execute();

            $result = $stmt->get_result();
            $invoice = $result->fetch_assoc();

            $stmt->close();

            if (!$invoice) {
              throw new Exception("Tagihan siswa tidak valid atau sudah lunas");
            }

            // Menyimpan ke database
            $sql = "
              INSERT INTO payments (
                invoice_id,
                cashier_id,
                amount,
                status,
                transaction_code
              )
              VALUES (?, ?, ?, 'success', ?)
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
              'iids',
              $invoice['ID'],
              $cashier_id,
              $invoice['amount'],
              $transaction_code
            );

            $stmt->execute();
            $stmt->close();

            // Mengubah status taihan menjadi lunas
            $sql = "
              UPDATE spp_invoices
              SET STATUS = 'paid'
              WHERE ID = ?
                AND student_id = ?
                AND status IN ('unpaid', 'overdue')
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
              'ii',
              $invoice['ID'],
              $selected_student_id
            );

            $stmt->execute();

            if ($stmt->affected_rows !== 1) {
              throw new Exception('Gagal memperbarui status tagihan');
            }

            $stmt->close();

          }


          $conn->commit();

          $success = 'Pembayaran berhasil diproses';

          // Menyimpan daftar invoice untuk bukti pembayaran
          foreach ($invoices as $invoice) {
            if(in_array((int) $invoice['ID'], $selected_bill_ids, true)) {
              $paid_invoices[] = $invoice;
            }
          }

        } catch (Throwable $e) {
          $conn->rollback();

          error_log('ERROR PEMBAYARAN: ' . $e->getMessage());

          $errors[] = 'Pembayaran gagal diproses';
        }
      }

    }
  }
}

?>

<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kasir SPP — SMKN 1 Sukorejo</title>
  <meta name="description"
    content="SMKN 1 Sukorejo mempersiapkan pendidikan vokasi terbaik dan menyiapkan siswa untuk sukses di dunia industri modern." />
  <link rel="icon" href="../../assets/logo/favicon.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="../css/variables.css" />
  <link rel="stylesheet" href="../css/reset.css" />
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/spp.css" />
</head>

<body>
  <header class="site-header">
    <div class="container">
      <a href="../../index.html" class="brand">
        <svg class="brand__logo" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M12 3L2 8l10 5 8-4.09V15h2V8L12 3z" fill="currentColor" />
          <path d="M6 12.18v3.64C6 18.09 8.69 20 12 20s6-1.91 6-4.18v-3.64l-6 3-6-3z" fill="currentColor" />
        </svg>
        SMKN 1 SUKOREJO
      </a>
      <nav class="main-nav" aria-label="Navigasi utama">
        <a href="../index.html">Beranda</a>
        <a href="../profil-sekolah.html">Profil Sekolah</a>
        <a href="../agenda.html">Agenda</a>
        <a href="../prestasi.html">Prestasi</a>
        <a href="../contact.html">Kontak</a>
      </nav>
      <div class="header-account">
        <button type="button" class="user-chip__trigger" id="account-trigger" aria-haspopup="true"
          aria-expanded="false">
          <span class="user-chip__avatar" aria-hidden="true">&#128100;</span>
          <span class="user-chip__name">Portal Siswa</span>
          <svg class="user-chip__caret" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round" />
          </svg>
        </button>
        <div class="user-chip__menu" id="account-menu">
          <a href="../../cek-kelulusan.html" class="user-chip__link">
            <span aria-hidden="true">&#127891;</span> Portal Kelulusan
          </a>
          <button type="button" class="user-chip__logout">
            <span aria-hidden="true">&#128682;</span> Keluar
          </button>
        </div>
      </div>
    </div>
  </header>

  <main id="main" data-spp-role="kasir">
    <div class="page-header page-header--center">
      <div class="container">
        <h1>Dashboard Kasir SPP</h1>
        <p>Kelola dan catat pembayaran SPP siswa secara tunai.</p>
      </div>
    </div>
    <section class="spp-section">
      <div class="container">
        <p class="form-help spp-demo-note" role="note">
          <strong>Tampilan contoh.</strong> Semua data di halaman ini adalah
          placeholder untuk demonstrasi UI dan belum terhubung ke backend.
          Pratinjau peran: <a href="spp.html">Siswa</a> ·
          <a href="kasir-spp.php">Kasir</a> ·
          <a href="admin-spp.php">Admin</a>.
        </p>
        <div id="spp-msg" class="form-help" role="status" aria-live="polite"></div>
        <div class="spp-stats spp-stats--4">
          <div class="spp-card spp-stat">
            <span class="stat-block__label">Transaksi Hari Ini</span>
              <strong class="spp-stat__v">
                <?= number_format($total_transaction_today, 0,',','.'); ?>
              </strong>
          </div>
          <div class="spp-card spp-stat">
            <span class="stat-block__label">Pembayaran Hari Ini</span>
            <strong class="spp-stat__v">
              Rp<?= number_format((float) $total_payments_today,0,',','.'); ?>
            </strong>
          </div>
          <div class="spp-card spp-stat">
            <span class="stat-block__label">Transaksi Bulan Ini</span>
            <strong class="spp-stat__v">
              <?= number_format($total_payments_month,0,',','.'); ?>
            </strong>
          </div>
          <div class="spp-card spp-stat">
            <span class="stat-block__label">Pemasukan Bulan Ini</span>
            <strong class="spp-stat__v">
              Rp<?= number_format((float) $total_income_month,0,',','.'); ?>
            </strong>
          </div>
        </div>

        
        <div class="section-head spp-gap">
          <div class="section-head__text">
            <h2>Transaksi Pembayaran</h2>
            <p>Cari siswa berdasarkan nama atau NISN.</p>
          </div>
        </div>
        <div class="spp-card">
          <form class="spp-row" role="search" method="get" action="kasir-spp.php">
            <label class="visually-hidden" for="k-q">Nama / NISN siswa</label><input type="search" id="k-q" name="q"
              placeholder="Nama / NISN siswa" /><button type="submit" class="btn btn-primary btn-sm">
              Cari
            </button>
          </form>
          <div id="k-result">
            <div class="table-wrap spp-table">
              <table class="score-table">
                <caption class="visually-hidden">
                  Hasil pencarian siswa
                </caption>
                <thead>
                  <tr>
                    <th scope="col">Siswa</th>
                    <th scope="col">NISN</th>
                    <th scope="col">Kelas</th>
                    <th scope="col">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($students)): ?>

                    <?php foreach ($students as $student): ?>
                      <tr>
                        <td><?= htmlspecialchars($student['nama']) ?></td>
                        <td><?= htmlspecialchars($student['nisn']) ?></td>
                        <td><?= htmlspecialchars($student['class_name']) ?></td>
                        <td>
                          <form method="get" action="kasir-spp.php">
                            <input type="hidden" name="student_id" value="<?= (int) $student['student_id'] ?>">

                            <button type="submit" class="btn btn-primary btn-sm">
                              Pilih
                            </button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    
                    <?php elseif ($keyword !== ''): ?>
                      <tr>
                        <td colspan="4">Siswa tidak ditemukan.</td>
                      </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <h3 class="spp-gap">
            <?php if ($selected_student): ?>
              
              <h3 class="spp-gap">
                <?= htmlspecialchars($selected_student['nama']) ?>
              </h3>

            <?php endif; ?>
          </h3>
          <form class="spp-form" method="post">
            <input type="hidden" name="student_id" value="<?= (int) $selected_student['id'] ?>">

            <div class="form-field">
              <label>Pilih tagihan yang akan dibayar</label>

              <?php
              $month = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember'
              ];
              ?>

              <div class="spp-bill-list">

                <?php if (!empty($invoices)): ?>
                  <?php foreach ($invoices as $invoice): ?>

                    <label>
                      <input type="checkbox" name="bill_ids[]" value="<?= (int) $invoice['ID'] ?>"
                        data-amount="<?= htmlspecialchars($invoice['amount']) ?>">
                      <?= $month[(int) $invoice['period_month']] ?>
                      <?= htmlspecialchars($invoice['period_year']) ?>

                      - Rp<?= number_format((float) $invoice['amount'], 0, ',', '.') ?>

                      (<?= htmlspecialchars($invoice['status']) ?>)
                    </label>

                  <?php endforeach; ?>
                <?php endif; ?>

              </div>
            </div>

            <div class="form-field">
              <label for="k-amt">Total pembayaran</label>
              <input type="text" id="k-amt" value="Rp0" readonly />
            </div>
            <div class="spp-actions">
              <button type="submit" class="btn btn-primary">
                Proses Pembayaran
              </button>
            </div>
          </form>

          <?php if (!empty($errors)): ?>
  
            <?php foreach ($errors as $error): ?>
  
              <p class="form-error">
                <?= htmlspecialchars($error) ?>
              </p>
  
            <?php endforeach; ?>
  
          <?php endif; ?>
  
          <?php if ($success !== ''): ?>
            <p class="form-success" role="status">
              <?= htmlspecialchars($success) ?>
            </p>
          <?php endif; ?>

          <div class="spp-gap">
            <div class="spp-card spp-receipt" id="receipt">
              <h3>Bukti Pembayaran SPP</h3>
              <dl class="info-list">
                <div class="info-list__row">
                  <dt>Nama Sekolah</dt>
                  <dd>SMKN 1 Sukorejo</dd>
                </div>
                <div class="info-list__row">
                  <dt>Nama Siswa</dt>
                  <dd><?= htmlspecialchars($selected_student['nama']) ?? '-' ?></dd>
                </div>
                <div class="info-list__row">
                  <dt>NIS/NISN</dt>
                  <dd><?= htmlspecialchars($selected_student['nisn']) ?? '-' ?></dd>
                </div>
                <div class="info-list__row">
                  <dt>Kelas</dt>
                  <dd><?= htmlspecialchars($selected_student['class_name']) ?? '-' ?></dd>
                </div>
                <div class="info-list__row">
                  <dt>Bulan Pembayaran</dt>
                  <dd>
                    <?php foreach ($paid_invoices as $index => $invoice): ?>
                      <?= $month[(int) $invoice['period_month']] ?>
                      <?= htmlspecialchars($invoice['period_year']) ?>
                      <?= $index < count($paid_invoices) - 1 ? ', ' : '' ?>
                    <?php endforeach; ?>
                  </dd>
                </div>
                <div class="info-list__row">
                  <dt>Nominal</dt>
                  <dd>Rp<?= number_format((float) $total_payments, 0, ',', '.') ?></dd>
                </div>
                <div class="info-list__row">
                  <dt>Tanggal Pembayaran</dt>
                  <dd><?= date('d-m-Y H:i:s') ?></dd>
                </div>
                <div class="info-list__row">
                  <dt>Nomor Transaksi</dt>
                  <dd><?= $transaction_code ?></dd>
                </div>
              </dl>
              <div class="spp-actions">
                <button type="button" class="btn btn-primary btn-sm" data-action="print">
                  Cetak Bukti Pembayaran
                </button>
              </div>
            </div>
          </div>
        </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container footer-top">
      <div class="footer-brand">
        <p class="footer-brand__name">SMKN 1 SUKOREJO</p>
        <p>
          Mencetak generasi muda yang kompeten, berkarakter, profesional, dan
          siap menghadapi tantangan industri global.
        </p>
        <div class="footer-social">
          <a href="#" aria-label="Website resmi">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6" />
              <path d="M3 12h18M12 3a14 14 0 010 18 14 14 0 010-18z" stroke="currentColor" stroke-width="1.6" />
            </svg>
          </a>
          <a href="#" aria-label="Bagikan">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="18" cy="5" r="2.4" stroke="currentColor" stroke-width="1.6" />
              <circle cx="6" cy="12" r="2.4" stroke="currentColor" stroke-width="1.6" />
              <circle cx="18" cy="19" r="2.4" stroke="currentColor" stroke-width="1.6" />
              <path d="M8.1 10.8l7.8-4.4M8.1 13.2l7.8 4.4" stroke="currentColor" stroke-width="1.6" />
            </svg>
          </a>
        </div>
      </div>
      <div class="footer-col">
        <h3>Tautan Cepat</h3>
        <ul>
          <li><a href="../../profil-sekolah.html">Profil Sekolah</a></li>
          <li><a href="../../profil-sekolah.html#visi-misi">Visi Misi</a></li>
          <li><a href="../../agenda.html">Agenda</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h3>Informasi</h3>
        <ul>
          <li><a href="../../prestasi.html">Prestasi</a></li>
          <li><a href="../../cek-kelulusan.html"></a></li>
          <li><a href="../../cek-kelulusan.html">Pendaftaran</a></li>
        </ul>
      </div>
      <div class="footer-col footer-contact">
        <h3>Kontak Kami</h3>
        <ul>
          <li>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 22s7-6.2 7-12a7 7 0 10-14 0c0 5.8 7 12 7 12z" stroke="currentColor" stroke-width="1.6" />
              <circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.6" />
            </svg>
            <span>Jl. Raya Sukorejo No. 1, Pasuruan, Jawa Timur</span>
          </li>
          <li>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M4 5c0 8.3 6.7 15 15 15l3-4-6-3-2 2c-2-1-4-3-5-5l2-2-3-6-4 3z" stroke="currentColor"
                stroke-width="1.4" stroke-linejoin="round" />
            </svg>
            <span>(0343) 611234</span>
          </li>
          <li>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6" />
              <path d="M4 6.5l8 6 8-6" stroke="currentColor" stroke-width="1.6" />
            </svg>
            <span>info@smkn1sukorejo.sch.id</span>
          </li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      &copy; 2026 SMKN 1 SUKOREJO. All rights reserved.
    </div>
  </footer>

  <div class="auth-overlay" id="auth-overlay" aria-hidden="true">
    <div class="auth-modal" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title">
      <button type="button" class="icon-btn auth-modal__close" id="auth-modal-close" aria-label="Tutup">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
      </button>

      <!-- Login panel -->
      <div class="auth-panel is-active" id="auth-panel-login">
        <h2 class="auth-modal__title" id="auth-modal-title">Masuk ke Akun</h2>
        <p class="auth-modal__subtitle">
          Gunakan email dan kata sandi Anda untuk masuk.
        </p>

        <form class="auth-form" id="login-form" novalidate>
          <div class="form-field">
            <label for="login-email">Email</label>
            <div class="input-wrap">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6" />
                <path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
                  stroke-linejoin="round" />
              </svg>
              <input type="email" id="login-email" name="email" placeholder="Masukkan email" autocomplete="email" />
            </div>
            <p class="form-error" data-error-for="login-email"></p>
          </div>

          <div class="form-field">
            <label for="login-password">Kata Sandi</label>
            <div class="input-wrap">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.6" />
                <path d="M8 10V7a4 4 0 018 0v3" stroke="currentColor" stroke-width="1.6" />
              </svg>
              <input type="password" id="login-password" name="password" placeholder="Masukkan kata sandi"
                autocomplete="current-password" />
            </div>
            <p class="form-error" data-error-for="login-password"></p>
          </div>

          <div class="auth-form__row">
            <label class="auth-checkbox">
              <input type="checkbox" id="login-remember" name="remember" />
              <span>Ingat saya</span>
            </label>
          </div>

          <button type="submit" class="btn btn-primary btn-block">
            Masuk
          </button>
        </form>

        <div class="auth-success" id="auth-success" hidden>
          <div class="auth-success__icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
              <path d="M5 12.5l4.5 4.5L19 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"
                stroke-linejoin="round" />
            </svg>
          </div>
          <p>Login berhasil!</p>
        </div>
      </div>
    </div>
  </div>

  <script src="../js/script.js" defer></script>
  <script src="../js/spp-ui.js" defer></script>
</body>

</html>