<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

$error = '';
$success = '';

if (isset($_GET['token'])) {
    // HAPUS $conn dari parameter sanitize()
    $token = sanitize($_GET['token']);
    
    // Cari user berdasarkan token
    $query = "SELECT id, username, email FROM users WHERE verification_token = ? AND email_verified = 0";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Update status verifikasi
        $update_query = "UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "i", $user['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Email berhasil diverifikasi! Silakan login untuk melanjutkan.';
        } else {
            $error = 'Terjadi kesalahan saat verifikasi. Silakan coba lagi.';
        }
    } else {
        $error = 'Token verifikasi tidak valid atau sudah digunakan.';
    }
} else {
    $error = 'Token verifikasi tidak ditemukan.';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email - Perpustakaan Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-600 to-blue-500 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">
                <?php echo $success ? '✅' : '❌'; ?>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Verifikasi Email</h1>
            <p class="text-gray-600 text-sm">Perpustakaan Online</p>
        </div>
        
        <!-- Alert Success -->
        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                <p class="font-medium">Berhasil!</p>
                <p class="text-sm mt-1"><?php echo $success; ?></p>
            </div>
            <a href="login.php" 
               class="block w-full bg-gradient-to-r from-green-600 to-blue-500 text-white font-semibold py-3 rounded-lg text-center hover:shadow-lg transform hover:-translate-y-0.5 transition duration-200">
                Login Sekarang
            </a>
        <?php endif; ?>
        
        <!-- Alert Error -->
        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                <p class="font-medium">Gagal!</p>
                <p class="text-sm mt-1"><?php echo $error; ?></p>
            </div>
            <div class="space-y-3">
                <a href="register.php" 
                   class="block w-full bg-gradient-to-r from-purple-600 to-blue-500 text-white font-semibold py-3 rounded-lg text-center hover:shadow-lg transform hover:-translate-y-0.5 transition duration-200">
                    Daftar Ulang
                </a>
                <a href="login.php" 
                   class="block w-full bg-gray-200 text-gray-700 font-semibold py-3 rounded-lg text-center hover:bg-gray-300 transition duration-200">
                    Kembali ke Login
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>