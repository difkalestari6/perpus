<?php
require_once 'config.php';

$error = '';
$success = '';
$valid_token = false;
$token = '';

// Cek token
if (isset($_GET['token'])) {
    // HAPUS $conn dari parameter
    $token = sanitize($_GET['token']);
    
    // Verifikasi token dan cek expired
    $query = "SELECT id FROM users WHERE reset_token = ? AND reset_token_expire > NOW()";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $valid_token = true;
    } else {
        $error = 'Token tidak valid atau sudah kadaluarsa.';
    }
}

// Proses reset password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Semua field harus diisi!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak sama!';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password
        $update_query = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expire = NULL WHERE reset_token = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $token);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Password berhasil direset! Silakan login dengan password baru.';
            $valid_token = false;
        } else {
            $error = 'Terjadi kesalahan saat reset password.';
        }
    }
}
?>
<!-- Sisanya sama -->

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Perpustakaan Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-purple-600 to-blue-500 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🔑</div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Reset Password</h1>
            <p class="text-gray-600 text-sm">Masukkan password baru Anda</p>
        </div>
        
        <!-- Alert Error -->
        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                <p class="text-sm"><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Alert Success -->
        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                <p class="text-sm"><?php echo $success; ?></p>
            </div>
            <div class="text-center">
                <a href="login.php" 
                   class="inline-block bg-gradient-to-r from-purple-600 to-blue-500 text-white font-semibold px-8 py-3 rounded-lg hover:shadow-lg transform hover:-translate-y-0.5 transition duration-200">
                    Login Sekarang
                </a>
            </div>
        <?php elseif ($valid_token): ?>
            <!-- Form -->
            <form method="POST" action="" class="space-y-5">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <!-- Password Baru -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                    <input type="password" name="password" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                           placeholder="Minimal 6 karakter" 
                           required>
                </div>
                
                <!-- Konfirmasi Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                           placeholder="Ulangi password baru" 
                           required>
                </div>
                
                <!-- Button Submit -->
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-purple-600 to-blue-500 text-white font-semibold py-3 rounded-lg hover:shadow-lg transform hover:-translate-y-0.5 transition duration-200">
                    Reset Password
                </button>
            </form>
        <?php else: ?>
            <div class="text-center">
                <a href="forgot-password.php" 
                   class="inline-block bg-gradient-to-r from-purple-600 to-blue-500 text-white font-semibold px-8 py-3 rounded-lg hover:shadow-lg transform hover:-translate-y-0.5 transition duration-200">
                    Minta Link Baru
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>