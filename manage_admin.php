<?php

require_once __DIR__ . '/database/config.php';

function hiddenPassword($prompt)
{
    echo $prompt;

    $command = 'powershell -Command "$p = Read-Host -AsSecureString; $b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($p); [Runtime.InteropServices.Marshal]::PtrToStringAuto($b)"';

    return rtrim(shell_exec($command));
}

echo'=== ADMIN MANAGER ===\n';
echo'1. Buat akun admin\n';
echo'2. Ganti password admin\n';

$pilihan = readline("PIlih menu : ");

if ($pilihan == 1) {
    $username = readline("Username admin: ");
    $email = readline("Email admin: ");
    $password = hiddenPassword("Password admin: ");
    $confirmPassword = hiddenPassword("Konfirmasi password: ");
    
    if (empty($username) || empty($email) || empty($password)) {
        exit("Semua field wajib diisi!\n");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit("Format email tidak valid\n");
        }

    if (strlen($password) < 8) {
        exit("Password minimal 8 karakter!\n");
        }
        
    if ($password !== $confirmPassword) {
            exit("Password tidak cocok!\n");
    }
    
    $check = mysqli_prepare($conn,"SELECT id FROM users WHERE username = ? or email = ?");
    
    mysqli_stmt_bind_param($check,"ss", $username, $email);
    mysqli_stmt_execute($check);
    
    $result = mysqli_stmt_get_result($check);
    
    if (mysqli_num_rows($result) > 0) {
        exit("Username atau email sudah digunakan!");
    }
    
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt,"sss", $username, $email, $hash);

    if (mysqli_stmt_execute($stmt)) {
        echo "Akun admin berhasil dibuat";
        } else {
        echo "Gagal : " . mysqli_error($conn) . "\n";
    }

} elseif ($pilihan == 2) {
    $username = readline("Username admin: ");
    $password = hiddenPassword("Password baru: ");
    $confirmPassword = hiddenPassword("Konfirmasi password: ");
    
    if (empty($username) || empty($password)) {
        exit("Username dan Password wajib diisi");
    }
    
    $check = mysqli_prepare($conn,"SELECT id FROM users WHERE username = ? AND role = 'admin'");
    
    mysqli_stmt_bind_param($check,"s", $username,);
    mysqli_stmt_execute($check);
    
    $result = mysqli_stmt_get_result($check);
    
    if (mysqli_num_rows($result) === 0) {
        exit("Username atau email tidak ditemukan");
    }

    if (strlen($password) < 8) {
        exit("Password minimal 8 karakter!\n");
    }
    
    if ($password !== $confirmPassword) {
        exit("Password tidak cocok!\n");
    }
                
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "UPDATE users SET password = ? WHERE username = ? AND role = 'admin'";
                
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt,"ss", $hash, $username);

    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo "Passord berhasil diganti!\n";
        } else {
            echo "Admin tidak ditemukan atau password sama.\n";
        }
    }
    
    
} else {
    echo "Pilihan tidak valid\n";
}

?>