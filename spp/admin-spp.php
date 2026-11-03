<?php

session_start();

if (!isset($_SESSION['user_id'])) {
  header('Location: ../index.php');
  exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
  http_response_code(403);
  exit('Akses ditolak. Halaman ini khusus admin.');
}

require_once __DIR__ . '/../database/config.php';

$errors = [];
$studentSuccess = null;
$kasirSuccess = null;
$resetSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_student') {

  $nisn = trim($_POST['nisn'] ?? '');
  $email = trim($_POST['email'] ?? '');

  if (!preg_match('/^\d{10}$/', $nisn)) {
    $errors[] = 'NISN harus terdiri dari 10 digit angka.';
  }

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Format email tidak valid.';
  }

  if (empty($errors)) {
    $stmt = mysqli_prepare(
      $conn,
      "SELECT id, user_id, nisn, nama
       FROM students
       WHERE nisn = ?
       LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 's', $nisn);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $student = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$student) {
      $errors[] = 'Data siswa dengan NISN tersebut tidak ditemukan.';
    } elseif ($student['user_id'] !== null) {
      $errors[] = 'Siswa tersebut sudah memiliki akun.';
    }
  }

  if (empty($errors)) {
    $stmt = mysqli_prepare(
      $conn,
      "SELECT id FROM users WHERE email = ? LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
      $errors[] = 'Email sudah digunakan.';
    }

    mysqli_stmt_close($stmt);
  }

  if (empty($errors)) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $randomPart = '';

    for ($i = 0; $i < 6; $i++) {
      $randomPart .= $characters[random_int(0, strlen($characters) - 1)];
    }

    $temporaryPassword = 'SPP-' . $randomPart;
    $hashedPassword = password_hash($temporaryPassword, PASSWORD_DEFAULT);

    mysqli_begin_transaction($conn);

    try {
      $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO users (username, email, password, role)
         VALUES (?, ?, ?, 'siswa')"
      );

      mysqli_stmt_bind_param(
        $stmt,
        'sss',
        $nisn,
        $email,
        $hashedPassword
      );

      mysqli_stmt_execute($stmt);
      $userId = mysqli_insert_id($conn);

      mysqli_stmt_close($stmt);

      $stmt = mysqli_prepare(
        $conn,
        "UPDATE students SET user_id = ? WHERE id = ?"
      );

      mysqli_stmt_bind_param(
        $stmt,
        'ii',
        $userId,
        $student['id']
      );

      mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);

      mysqli_commit($conn);

      $studentSuccess = [
        'nama' => $student['nama'],
        'nisn' => $nisn,
        'email' => $email,
        'password' => $temporaryPassword
      ];

    } catch (Throwable $e) {
      mysqli_rollback($conn);
      $errors[] = 'Gagal membuat akun siswa. Silakan coba lagi.';
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_student_password') {

  $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

  if (!$userId || $userId <= 0) {
    $errors[] = 'ID akun tidak valid.';
  }

  if (empty($errors)) {
    $stmt = mysqli_prepare(
      $conn,
      "SELECT id FROM users
       WHERE id = ? AND role = 'siswa'
       LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $account = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$account) {
      $errors[] = 'Akun siswa tidak ditemukan.';
    }

    if (empty($errors)) {
      $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
      $randomPart = '';

      for ($i = 0; $i < 6; $i++) {
        $randomPart .= $characters[random_int(0, strlen($characters) - 1)];
      }

      $temporaryPassword = 'SPP-' . $randomPart;
      $hashedPassword = password_hash($temporaryPassword, PASSWORD_DEFAULT);

      $stmt = mysqli_prepare(
        $conn,
        "UPDATE users
         SET password = ?
         WHERE id = ? AND role = 'siswa'"
      );

      mysqli_stmt_bind_param(
        $stmt,
        'si',
        $hashedPassword,
        $userId
      );

      $executeResult = mysqli_stmt_execute($stmt);
      $affectedRows = mysqli_stmt_affected_rows($stmt);

      mysqli_stmt_close($stmt);

      if ($executeResult && $affectedRows > 0) {
        $resetSuccess = [
          'password' => $temporaryPassword
        ];
      } else {
        $errors[] = 'Password gagal diperbarui.';
      }
    }
  }
}

