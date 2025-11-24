<?php
require_once 'config.php';

// Ambil kategori
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);

// Ambil buku gratis
$free_books_query = "SELECT b.*, c.name as category_name FROM books b 
                      LEFT JOIN categories c ON b.category_id = c.id 
                      WHERE b.is_free = 1 ORDER BY b.created_at DESC LIMIT 4";
$free_books_result = mysqli_query($conn, $free_books_query);

// Ambil semua buku
$filter_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$books_query = "SELECT b.*, c.name as category_name FROM books b 
                LEFT JOIN categories c ON b.category_id = c.id WHERE 1=1";

if ($filter_category > 0) {
    $books_query .= " AND b.category_id = $filter_category";
}

if (!empty($search)) {
    $books_query .= " AND (b.title LIKE '%$search%' OR b.author LIKE '%$search%' OR c.name LIKE '%$search%')";
}

$books_query .= " ORDER BY b.created_at DESC";
$books_result = mysqli_query($conn, $books_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Perpustakaan Online</title>
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
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
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
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .hero-pattern {
            background-color: #4f46e5;
            background-image: 
                radial-gradient(at 40% 20%, hsla(240, 100%, 70%, 0.3) 0px, transparent 50%),
                radial-gradient(at 80% 0%, hsla(280, 100%, 70%, 0.3) 0px, transparent 50%),
                radial-gradient(at 0% 50%, hsla(260, 100%, 70%, 0.3) 0px, transparent 50%);
        }
        
        .search-glow:focus {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- Navbar dengan Glass Effect -->
    <nav class="glass-effect shadow-xl sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3 group">
                    <span class="text-4xl animate-float">📚</span>
                    <span class="text-2xl font-bold gradient-text">Perpustakaan Online</span>
                </div>
                <div class="flex items-center space-x-6">
                    <?php if (isLoggedIn()): ?>
                        <a href="subscription.php" class="flex items-center space-x-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-5 py-2.5 rounded-full font-semibold hover:shadow-lg transform hover:scale-105 transition">
                            <span>⭐</span>
                            <span>Langganan</span>
                        </a>
                        <a href="my-books.php" class="text-gray-700 hover:text-indigo-600 font-medium transition">
                            📖 Buku Saya
                        </a>
                        <div class="relative group">
                            <button class="flex items-center space-x-2 bg-gray-100 px-4 py-2 rounded-full hover:bg-gray-200 transition">
                                <div class="w-8 h-8 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                    <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                                </div>
                                <span class="font-semibold text-gray-700"><?php echo $_SESSION['full_name']; ?></span>
                                <span class="text-gray-500">▼</span>
                            </button>
                            <div class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                <?php if (isAdmin()): ?>
                                    <a href="admin/dashboard.php" class="block px-4 py-3 text-gray-800 hover:bg-indigo-50 rounded-t-2xl transition">
                                        🔧 Dashboard Admin
                                    </a>
                                <?php endif; ?>
                                <a href="logout.php" class="block px-4 py-3 text-red-600 hover:bg-red-50 rounded-b-2xl transition">
                                    🚪 Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login-user.php" class="text-gray-700 hover:text-indigo-600 font-medium transition">Login</a>
                        <a href="register.php" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-2.5 rounded-full hover:shadow-lg transform hover:scale-105 transition font-semibold">
                            Daftar Gratis
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Banner dengan Pattern -->
    <div class="hero-pattern text-white py-24 relative overflow-hidden">
        <div class="absolute inset-0 bg-black opacity-10"></div>
        <div class="container mx-auto px-4 text-center relative z-10 animate-fade-in-up">
            <h1 class="text-6xl font-bold mb-6 leading-tight">
                Selamat Datang di<br/>
                <span class="text-yellow-300">Perpustakaan Digital</span>
            </h1>
            <p class="text-2xl mb-10 text-indigo-100 max-w-3xl mx-auto">
                Ribuan buku menanti untuk dibaca. Mulai petualangan literasimu sekarang! 📖
            </p>
            <div class="flex justify-center space-x-4">
                <a href="subscription.php" class="bg-white text-indigo-600 px-10 py-4 rounded-full font-bold text-lg hover:bg-yellow-300 hover:text-indigo-700 transform hover:scale-105 transition shadow-xl">
                    🚀 Lihat Paket Langganan
                </a>
                <a href="#books" class="bg-indigo-700 bg-opacity-50 text-white px-10 py-4 rounded-full font-bold text-lg hover:bg-opacity-70 transition border-2 border-white border-opacity-30">
                    📚 Jelajahi Buku
                </a>
            </div>
        </div>
        
        <!-- Decorative Elements -->
        <div class="absolute top-20 left-10 text-6xl opacity-20 animate-float">📖</div>
        <div class="absolute bottom-20 right-20 text-6xl opacity-20 animate-float" style="animation-delay: 1s;">📚</div>
        <div class="absolute top-40 right-40 text-5xl opacity-20 animate-float" style="animation-delay: 2s;">✨</div>
    </div>

    <div class="container mx-auto px-4 py-12">
        <!-- Buku Gratis dengan Desain Khusus -->
        <?php if (mysqli_num_rows($free_books_result) > 0): ?>
            <section class="mb-16 animate-fade-in-up">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-4xl font-bold gradient-text mb-2">📗 Buku Gratis</h2>
                        <p class="text-gray-600">Mulai membaca tanpa biaya sepeserpun!</p>
                    </div>
                    <div class="bg-green-100 text-green-800 px-6 py-2 rounded-full font-semibold">
                        100% Gratis
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <?php while ($book = mysqli_fetch_assoc($free_books_result)): ?>
                        <div class="book-card bg-white rounded-3xl shadow-xl overflow-hidden">
                            <div class="book-cover h-72 bg-gradient-to-br from-green-400 via-emerald-500 to-teal-600 flex items-center justify-center text-white text-7xl relative">
                                📗
                                <div class="absolute top-4 right-4 bg-white text-green-600 px-3 py-1 rounded-full text-sm font-bold">
                                    GRATIS
                                </div>
                            </div>
                            <div class="p-6">
                                <h3 class="font-bold text-xl mb-2 text-gray-800 line-clamp-2">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </h3>
                                <p class="text-gray-600 text-sm mb-1">✍️ <?php echo htmlspecialchars($book['author']); ?></p>
                                <p class="text-gray-500 text-xs mb-4">📂 <?php echo htmlspecialchars($book['category_name']); ?></p>
                                <a href="book-detail.php?id=<?php echo $book['id']; ?>" 
                                   class="block text-center bg-gradient-to-r from-green-500 to-emerald-600 text-white py-3 rounded-xl font-semibold hover:shadow-lg transform hover:scale-105 transition">
                                    Baca Sekarang →
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Filter dan Pencarian dengan Desain Modern -->
        <div id="books" class="glass-effect rounded-3xl shadow-xl p-8 mb-12 animate-fade-in-up">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <span class="text-3xl mr-3">🔍</span>
                Cari Buku Favoritmu
            </h3>
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative">
                    <input type="text" name="search" placeholder="Cari judul buku, nama penulis, atau kategori..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="w-full px-6 py-4 border-2 border-gray-200 rounded-2xl focus:outline-none focus:border-indigo-500 search-glow transition text-gray-700 font-medium">
                    <span class="absolute right-4 top-4 text-2xl">🔎</span>
                </div>
                <select name="category" class="px-6 py-4 border-2 border-gray-200 rounded-2xl focus:outline-none focus:border-indigo-500 transition text-gray-700 font-medium bg-white">
                    <option value="0">📚 Semua Kategori</option>
                    <?php 
                    mysqli_data_seek($categories_result, 0);
                    while ($cat = mysqli_fetch_assoc($categories_result)): 
                    ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-10 py-4 rounded-2xl hover:shadow-xl transform hover:scale-105 transition font-bold text-lg">
                    Cari Buku
                </button>
            </form>
        </div>

        <!-- Koleksi Buku dengan Card Premium -->
        <section class="animate-fade-in-up">
            <div class="mb-8">
                <h2 class="text-4xl font-bold gradient-text mb-2">📚 Koleksi Buku Premium</h2>
                <p class="text-gray-600">Temukan buku terbaik untuk menambah wawasanmu</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-8">
                <?php while ($book = mysqli_fetch_assoc($books_result)): ?>
                    <div class="book-card bg-white rounded-3xl shadow-xl overflow-hidden">
                        <div class="book-cover h-72 bg-gradient-to-br from-indigo-400 via-purple-500 to-pink-500 flex items-center justify-center text-white text-7xl relative">
                            <?php echo $book['is_free'] ? '📗' : '📘'; ?>
                            <div class="absolute top-4 right-4 <?php echo $book['is_free'] ? 'bg-green-500' : 'bg-indigo-600'; ?> text-white px-3 py-1 rounded-full text-sm font-bold">
                                <?php echo $book['is_free'] ? 'GRATIS' : 'PREMIUM'; ?>
                            </div>
                        </div>
                        <div class="p-6">
                            <h3 class="font-bold text-xl mb-2 text-gray-800 line-clamp-2">
                                <?php echo htmlspecialchars($book['title']); ?>
                            </h3>
                            <p class="text-gray-600 text-sm mb-1">✍️ <?php echo htmlspecialchars($book['author']); ?></p>
                            <p class="text-gray-500 text-xs mb-3">📂 <?php echo htmlspecialchars($book['category_name']); ?></p>
                            <div class="flex items-center justify-between mb-4">
                                <p class="<?php echo $book['is_free'] ? 'text-green-600' : 'text-indigo-600'; ?> font-bold text-2xl">
                                    <?php echo $book['is_free'] ? 'Gratis' : formatRupiah($book['price']); ?>
                                </p>
                            </div>
                            <a href="book-detail.php?id=<?php echo $book['id']; ?>" 
                               class="block text-center bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3 rounded-xl font-semibold hover:shadow-lg transform hover:scale-105 transition">
                                Lihat Detail →
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    </div>

    <!-- Footer dengan Desain Modern -->
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