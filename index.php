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
    $books_query .= " AND (b.title LIKE '%$search%' OR b.author LIKE '%$search%')";
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
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-2">
                    <span class="text-3xl">📚</span>
                    <span class="text-xl font-bold text-gray-800">Perpustakaan Online</span>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (isLoggedIn()): ?>
                        <a href="subscription.php" class="text-blue-600 hover:text-blue-800 font-semibold">
                            ⭐ Langganan
                        </a>
                        <a href="my-books.php" class="text-gray-700 hover:text-gray-900">Buku Saya</a>
                        <div class="relative group">
                            <button class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                                <span class="font-semibold"><?php echo $_SESSION['full_name']; ?></span>
                                <span>▼</span>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl hidden group-hover:block">
                                <?php if (isAdmin()): ?>
                                    <a href="admin/dashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Dashboard Admin</a>
                                <?php endif; ?>
                                <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login-user.php" class="text-gray-700 hover:text-gray-900">Login</a>
                        <a href="register.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Banner Hero -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-20">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-5xl font-bold mb-4">Selamat Datang di Perpustakaan Digital</h1>
            <p class="text-xl mb-8">Ribuan buku menanti untuk dibaca. Mulai petualangan literasimu sekarang!</p>
            <a href="subscription.php" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 inline-block">
                Lihat Paket Langganan
            </a>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Buku Gratis -->
        <?php if (mysqli_num_rows($free_books_result) > 0): ?>
            <section class="mb-12">
                <h2 class="text-3xl font-bold text-gray-800 mb-6">📖 Buku Gratis</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php while ($book = mysqli_fetch_assoc($free_books_result)): ?>
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                            <div class="h-64 bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white text-6xl">
                                📗
                            </div>
                            <div class="p-4">
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">GRATIS</span>
                                <h3 class="font-bold text-lg mt-2 mb-1"><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p class="text-gray-600 text-sm mb-2">Oleh: <?php echo htmlspecialchars($book['author']); ?></p>
                                <a href="book-detail.php?id=<?php echo $book['id']; ?>" 
                                   class="block text-center bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 mt-3">
                                    Baca Sekarang
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Filter dan Pencarian -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <input type="text" name="search" placeholder="Cari buku..." value="<?php echo htmlspecialchars($search); ?>"
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <select name="category" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="0">Semua Kategori</option>
                    <?php 
                    mysqli_data_seek($categories_result, 0);
                    while ($cat = mysqli_fetch_assoc($categories_result)): 
                    ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    Cari
                </button>
            </form>
        </div>

        <!-- Semua Buku -->
        <section>
            <h2 class="text-3xl font-bold text-gray-800 mb-6">📚 Koleksi Buku</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php while ($book = mysqli_fetch_assoc($books_result)): ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                        <div class="h-64 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-6xl">
                            📘
                        </div>
                        <div class="p-4">
                            <?php if ($book['is_free']): ?>
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">GRATIS</span>
                            <?php else: ?>
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">PREMIUM</span>
                            <?php endif; ?>
                            <h3 class="font-bold text-lg mt-2 mb-1"><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p class="text-gray-600 text-sm mb-1">Oleh: <?php echo htmlspecialchars($book['author']); ?></p>
                            <p class="text-gray-500 text-xs mb-2"><?php echo htmlspecialchars($book['category_name']); ?></p>
                            <p class="text-blue-600 font-bold text-lg mb-3">
                                <?php echo $book['is_free'] ? 'Gratis' : formatRupiah($book['price']); ?>
                            </p>
                            <a href="book-detail.php?id=<?php echo $book['id']; ?>" 
                               class="block text-center bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2025 Perpustakaan Online. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>