// Ambil kata kunci pencarian siswa
$studentSearch = trim($_GET['student_q'] ?? '');


// Ambil daftar akun siswa
$studentAccounts = [];

$sql = "
  SELECT
    users.id AS user_id,
    students.nama,
    students.nisn,
    users.email,
    classes.class_name
  FROM students
  INNER JOIN users ON students.user_id = users.id
  INNER JOIN classes ON students.class_id = classes.id
";

if ($studentSearch !== '') {
  $sql .= "
    WHERE
      students.nama LIKE ?
      OR students.nisn LIKE ?
      OR users.email LIKE ?
  ";
}

$sql .= " ORDER BY students.nama ASC";

$stmt = mysqli_prepare($conn, $sql);

if ($studentSearch !== '') {
  $keyword = "%{$studentSearch}%";

  mysqli_stmt_bind_param(
    $stmt,
    'sss',
    $keyword,
    $keyword,
    $keyword
  );
}

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    $studentAccounts[] = $row;
  }

  mysqli_free_result($result);
}

mysqli_stmt_close($stmt);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_kasir') {
  $nama = trim($_POST['nama'] ?? '');
  $email = trim($_POST['email'] ?? '');

  if ($nama === '') {
    $errors[] = 'Nama kasir wajib diisi.';
  }

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Format email tidak valid.';
  }

  if (empty($errors)) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $randomPart = '';

    for ($i = 0; $i < 6; $i++) {
      $randomPart .= $characters[random_int(0, strlen($characters) - 1)];
    }

    $temporaryPassword = 'KSR-' . $randomPart;
    $hashedPassword = password_hash($temporaryPassword, PASSWORD_DEFAULT);

    try {
      $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO users (username, email, password, role)
         VALUES (?, ?, ?, 'kasir')"
      );

      mysqli_stmt_bind_param(
        $stmt,
        'sss',
        $nama,
        $email,
        $hashedPassword
      );

      mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);

      $kasirSuccess = [
        'nama' => $nama,
        'email' => $email,
        'password' => $temporaryPassword
      ];

    } catch (mysqli_sql_exception $e) {
      if ($e->getCode() === 1062) {
        $errors[] = 'Username atau email sudah digunakan.';
      } else {
        $errors[] = 'Gagal membuat akun kasir.';
      }
    }
  }
}

// AMbil kata kunci pencarian kasir
$kasirSearch = trim($_GET['kasir_q'] ?? '');
$keyword = "%{$kasirSearch}%";

$kasirAccounts = [];

$sql = "
    SELECT id AS user_id, username AS name, email
    FROM users
    WHERE role = 'kasir'
";

if ($kasirSearch !== '') {
  $sql .= " AND (username LIKE ? OR email LIKE ?)";
}

$sql .= " ORDER BY username ASC";

$stmt = mysqli_prepare($conn, $sql);

if ($kasirSearch !== '') {
  mysqli_stmt_bind_param($stmt, 'ss', $keyword, $keyword);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    $kasirAccounts[] = $row;
  }

  mysqli_free_result($result);
}

