<?php

// Memuat konfigurasi database dan koneksi MySQL.
require_once __DIR__ . '/../database/config.php';

// Menentukan bahwa respons dari file ini menggunakan format JSON.
header('Content-Type: application/json');

// Mengambil ID jurusan yang dikirim melalui parameter URL.
$majorId = $_GET['major_id'] ?? '';

// Memastikan ID jurusan berupa angka sebelum digunakan dalam query.
if (!is_numeric($majorId)) {
    echo json_encode([]);
    exit;
}

// Menyiapkan query untuk mengambil kelas berdasarkan ID jurusan.
$stmt = mysqli_prepare($conn, "
        SELECT id, class_name
        FROM classes
        WHERE major_id = ?
        ORDER BY
        FIELD(grade, 'X', 'XI', 'XII'),
        class_number ASC"
    );

// Mengikat ID jurusan ke parameter query, lalu menjalankan query.
mysqli_stmt_bind_param( $stmt,'i', $majorId);
mysqli_stmt_execute($stmt);

// Mengambil hasil query dari database.
$result = mysqli_stmt_get_result($stmt);

// Menampung seluruh data kelas yang ditemukan.
$classes = [];

// Memasukkan setiap baris hasil query ke dalam array kelas.
while ($row = mysqli_fetch_assoc($result)) {
    $classes[] = $row;
}

// Mengirim daftar kelas ke JavaScript dalam format JSON.
echo json_encode($classes);

?>