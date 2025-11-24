<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = getUserId();

// Cek langganan aktif
$active_sub_query = "SELECT us.*, sp.name as plan_name FROM user_subscriptions us 
                      JOIN subscription_plans sp ON us.plan_id = sp.id 
                      WHERE us.user_id = ? AND us.is_active = 1 AND us.end_date > NOW() 
                      ORDER BY us.end_date DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $active_sub_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$active_sub_result = mysqli_stmt_get_result($stmt);
$active_subscription = mysqli_fetch_assoc($active_sub_result);

// Buku yang sudah dibeli
$purchased_books = "SELECT b.*, bp.purchased_at FROM book_purchases bp 
                    JOIN books b ON bp.book_id = b.id 
                    WHERE bp.user_id = ? 
                    ORDER BY bp.purchased_at DESC";
$stmt = mysqli_prepare($conn, $purchased_books);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$purchased_result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Saya - Perpustakaan Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes pulse-scale {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        .book-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .book-card:hover {
            transform: translateY(-8px) scale(1.02);
        }
        
        .book-cover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        
        .book-cover::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent 30%,
                rgba(255, 255, 255, 0.1) 50%,
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
        
        .premium-badge {
            animation: pulse-scale 2s infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Navbar dengan Glass Effect -->
    <nav class="glass-effect shadow-xl sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="flex items-center space-x-3 group">
                    <span class="text-4xl transition-transform group-hover:scale-110">📚</span>
                    <span class="text-2xl font-bold gradient-text">Perpustakaan Online</span>
                </a>
                <div class="flex items-center space-x-6">
                    <a href="subscription.php" class="flex items-center space-x-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-5 py-2.5 rounded-full font-semibold hover:shadow-lg transform hover:scale-105 transition">
                        <span>⭐</span>
                        <span>Langganan</span>
                    </a>
                    <a href="my-books.php" class="text-indigo-600 font-bold border-b-2 border-indigo-600 pb-1">
                        📖 Buku Saya
                    </a>
                    <div class="flex items-center space-x-2 bg-gray-100 px-4 py-2 rounded-full">
                        <div class="w-8 h-8 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                            <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                        </div>
                        <span class="font-semibold text-gray-700"><?php echo $_SESSION['full_name']; ?></span>
                    </div>
                    <a href="logout.php" class="text-red-600 hover:text-red-700 font-medium transition">🚪 Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-12">
        <!-- Page Header -->
        <div class="mb-10 animate-fade-in-up">
            <h1 class="text-5xl font-bold gradient-text mb-3 flex items-center">
                <span class="text-6xl mr-4 animate-float">📚</span>
                Perpustakaan Pribadi Saya
            </h1>
            <p class="text-gray-600 text-lg">Koleksi buku yang sudah Anda beli dan miliki</p>
        </div>

        <!-- Status Langganan -->
        <?php if ($active_subscription): ?>
            <div class="glass-effect border-2 border-indigo-300 rounded-3xl p-8 mb-10 animate-fade-in-up shadow-xl relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-yellow-300 to-orange-400 rounded-full -mr-16 -mt-16 opacity-20"></div>
                <div class="relative z-10">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="text-6xl premium-badge">👑</div>
                            <div>
                                <h3 class="text-3xl font-bold text-indigo-900 mb-2">Langganan Premium Aktif</h3>
                                <div class="space-y-1">
                                    <p class="text-indigo-700 text-lg">
                                        <span class="font-semibold">Paket:</span> 
                                        <span class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-4 py-1 rounded-full text-sm font-bold">
                                            <?php echo htmlspecialchars($active_subscription['plan_name']); ?>
                                        </span>
                                    </p>
                                    <p class="text-indigo-700 text-lg">
                                        <span class="font-semibold">Berlaku hingga:</span> 
                                        <strong class="text-indigo-900"><?php echo date('d F Y', strtotime($active_subscription['end_date'])); ?></strong>
                                    </p>
                                </div>
                                <div class="mt-4 bg-green-100 text-green-800 px-4 py-2 rounded-full inline-flex items-center space-x-2">
                                    <span>✨</span>
                                    <span class="font-semibold">Akses UNLIMITED ke semua buku premium!</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="glass-effect border-2 border-yellow-400 rounded-3xl p-8 mb-10 animate-fade-in-up shadow-xl bg-gradient-to-r from-yellow-50 to-orange-50">
                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="text-6xl">💡</div>
                        <div>
                            <h3 class="text-3xl font-bold text-yellow-900 mb-2">Upgrade ke Premium</h3>
                            <p class="text-yellow-800 text-lg mb-4">
                                Berlangganan untuk akses <strong>unlimited</strong> ke semua buku premium!
                            </p>
                            <a href="subscription.php" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-3 rounded-full hover:shadow-xl transform hover:scale-105 transition font-bold inline-flex items-center space-x-2">
                                <span>🚀</span>
                                <span>Lihat Paket Langganan</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Buku yang Sudah Dibeli -->
        <div class="animate-fade-in-up">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-4xl font-bold text-gray-800 mb-2">📖 Koleksi Buku Saya</h2>
                    <p class="text-gray-600">Buku yang sudah Anda beli dan dapat dibaca kapan saja</p>
                </div>
                <?php if (mysqli_num_rows($purchased_result) > 0): ?>
                    <div class="bg-indigo-100 text-indigo-800 px-6 py-3 rounded-full font-bold text-lg">
                        Total: <?php echo mysqli_num_rows($purchased_result); ?> Buku
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (mysqli_num_rows($purchased_result) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-8">
                    <?php while ($book = mysqli_fetch_assoc($purchased_result)): ?>
                        <div class="book-card bg-white rounded-3xl shadow-xl overflow-hidden">
                            <div class="book-cover h-72 flex items-center justify-center text-white text-7xl relative">
                                <span class="relative z-10">📘</span>
                                <div class="absolute top-4 right-4 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold flex items-center space-x-1">
                                    <span>✓</span>
                                    <span>DIMILIKI</span>
                                </div>
                            </div>
                            <div class="p-6">
                                <h3 class="font-bold text-xl mb-2 text-gray-800 line-clamp-2">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </h3>
                                <p class="text-gray-600 text-sm mb-3">
                                    ✍️ <?php echo htmlspecialchars($book['author']); ?>
                                </p>
                                <div class="bg-gray-100 px-3 py-2 rounded-lg mb-4">
                                    <p class="text-xs text-gray-600">
                                        📅 Dibeli: <strong><?php echo date('d M Y', strtotime($book['purchased_at'])); ?></strong>
                                    </p>
                                </div>
                                <a href="read-book.php?id=<?php echo $book['id']; ?>" 
                                   class="block text-center bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3 rounded-xl font-semibold hover:shadow-lg transform hover:scale-105 transition">
                                    📖 Baca Sekarang
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="glass-effect rounded-3xl p-16 text-center shadow-xl">
                    <div class="text-8xl mb-6 animate-float">📚</div>
                    <h3 class="text-3xl font-bold text-gray-800 mb-3">Belum Ada Buku</h3>
                    <p class="text-gray-600 text-lg mb-8 max-w-md mx-auto">
                        Anda belum membeli buku apapun. Mulai jelajahi koleksi kami dan temukan buku favorit Anda!
                    </p>
                    <a href="index.php" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-10 py-4 rounded-full hover:shadow-xl transform hover:scale-105 transition font-bold text-lg inline-flex items-center space-x-2">
                        <span>🔍</span>
                        <span>Jelajahi Koleksi Buku</span>
                    </a>
                </div>
            <?php endif; ?>
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