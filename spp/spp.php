<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// Halaman ini hanya untuk akun siswa yang sudah login.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'siswa') {
    header('Location: ../index.php?login=1');
    exit;
}

require_once __DIR__ . '/../database/config.php';

// Mengambil profil siswa beserta kelas, jurusan, tahun ajaran, dan tarif SPP.
$sql = "
    SELECT s.id, s.nama, s.nisn, c.class_name, c.grade,
           m.name AS major_name, ay.id AS academic_year_id,
           ay.name AS academic_year_name, COALESCE(t.amount, 0) AS monthly_tariff
    FROM students s
    INNER JOIN classes c ON c.id = s.class_id
    INNER JOIN majors m ON m.id = c.major_id
    LEFT JOIN academic_years ay ON ay.is_current = 1 AND ay.status = 'active'
    LEFT JOIN spp_tariffs t ON t.grade = c.grade
    WHERE s.user_id = ?
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    http_response_code(404);
    exit('Data siswa tidak ditemukan. Silakan hubungi administrator sekolah.');
}

$studentId = (int) $student['id'];
$academicYearId = $student['academic_year_id'];
$invoices = [];
$paymentHistory = [];
$totalInvoices = 0;
$totalPayments = 0;
$totalOutstanding = 0;

// Menghitung ringkasan seluruh tagihan siswa dari semua tahun ajaran.
$sql = "
    SELECT
        COALESCE(SUM(amount), 0) AS total_tagihan,
        COALESCE(SUM(CASE WHEN status IN ('unpaid', 'overdue') THEN amount ELSE 0 END), 0) AS total_tunggakan
    FROM spp_invoices
    WHERE student_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $studentId);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalInvoices = (float) $summary['total_tagihan'];
$totalOutstanding = (float) $summary['total_tunggakan'];

// Menampilkan tagihan dari seluruh tahun ajaran agar tunggakan lama tetap terlihat.
$sql = "
    SELECT
        i.ID,
        i.period_month,
        i.period_year,
        i.due_date,
        i.amount,
        i.status,
        ay.name AS academic_year_name
    FROM spp_invoices i
    INNER JOIN academic_years ay ON ay.id = i.academic_year_id
    WHERE i.student_id = ?
    ORDER BY i.period_year DESC, i.period_month DESC, i.ID DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $studentId);
$stmt->execute();
$invoices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Menjumlahkan seluruh pembayaran sukses siswa dari semua tahun ajaran.
$sql = "
    SELECT COALESCE(SUM(p.amount), 0) AS total_pembayaran
    FROM payments p
    INNER JOIN spp_invoices i ON i.ID = p.invoice_id
    WHERE i.student_id = ? AND p.status = 'success'
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $studentId);
$stmt->execute();
$paymentSummary = $stmt->get_result()->fetch_assoc();
$stmt->close();
$totalPayments = (float) $paymentSummary['total_pembayaran'];

// Mengambil riwayat pembayaran sukses milik siswa dari semua tahun ajaran.
$sql = "
    SELECT
        i.period_month,
        i.period_year,
        ay.name AS academic_year_name,
        p.amount,
        p.payment_date,
        p.transaction_code
    FROM payments p
    INNER JOIN spp_invoices i ON i.ID = p.invoice_id
    INNER JOIN academic_years ay ON ay.id = i.academic_year_id
    WHERE i.student_id = ? AND p.status = 'success'
    ORDER BY p.payment_date DESC, p.id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $studentId);
$stmt->execute();
$paymentHistory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$shortMonths = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
    7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
];
$rupiah = static fn($value) => 'Rp' . number_format((float) $value, 0, ',', '.');
$formatDate = static function ($value) use ($shortMonths) {
    $time = strtotime($value);
    return date('d', $time) . ' ' . $shortMonths[(int) date('n', $time)] . ' ' . date('Y', $time);
};
$statusText = $totalOutstanding > 0 ? 'Ada Tunggakan' : 'Lunas';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pembayaran SPP — SMKN 1 Sukorejo</title>
  <link rel="icon" href="../assets/logo/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/variables.css">
  <link rel="stylesheet" href="../css/reset.css">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/spp.css">
</head>
<body data-auth="in">
<header class="site-header">
  <div class="container">
    <a href="../index.php" class="brand">SMKN 1 SUKOREJO</a>
    <nav class="main-nav" aria-label="Navigasi utama">
      <a href="../index.php">Beranda</a>
      <a href="../profil-sekolah.php">Profil Sekolah</a>
      <a href="../agenda.php">Agenda</a>
      <a href="../prestasi.php">Prestasi</a>
      <a href="../contact.php">Kontak</a>
    </nav>
    <div class="header-account">
      <a href="../logout.php" class="user-chip__link">
        <span aria-hidden="true">&#128682;</span>
        Keluar
      </a>
    </div>
  </div>
</header>

