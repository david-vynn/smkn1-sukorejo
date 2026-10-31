<?php

$host = 'localhost';
$user = 'belajar';
$password = 'Belajar123!';
$db = 'db_smk_latihan';

$conn = mysqli_connect($host, $user, $password, $db);

if (!$conn) {
    die(" Gagal Masuk" . mysqli_connect_error());
}

echo 'Masuk';

?>