mysqli_stmt_close($stmt);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_kasir_password') {

  $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

  if (!$userId || $userId <= 0) {
    $errors[] = 'ID akun tidak valid.';
  }

  if (empty($errors)) {
    $stmt = mysqli_prepare(
      $conn,
      "SELECT id FROM users
       WHERE id = ? AND role = 'kasir'
       LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $account = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$account) {
      $errors[] = 'Akun kasir tidak ditemukan.';
    }

    if (empty($errors)) {
      $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
      $randomPart = '';

      for ($i = 0; $i < 6; $i++) {
        $randomPart .= $characters[random_int(0, strlen($characters) - 1)];
      }

      $temporaryPassword = 'KSR-' . $randomPart;
      $hashedPassword = password_hash($temporaryPassword, PASSWORD_DEFAULT);

      $stmt = mysqli_prepare(
        $conn,
        "UPDATE users
         SET password = ?
         WHERE id = ? AND role = 'kasir'"
      );

      mysqli_stmt_bind_param(
        $stmt,
        'si',
        $hashedPassword,
        $userId
      );

      $executeResult = mysqli_stmt_execute($stmt);
      $affectedRows = mysqli_stmt_affected_rows($stmt);

      mysqli_stmt_close($stmt);

      if ($executeResult && $affectedRows > 0) {
        $resetSuccess = [
          'password' => $temporaryPassword
        ];
      } else {
        $errors[] = 'Password gagal diperbarui.';
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
  <title>Admin SPP — SMKN 1 Sukorejo</title>
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

      <div class="header-account user-chip_menu">
        <a href="../logout.php" class="user-chip__logout">
          <span aria-hidden="true">&#128682;</span>
          Keluar
        </a>
      </div>
    </div>
  </header>

  <main id="main" data-spp-role="admin">
    <div class="page-header page-header--center">
      <div class="container">
        <h1>Dashboard Admin SPP</h1>
        <p>Kelola tagihan dan pantau pembayaran SPP.</p>
      </div>
    </div>

    <section class="spp-section">
      <div class="container">

        <p class="form-help spp-demo-note" role="note">
          <strong>Tampilan contoh.</strong> Semua data di halaman ini adalah
          placeholder untuk demonstrasi UI dan belum terhubung ke backend.
          Pratinjau peran:
          <a href="spp.html">Siswa</a> ·
          <a href="kasir-spp.html">Kasir</a> ·
          <a href="admin-spp.html">Admin</a>.
        </p>

        <div id="spp-msg" class="form-help" role="status" aria-live="polite">
          <?php foreach ($errors as $error): ?>
            <p class="form-error">
              <?= htmlspecialchars($error); ?>
            </p>
          <?php endforeach; ?>

          <?php if ($studentSuccess): ?>
            <p class="form-help">
              Akun siswa berhasil dibuat untuk
              <strong><?= htmlspecialchars($studentSuccess['nama']); ?></strong>.
            </p>
          <?php endif; ?>
        </div>

        <div class="spp-stats">
          <div class="spp-card spp-stat">
            <span class="stat-block__label">Total Siswa</span>
            <strong class="spp-stat__v">120</strong>
          </div>
          <div class="spp-card spp-stat">
            <span class="stat-block__label">Siswa Lunas</span>
            <strong class="spp-stat__v">45</strong>
          </div>
          <div class="spp-card spp-stat">
            <span class="stat-block__label">Siswa Belum Lunas</span>
            <strong class="spp-stat__v">75</strong>
          </div>
          <div class="spp-card spp-stat">
            <span class="stat-block__label">Pemasukan Hari Ini</span>
            <strong class="spp-stat__v">Rp450.000</strong>
          </div>
          <div class="spp-card spp-stat">
            <span class="stat-block__label">Pemasukan Bulan Ini</span>
            <strong class="spp-stat__v">Rp3.600.000</strong>
          </div>
          <div class="spp-card spp-stat">
            <span class="stat-block__label">Total Tunggakan</span>
            <strong class="spp-stat__v">Rp5.250.000</strong>
          </div>
          <div class="spp-card spp-stat">
            <span class="stat-block__label">Total Transaksi</span>
            <strong class="spp-stat__v">210</strong>
          </div>
        </div>

        <div class="section-head spp-gap">
          <div class="section-head__text">
            <h2>Manajemen SPP</h2>
          </div>
        </div>

        <div class="spp-grid">
          <div class="spp-card">
            <h3>Pengaturan Tarif SPP</h3>

            <form class="spp-form" method="post" data-backend="pending">
              <div class="form-field">
                <label for="a-year">Tahun Ajaran</label>
                <input type="text" id="a-year" name="year" value="2026/2027" />
              </div>

              <div class="form-field">
                <label for="tarif-x">Tarif Kelas X</label>
                <input type="number" id="tarif-x" name="tarif_x" value="150000" min="0" />
              </div>

              <div class="form-field">
                <label for="tarif-xi">Tarif Kelas XI</label>
                <input type="number" id="tarif-xi" name="tarif_xi" value="175000" min="0" />
              </div>

              <div class="form-field">
                <label for="tarif-xii">Tarif Kelas XII</label>
                <input type="number" id="tarif-xii" name="tarif_xii" value="200000" min="0" />
              </div>

              <button type="submit" class="btn btn-primary btn-sm">
                Simpan Tarif
              </button>
            </form>
          </div>

          <div class="spp-card">
            <h3>Generate Tagihan (2026/2027)</h3>

            <form class="spp-form" method="post" data-backend="pending">
              <div class="form-field">
                <label for="a-kelas">Kelas</label>
                <select id="a-kelas" name="kelas">
                  <option value="">Semua kelas</option>
                  <option value="X RPL 1">X RPL 1</option>
                  <option value="XI RPL 1">XI RPL 1</option>
                  <option value="XII RPL 1">XII RPL 1</option>
                </select>
              </div>

              <div class="form-field">
                <label for="a-bln">Bulan</label>
                <select id="a-bln" name="month">
                  <option value="all">Seluruh tahun ajaran</option>
                  <option value="7">Juli</option>
                  <option value="8">Agustus</option>
                  <option value="9">September</option>
                  <option value="10">Oktober</option>
                  <option value="11">November</option>
                  <option value="12">Desember</option>
                  <option value="1">Januari</option>
                  <option value="2">Februari</option>
                  <option value="3">Maret</option>
                  <option value="4">April</option>
                  <option value="5">Mei</option>
                  <option value="6">Juni</option>

                </select>
              </div>

              <div class="form-field">
                <label>Nominal Tagihan</label>
                <p class="form-help">
                  Sistem menggunakan tarif sesuai tingkat kelas siswa.
                </p>
              </div>

              <div class="form-field">
                <label for="a-gdue">Jatuh tempo tanggal (1-28)</label>
                <input type="number" id="a-gdue" name="due_day" value="10" min="1" max="28" />
              </div>

              <button type="submit" class="btn btn-primary btn-sm">
                Generate
              </button>
            </form>
          </div>
        </div>

        <!-- Daftar Tagihan Siswa -->
        <div class="section-head spp-gap">
          <div class="section-head__text">
            <h2>Daftar Tagihan Siswa</h2>
            <p>
              Lihat daftar tagihan dan status pembayaran seluruh siswa.
            </p>
          </div>
        </div>

        <form class="spp-row spp-gap" role="search" method="get" data-backend="pending">
          <label class="visually-hidden" for="b-q">Cari nama / NISN</label>
          <input type="search" id="b-q" name="q" placeholder="Cari nama / NISN" />

          <label class="visually-hidden" for="b-fstatus">Filter status</label>
          <select id="b-fstatus" name="status">
            <option value="">Semua status</option>
            <option value="unpaid">Belum Bayar</option>
            <option value="overdue">Menunggak</option>
            <option value="paid">Lunas</option>
          </select>

          <button type="submit" class="btn btn-primary btn-sm">
            Cari
          </button>
        </form>

        <div class="spp-gap">
          <div class="table-wrap spp-table">
            <table class="score-table">
              <caption class="visually-hidden">
                Daftar tagihan semua siswa
              </caption>

              <thead>
                <tr>
                  <th scope="col">Siswa</th>
                  <th scope="col">Bulan</th>
                  <th scope="col">Nominal</th>
                  <th scope="col">Jatuh Tempo</th>
                  <th scope="col">Status</th>
                </tr>
              </thead>

              <tbody>
                <tr>
                  <td data-label="Siswa">
                    Siswa Contoh<br />
                    <small>0000000001 · XI RPL 1</small>
                  </td>
                  <td data-label="Bulan">Oktober 2026</td>
                  <td data-label="Nominal">Rp150.000</td>
                  <td data-label="Jatuh Tempo">10 Okt 2026</td>
                  <td data-label="Status">
                    <span class="spp-badge spp-badge--unpaid">Belum Bayar</span>
                  </td>
                </tr>

                <tr>
                  <td data-label="Siswa">
                    Siswa Contoh<br />
                    <small>0000000001 · XI RPL 1</small>
                  </td>
                  <td data-label="Bulan">Juli 2026</td>
                  <td data-label="Nominal">Rp150.000</td>
                  <td data-label="Jatuh Tempo">10 Jul 2026</td>
                  <td data-label="Status">
                    <span class="spp-badge spp-badge--paid">Lunas</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Laporan SPP -->
        <div class="section-head spp-gap">
          <div class="section-head__text">
            <h2>Laporan SPP</h2>
          </div>
        </div>

        <form class="spp-row" method="get" data-backend="pending">
          <label class="visually-hidden" for="a-period">Periode</label>
          <select id="a-period" name="period">
            <option value="all">Semua waktu</option>
            <option value="today">Hari ini</option>
            <option value="week">Minggu ini</option>
            <option value="month">Bulan ini</option>
            <option value="year">Tahun ini</option>
          </select>

          <label class="visually-hidden" for="a-rkelas">Kelas</label>
          <select id="a-rkelas" name="kelas">
            <option value="">Semua kelas</option>
            <option value="X RPL 1">X RPL 1</option>
            <option value="XI RPL 1">XI RPL 1</option>
            <option value="XII RPL 1">XII RPL 1</option>
          </select>

          <label class="visually-hidden" for="a-status">Status</label>
          <select id="a-status" name="status">
            <option value="">Semua status</option>
            <option value="success">Berhasil</option>
          </select>

          <button type="submit" class="btn btn-primary btn-sm">
            Tampilkan
          </button>

          <button type="button" class="btn btn-outline-navy btn-sm" data-action="a-csv">
            Export CSV
          </button>
        </form>

        <div class="spp-gap">
          <p class="form-help">
            Total transaksi: <strong>2</strong> · Total pemasukan (berhasil):
            <strong>Rp150.000</strong>
          </p>

          <div class="table-wrap spp-table">
            <table class="score-table">
              <caption class="visually-hidden">
                Laporan transaksi SPP
              </caption>

              <thead>
                <tr>
                  <th scope="col">No. Transaksi</th>
                  <th scope="col">Siswa</th>
                  <th scope="col">Kelas</th>
                  <th scope="col">Bulan</th>
                  <th scope="col">Nominal</th>
                  <th scope="col">Tanggal</th>
                </tr>
              </thead>

              <tbody>
                <tr>
                  <td data-label="No. Transaksi">SPP-20260705-0001</td>
                  <td data-label="Siswa">Siswa Contoh</td>
                  <td data-label="Kelas">XI RPL 1</td>
                  <td data-label="Bulan">Juli 2026</td>
                  <td data-label="Nominal">Rp150.000</td>
                  <td data-label="Tanggal">5 Jul 2026</td>
                </tr>

                <tr>
                  <td data-label="No. Transaksi">SPP-20260808-0003</td>
                  <td data-label="Siswa">Siswa Contoh</td>
                  <td data-label="Kelas">XI RPL 1</td>
                  <td data-label="Bulan">Agustus 2026</td>
                  <td data-label="Nominal">Rp150.000</td>
                  <td data-label="Tanggal">8 Agu 2026</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Manajemen Akun Siswa -->
        <div class="spp-gap" id="siswa-manager">
          <div class="section-head">
            <div class="section-head__text">
              <h2>Tambah Akun Siswa</h2>
            </div>
          </div>

          <div class="spp-card">
            <h3 id="sw-title">Tambah Siswa</h3>

            <form class="spp-form" method="post">
              <input type="hidden" name="action" value="create_student">

              <div class="form-field">
                <label for="sw-nisn">NISN</label>
                <input type="text" id="sw-nisn" name="nisn" placeholder="Masukkan 10 digit NISN" required maxlength="10"
                  pattern="[0-9]{10}">
                <p class="form-error" data-error-for="sw-nisn"></p>
              </div>

              <div class="form-field">
                <label for="sw-email">Email (untuk login)</label>
                <input type="email" id="sw-email" name="email" placeholder="email@contoh.com" required maxlength="100">
                <p class="form-error" data-error-for="sw-email"></p>
              </div>

              <div class="spp-row">
                <button type="submit" class="btn btn-primary" id="sw-save">
                  Buat Akun Siswa
                </button>
              </div>
            </form>

            <?php if ($studentSuccess): ?>
              <div id="sw-cred" class="spp-gap spp-row">
                <div class="spp-card">
                  <strong>
                    Akun siswa berhasil dibuat untuk
                    <?= htmlspecialchars($studentSuccess['nama']); ?>
                  </strong>

                  <dl class="info-list">
                    <div class="info-list__row">
                      <dt>Username</dt>
                      <dd><?= htmlspecialchars($studentSuccess['nisn']); ?></dd>
                    </div>

                    <div class="info-list__row">
                      <dt>Email</dt>
                      <dd><?= htmlspecialchars($studentSuccess['email']); ?></dd>
                    </div>

                    <div class="info-list__row">
                      <dt>Password</dt>
                      <dd>
                        <code><?= htmlspecialchars($studentSuccess['password']); ?></code>
                      </dd>
                    </div>
                  </dl>

                  <p class="form-help">
                    Catat dan berikan informasi login ini kepada siswa.
                    Password hanya ditampilkan pada hasil pembuatan akun ini.
                  </p>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <form class="spp-row spp-gap" role="search" method="get">
            <label class="visually-hidden" for="sw-q">Cari siswa</label>

            <input type="search" id="sw-q" name="student_q" placeholder="Cari nama / NISN / email"
              value="<?= htmlspecialchars($studentSearch); ?>" />

            <button type="submit" class="btn btn-primary btn-sm">
              Cari
            </button>

            <?php if ($studentSearch !== ''): ?>
              <a href="admin-spp.php#siswa-manager" class="btn btn-outline-navy btn-sm">
                Reset
              </a>
            <?php endif; ?>
          </form>
          <div class="spp-gap">

            <?php if ($resetSuccess): ?>
              <div class="spp-card">
                <strong>Password siswa berhasil di-reset!</strong>

                <dl class="info-list">
                  <div class="info-list__row">
                    <dt>Password baru</dt>
                    <dd>
                      <code><?= htmlspecialchars($resetSuccess['password']); ?></code>
                    </dd>
                  </div>
                </dl>

                <p class="form-help">
                  Catat dan berikan password baru ini kepada siswa.
                  Password hanya ditampilkan pada hasil reset ini.
                </p>
              </div>
            <?php endif; ?>

            <div class="table-wrap spp-table">
              <table class="score-table">
                <caption class="visually-hidden">
                  Daftar akun siswa
                </caption>

                <thead>
                  <tr>
                    <th scope="col">Nama</th>
                    <th scope="col">NISN</th>
                    <th scope="col">Email</th>
                    <th scope="col">Kelas</th>
                    <th scope="col">Aksi</th>
                  </tr>
                </thead>

                <tbody>
                  <?php if (empty($studentAccounts)): ?>

                    <tr>
                      <td colspan="6">
                        <?= $studentSearch !== ''
                          ? 'Siswa tidak ditemukan.'
                          : 'Belum ada akun siswa.'; ?>
                      </td>
                    </tr>

                  <?php else: ?>

                    <?php foreach ($studentAccounts as $account): ?>
                      <tr>
                        <td data-label="Nama">
                          <?= htmlspecialchars($account['nama']); ?>
                        </td>

                        <td data-label="NISN">
                          <?= htmlspecialchars($account['nisn']); ?>
                        </td>

                        <td data-label="Email">
                          <?= htmlspecialchars($account['email']); ?>
                        </td>

                        <td data-label="Kelas">
                          <?= htmlspecialchars($account['class_name']); ?>
                        </td>

                        <td data-label="Aksi">
                          <form method="post" class="reset-password-form">
                            <input type="hidden" name="action" value="reset_student_password">
                            <input type="hidden" name="user_id" value="<?= (int) $account['user_id']; ?>">

                            <button type="submit" class="btn btn-outline-navy btn-sm">
                              Reset Password
                            </button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>

                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Manajemen Akun Kasir -->
        <div class="spp-gap" id="kasir-manager">
          <div class="section-head">
            <div class="section-head__text">
              <h2>Manajemen Akun Kasir</h2>
              <p>
                Kelola akun petugas kasir yang memiliki akses untuk mencatat
                pembayaran SPP.
              </p>
            </div>
          </div>

          <div class="spp-card">
            <h3>Tambah Akun Kasir</h3>

            <form class="spp-form" method="post">
              <input type="hidden" name="action" value="create_kasir">

              <div class="form-field">
                <label for="k-nama">Nama lengkap</label>
                <input type="text" id="k-nama" name="nama" placeholder="Nama petugas kasir" required />
              </div>

              <div class="form-field">
                <label for="k-email">Email</label>
                <input type="email" id="k-email" name="email" placeholder="kasir@contoh.com" required />
              </div>

              <button type="submit" class="btn btn-primary">
                Buat Akun Kasir
              </button>
            </form>

            <?php if ($kasirSuccess): ?>
              <div id="sw-cred" class="spp-gap spp-row">
                <div class="spp-card">
                  <strong>
                    Akun kasir berhasil dibuat untuk
                    <?= htmlspecialchars($kasirSuccess['nama']); ?>
                  </strong>

                  <div class="info-list__row">
                    <dt>Email</dt>
                    <dd><?= htmlspecialchars($kasirSuccess['email']); ?></dd>
                  </div>

                  <div class="info-list__row">
                    <dt>Password</dt>
                    <dd>
                      <code><?= htmlspecialchars($kasirSuccess['password']); ?></code>
                    </dd>
                  </div>
                  </dl>

                  <p class="form-help">
                    Catat dan berikan informasi login ini kepada kasir.
                    Password hanya ditampilkan pada hasil pembuatan akun ini.
                  </p>
                </div>
              </div>
            <?php endif; ?>
            <form class="spp-row spp-gap" role="search" method="get">
              <label class="visually-hidden" for="sw-q">Cari siswa</label>

              <input type="search" id="sw-q" name="kasir_q" placeholder="Cari nama / email"
                value="<?= htmlspecialchars($kasirSearch); ?>" />

              <button type="submit" class="btn btn-primary btn-sm">
                Cari
              </button>

              <?php if ($kasirSearch !== ''): ?>
                <a href="admin-spp.php#siswa-manager" class="btn btn-outline-navy btn-sm">
                  Reset
                </a>
              <?php endif; ?>
            </form>

            <div class="spp-gap">

              <?php if ($resetSuccess): ?>
                <div class="spp-card">
                  <strong>Password kasir berhasil di-reset!</strong>

                  <dl class="info-list">
                    <div class="info-list__row">
                      <dt>Password baru</dt>
                      <dd>
                        <code><?= htmlspecialchars($resetSuccess['password']); ?></code>
                      </dd>
                    </div>
                  </dl>

                  <p class="form-help">
                    Catat dan berikan password baru ini kepada kasir.
                    Password hanya ditampilkan pada hasil reset ini.
                  </p>
                </div>
              <?php endif; ?>

            </div>

            <div class="spp-gap">
              <div class="table-wrap spp-table">
                <table class="score-table">
                  <caption class="visually-hidden">
                    Daftar akun kasir
                  </caption>

                  <thead>
                    <tr>
                      <th>Nama</th>
                      <th>Email</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>

                  <tbody>
                    <?php if (empty($kasirAccounts)): ?>

                      <tr>
                        <td colspan="6">
                          <?= $kasirSearch !== '' ? 'Kasir tidak ditemukan.' : 'Belum ada akun siswa.'; ?>
                        </td>
                      </tr>

                    <?php else: ?>

                      <?php foreach ($kasirAccounts as $account): ?>
                        <tr>
                          <td data-label="Nama">
                            <?= htmlspecialchars($account['name']); ?>
                          </td>

                          <td data-label="Email">
                            <?= htmlspecialchars($account['email']); ?>
                          </td>

                          <td data-label="Aksi">
                            <form method="post" class="reset-password-form">
                              <input type="hidden" name="action" value="reset_kasir_password">
                              <input type="hidden" name="user_id" value="<?= (int) $account['user_id']; ?>">

                              <button type="submit" class="btn btn-outline-navy btn-sm">
                                Reset Password
                              </button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>

                    <?php endif; ?>
                  </tbody>
                </table>
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