<main id="main" data-spp-role="user">
  <div class="page-header page-header--center">
    <div class="container">
      <h1>Pembayaran SPP</h1>
      <p>Lihat tagihan dan riwayat pembayaran SPP Anda.</p>
    </div>
  </div>
  <section class="spp-section">
    <div class="container">
      <div class="spp-grid">
        <div class="spp-card">
          <h3>Data Siswa</h3>
          <dl class="info-list">
            <div class="info-list__row"><dt>Nama</dt><dd><?= htmlspecialchars($student['nama'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div class="info-list__row"><dt>NISN</dt><dd><?= htmlspecialchars($student['nisn'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div class="info-list__row"><dt>Kelas</dt><dd><?= htmlspecialchars($student['class_name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div class="info-list__row"><dt>Jurusan</dt><dd><?= htmlspecialchars($student['major_name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div class="info-list__row"><dt>Tahun Ajaran</dt><dd><?= htmlspecialchars($student['academic_year_name'] ?? 'Belum ditentukan', ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div class="info-list__row"><dt>SPP per Bulan</dt><dd><?= $rupiah($student['monthly_tariff']) ?></dd></div>
          </dl>
        </div>
        <div class="spp-stats">
          <div class="spp-card spp-stat"><span class="stat-block__label">Total Tagihan</span><strong class="spp-stat__v"><?= $rupiah($totalInvoices) ?></strong></div>
          <div class="spp-card spp-stat"><span class="stat-block__label">Total Pembayaran</span><strong class="spp-stat__v"><?= $rupiah($totalPayments) ?></strong></div>
          <div class="spp-card spp-stat"><span class="stat-block__label">Total Tunggakan</span><strong class="spp-stat__v"><?= $rupiah($totalOutstanding) ?></strong></div>
          <div class="spp-card spp-stat"><span class="stat-block__label">Status</span><strong class="spp-stat__v"><?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?></strong></div>
        </div>
      </div>

      <div class="section-head spp-gap"><div class="section-head__text"><h2>Daftar Tagihan</h2></div></div>
      <div class="table-wrap spp-table">
        <table class="score-table">
          <caption class="visually-hidden">Daftar tagihan SPP siswa</caption>
          <thead><tr><th scope="col">Bulan</th><th scope="col">Tahun Ajaran</th><th scope="col">Nominal</th><th scope="col">Jatuh Tempo</th><th scope="col">Status</th></tr></thead>
          <tbody>
          <?php if ($invoices): ?>
            <?php foreach ($invoices as $invoice): ?>
              <?php
                $statusMap = [
                    'paid' => ['Lunas', 'spp-badge--paid'],
                    'overdue' => ['Menunggak', 'spp-badge--overdue'],
                    'unpaid' => ['Belum Bayar', 'spp-badge--unpaid']
                ];
                [$label, $class] = $statusMap[$invoice['status']] ?? ['Tidak diketahui', 'spp-badge--pending'];
              ?>
              <tr>
                <td data-label="Bulan"><?= $monthNames[(int) $invoice['period_month']] . ' ' . htmlspecialchars($invoice['period_year']) ?></td>
                <td data-label="Tahun Ajaran"><?= htmlspecialchars($invoice['academic_year_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td data-label="Nominal"><?= $rupiah($invoice['amount']) ?></td>
                <td data-label="Jatuh Tempo"><?= $formatDate($invoice['due_date']) ?></td>
                <td data-label="Status"><span class="spp-badge <?= $class ?>"><?= $label ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="5">Belum ada tagihan.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="section-head spp-gap"><div class="section-head__text"><h2>Riwayat Pembayaran</h2></div></div>
      <div class="table-wrap spp-table">
        <table class="score-table">
          <caption class="visually-hidden">Riwayat pembayaran SPP siswa</caption>
          <thead><tr><th scope="col">Bulan</th><th scope="col">Tahun Ajaran</th><th scope="col">Nominal</th><th scope="col">Tanggal</th><th scope="col">No. Transaksi</th></tr></thead>
          <tbody>
          <?php if ($paymentHistory): ?>
            <?php foreach ($paymentHistory as $payment): ?>
              <tr>
                <td data-label="Bulan"><?= $monthNames[(int) $payment['period_month']] . ' ' . htmlspecialchars($payment['period_year']) ?></td>
                <td data-label="Tahun Ajaran"><?= htmlspecialchars($payment['academic_year_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td data-label="Nominal"><?= $rupiah($payment['amount']) ?></td>
                <td data-label="Tanggal"><?= $formatDate($payment['payment_date']) ?></td>
                <td data-label="No. Transaksi"><?= htmlspecialchars($payment['transaction_code'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="5">Belum ada riwayat pembayaran.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</main>

<footer class="site-footer">
  <div class="container footer-top">
    <div class="footer-brand">
      <p class="footer-brand__name">SMKN 1 SUKOREJO</p>
      <p>Mencetak generasi muda yang kompeten, berkarakter, profesional, dan siap menghadapi tantangan industri global.</p>
    </div>
    <div class="footer-col"><h3>Tautan Cepat</h3><ul><li><a href="../profil-sekolah.php">Profil Sekolah</a></li><li><a href="../agenda.php">Agenda</a></li></ul></div>
    <div class="footer-col"><h3>Informasi</h3><ul><li><a href="../prestasi.php">Prestasi</a></li></ul></div>
    <div class="footer-col footer-contact"><h3>Kontak Kami</h3><ul><li><span>Jl. Raya Sukorejo No. 1, Pasuruan, Jawa Timur</span></li><li><span>(0343) 611234</span></li><li><span>info@smkn1sukorejo.sch.id</span></li></ul></div>
  </div>
  <div class="footer-bottom">&copy; 2026 SMKN 1 SUKOREJO. All rights reserved.</div>
</footer>
<script src="../js/script.js" defer></script>
</body>
</html>
