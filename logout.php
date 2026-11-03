<?php

session_start();

// Hapus seluruh data session
session_unset();

// Hancurkan session
session_destroy();

// Kemabli ke halaman utama
header('Location: index.php');
exit;

?>