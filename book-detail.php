<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($book_id <= 0) {
    redirect('index.php');
}

$query = "SELECT b.*, c.name as category_name FROM books b 
          LEFT JOIN categories c ON b.category_id = c.id 
          WHERE b.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $book_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$book = mysqli_fetch_assoc($result);

if (!$book) {
    redirect('index.php');
}

$user_id = getUserId();
$can_read = false;
$already_purchased = false;

if ($user_id) {
    $can_read = canReadBook($conn, $user_id, $book);
    $already_purchased = hasPurchasedBook($conn, $user_id, $book_id);
}

$success = isset($_GET['success']) ? true : false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - Perpustakaan Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes slideInFromTop {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(99, 102, 241, 0.4); }
            50% { box-shadow: 0 0 40px rgba(99, 102, 241, 0.6); }
        }
        
        .animate-slide-in {
            animation: slideInFromTop 0.6s ease-out;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        
        .book-cover-3d {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
            transform-style: preserve-3d;
            transition: transform 0.3s ease;
        }
        
        .book-cover-3d:hover {
            transform: rotateY(-5deg) rotateX(5deg) scale(1.05);
        }
        
        .book-cover-3d::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent 30%,
                rgba(255, 255, 255, 0.2) 50%,
                transparent 70%
            );
            animation: shimmer 3s infinite;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .btn-glow {
            animation: pulse-glow 2s infinite;
        }
        
        .success-alert {
            animation: slideInFromTop 0.5s ease-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Navbar -->
    <nav class="glass-effect shadow-xl sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="flex items-center space-x-3 group">
                    <span class="text-4xl transition-transform group-hover:scale-110">📚</span>
                    <span class="text-2xl font-bold gradient-text">Perpustakaan Online</span>
                </a>
                <div class="flex items-center space-x-6">
                    <?php if (isLoggedIn()): ?>
                        <a href="subscription.php" class="flex items-center space-x-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-5 py-2.5 rounded-full font-semibold hover:shadow-lg transform hover:scale-105 transition">
                            <span>⭐</span>
                            <span>Langganan</span>
                        </a>
                        <a href="my-books.php" class="text-gray-700 hover:text-indigo-600 font-medium transition">
                            📖 Buku Saya
                        </a>
                        <div class="flex items-center space-x-2 bg-gray-100 px-4 py-2 rounded-full">
                            <div class="w-8 h-8 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                            </div>
                            <span class="font-semibold text-gray-700"><?php echo $_SESSION['full_name']; ?></span>
                        </div>
                        <a href="logout.php" class="text-red-600 hover:text-red-700 font-medium transition">🚪 Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-700 hover:text-indigo-600 font-medium transition">Login</a>
                        <a href="register.php" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-2.5 rounded-full hover:shadow-lg transform hover:scale-105 transition font-semibold">
                            Daftar Gratis
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-12">
        <!-- Success Alert -->
        <?php if ($success): ?>
            <div class="success-alert bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-400 text-green-800 px-6 py-4 rounded-2xl mb-8 shadow-lg flex items-center space-x-3">
                <span class="text-3xl">✅</span>
                <div>
                    <p class="font-bold text-lg">Pembelian Berhasil!</p>
                    <p class="text-sm">Anda sekarang dapat membaca buku ini. Selamat membaca!</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Book Detail Card -->
        <div class="glass-effect rounded-3xl shadow-2xl overflow-hidden animate-fade-in">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-0">
                <!-- Cover Buku Section -->
                <div class="lg:col-span-1 bg-gradient-to-br from-indigo-100 to-purple-100 p-8 flex items-center justify-center">
                    <div class="animate-slide-in">
                        <div class="book-cover-3d rounded-2xl h-[500px] w-full max-w-[350px] flex items-center justify-center text-white shadow-2xl">
                            <span class="text-[150px] relative z-10">
                                <?php echo $book['is_free'] ? '📗' : '📘'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Detail Buku Section -->
                <div class="lg:col-span-2 p-10">
                    <div class="animate-slide-in">
                        <!-- Badge Status -->
                        <div class="mb-4">
                            <?php if ($book['is_free']): ?>
                                <span class="bg-gradient-to-r from-green-400 to-emerald-500 text-white text-sm font-bold px-4 py-2 rounded-full shadow-lg">
                                    ✨ GRATIS
                                </span>
                            <?php else: ?>
                                <span class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-bold px-4 py-2 rounded-full shadow-lg">
                                    ⭐ PREMIUM
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Title & Author -->
                        <h1 class="text-5xl font-bold text-gray-800 mt-4 mb-3 leading-tight">
                            <?php echo htmlspecialchars($book['title']); ?>
                        </h1>
                        <p class="text-2xl text-gray-600 mb-6 flex items-center">
                            <span class="mr-2">✍️</span>
                            <?php echo htmlspecialchars($book['author']); ?>
                        </p>
                        
                        <!-- Category Badge -->
                        <div class="flex items-center space-x-3 mb-8">
                            <div class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-full font-semibold flex items-center space-x-2">
                                <span>📂</span>
                                <span><?php echo htmlspecialchars($book['category_name']); ?></span>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-8 bg-gray-50 p-6 rounded-2xl">
                            <h3 class="font-bold text-xl mb-3 flex items-center text-gray-800">
                                <span class="text-2xl mr-2">📝</span>
                                Deskripsi Buku
                            </h3>
                            <p class="text-gray-700 leading-relaxed text-lg">
                                <?php echo nl2br(htmlspecialchars($book['description'])); ?>
                            </p>
                        </div>

                        <!-- Price & Action Section -->
                        <div class="border-t-2 border-gray-200 pt-8">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <p class="text-gray-600 text-sm mb-1">Harga:</p>
                                    <p class="text-5xl font-bold gradient-text">
                                        <?php echo $book['is_free'] ? 'Gratis' : formatRupiah($book['price']); ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="space-y-4">
                                <?php if ($book['is_free']): ?>
                                    <a href="read-book.php?id=<?php echo $book['id']; ?>" 
                                       class="block text-center bg-gradient-to-r from-green-500 to-emerald-600 text-white px-10 py-4 rounded-2xl hover:shadow-2xl font-bold text-xl transform hover:scale-105 transition btn-glow">
                                        📖 Baca Sekarang - Gratis!
                                    </a>
                                <?php elseif ($can_read): ?>
                                    <a href="read-book.php?id=<?php echo $book['id']; ?>" 
                                       class="block text-center bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-10 py-4 rounded-2xl hover:shadow-2xl font-bold text-xl transform hover:scale-105 transition btn-glow">
                                        📖 Baca Sekarang
                                    </a>
                                    <?php if ($already_purchased): ?>
                                        <div class="bg-green-50 border-2 border-green-300 text-green-700 px-6 py-3 rounded-2xl flex items-center space-x-2">
                                            <span class="text-2xl">✓</span>
                                            <span class="font-semibold">Anda sudah membeli buku ini</span>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (isLoggedIn()): ?>
                                        <!-- UPDATED: Redirect ke checkout page -->
                                        <a href="checkout-book.php?id=<?php echo $book_id; ?>"
                                           class="block text-center w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-10 py-4 rounded-2xl hover:shadow-2xl font-bold text-xl transform hover:scale-105 transition btn-glow">
                                            🛒 Beli Sekarang - <?php echo formatRupiah($book['price']); ?>
                                        </a>
                                        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-300 p-6 rounded-2xl">
                                            <p class="text-gray-700 text-center">
                                                <span class="font-semibold">💡 Hemat lebih banyak!</span><br>
                                                <a href="subscription.php" class="text-indigo-600 hover:text-indigo-700 font-bold text-lg underline">
                                                    Berlangganan sekarang
                                                </a> 
                                                untuk akses <span class="font-bold">UNLIMITED</span> ke semua buku premium!
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <a href="login.php" class="block text-center bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-10 py-4 rounded-2xl hover:shadow-2xl font-bold text-xl transform hover:scale-105 transition">
                                            🔐 Login untuk Membeli
                                        </a>
                                        <p class="text-center text-gray-600 text-sm">
                                            Belum punya akun? <a href="register.php" class="text-indigo-600 font-semibold hover:underline">Daftar gratis</a>
                                        </p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-8">
            <a href="index.php" class="inline-flex items-center space-x-2 text-indigo-600 hover:text-indigo-700 font-semibold text-lg transition group">
                <span class="transform group-hover:-translate-x-1 transition">←</span>
                <span>Kembali ke Beranda</span>
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gradient-to-r from-gray-900 via-indigo-900 to-purple-900 text-white py-12 mt-20">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <span class="text-4xl">📚</span>
                        <span class="text-2xl font-bold">Perpustakaan Online</span>
                    </div>
                    <p class="text-gray-300">Platform perpustakaan digital terlengkap di Indonesia</p>
                </div>
                <div>
                    <h4 class="font-bold text-lg mb-4">Link Cepat</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="#" class="hover:text-yellow-300 transition">Tentang Kami</a></li>
                        <li><a href="#" class="hover:text-yellow-300 transition">Kontak</a></li>
                        <li><a href="#" class="hover:text-yellow-300 transition">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-lg mb-4">Hubungi Kami</h4>
                    <p class="text-gray-300">📧 info@perpustakaan.com</p>
                    <p class="text-gray-300">📱 +62 123 4567 890</p>
                </div>
            </div>
            <div class="border-t border-gray-700 pt-6 text-center text-gray-400">
                <p>&copy; 2025 Perpustakaan Online. All rights reserved. Made with ❤️</p>
            </div>
        </div>
    </footer>
</body>
</html>