<?php
require_once 'config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // HAPUS $conn dari parameter
    $email = sanitize($_POST['email']);
    
    if (empty($email)) {
        $error = 'Email harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        $result = sendResetPasswordEmail($conn, $email);
        
        if ($result['success']) {
            $message = 'Link reset password telah dikirim ke email Anda. Silakan cek inbox atau folder spam.';
        } else {
            $error = $result['message'];
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
    <title>Lupa Password - Perpustakaan Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-purple-600 to-blue-500 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🔐</div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Lupa Password</h1>
            <p class="text-gray-600 text-sm">Masukkan email Anda untuk reset password</p>
        </div>
        
        <!-- Alert Error -->
        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                <p class="text-sm"><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Alert Success -->
        <?php if ($message): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                <p class="text-sm"><?php echo $message; ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Form -->
        <form method="POST" action="" class="space-y-5">
            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" name="email" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                       placeholder="Masukkan email Anda" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                       required>
            </div>
            
            <!-- Button Submit -->
            <button type="submit" 
                    class="w-full bg-gradient-to-r from-purple-600 to-blue-500 text-white font-semibold py-3 rounded-lg hover:shadow-lg transform hover:-translate-y-0.5 transition duration-200">
                Kirim Link Reset
            </button>
        </form>
        
        <!-- Back to Login -->
        <div class="mt-6 text-center text-sm text-gray-600">
            <a href="login.php" class="text-purple-600 font-semibold hover:underline">← Kembali ke Login</a>
        </div>
    </div>
</body>
</html>