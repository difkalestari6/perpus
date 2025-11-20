<?php
require_once 'config.php';

$message = '';
$success = false;

if (isset($_GET['token'])) {
    $token = sanitize($_GET['token']); // ✅ FIXED - Hapus parameter $conn
    
    // Cari user dengan token ini
    $query = "SELECT id, email_verified FROM users WHERE verification_token = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if ($user['email_verified'] == 1) {
            $message = 'Email Anda sudah diverifikasi sebelumnya.';
            $success = true;
        } else {
            // Update status verifikasi
            $update_query = "UPDATE users SET email_verified = 1, verification_token = NULL WHERE verification_token = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "s", $token);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Email berhasil diverifikasi! Sekarang Anda bisa login.';
                $success = true;
            } else {
                $message = 'Terjadi kesalahan saat verifikasi email.';
            }
        }
    } else {
        $message = 'Token verifikasi tidak valid atau sudah kadaluarsa.';
    }
} else {
    $message = 'Token verifikasi tidak ditemukan.';
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
<body class="bg-gradient-to-br from-purple-600 to-blue-500 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-lg text-center">
        <!-- Icon -->
        <div class="text-6xl mb-6">
            <?php echo $success ? '✅' : '❌'; ?>
        </div>
        
        <!-- Title -->
        <h1 class="text-3xl font-bold text-gray-800 mb-4">
            <?php echo $success ? 'Verifikasi Berhasil!' : 'Verifikasi Gagal'; ?>
        </h1>
        
        <!-- Message -->
        <p class="text-gray-600 mb-8 leading-relaxed">
            <?php echo $message; ?>
        </p>
        
        <!-- Button -->
        <?php if ($success): ?>
            <a href="login.php" 
               class="inline-block bg-gradient-to-r from-purple-600 to-blue-500 text-white font-semibold px-8 py-3 rounded-lg hover:shadow-lg transform hover:-translate-y-0.5 transition duration-200">
                Login Sekarang
            </a>
        <?php else: ?>
            <a href="register.php" 
               class="inline-block bg-gradient-to-r from-purple-600 to-blue-500 text-white font-semibold px-8 py-3 rounded-lg hover:shadow-lg transform hover:-translate-y-0.5 transition duration-200">
                Kembali ke Registrasi
            </a>
        <?php endif; ?>
    </div>
</body>
</html>