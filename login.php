<?php

session_start();

require_once __DIR__ . "/database/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = 'SELECT * FROM users WHERE email = ? LIMIT 1';

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt,'s', $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        echo 'Login berhasil';
    } else {
        echo 'Email atau password salah';
    }

    if (!$stmt) {
        die('Query gagal disiapkan: ' . mysqli_error($conn));
    }
    
}

?>