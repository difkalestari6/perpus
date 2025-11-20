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
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="flex items-center space-x-2">
                    <span class="text-3xl">📚</span>
                    <span class="text-xl font-bold text-gray-800">Perpustakaan Online</span>
                </a>
                <div class="flex items-center space-x-4">
                    <a href="subscription.php" class="text-blue-600 hover:text-blue-800 font-semibold">⭐ Langganan</a>
                    <a href="my-books.php" class="text-gray-900 font-semibold">Buku Saya</a>
                    <span class="text-gray-700"><?php echo $_SESSION['full_name']; ?></span>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">📖 Buku Saya</h1>

        <!-- Status Langganan -->
        <?php if ($active_subscription): ?>
            <div class="bg-blue-50 border border-blue-400 rounded-lg p-6 mb-8">
                <h3 class="text-xl font-bold text-blue-800 mb-2">⭐ Langganan Premium Aktif</h3>
                <p class="text-blue-700">Paket: <strong><?php echo htmlspecialchars($active_subscription['plan_name']); ?></strong></p>
                <p class="text-blue-700">Berlaku hingga: <strong><?php echo date('d F Y', strtotime($active_subscription['end_date'])); ?></strong></p>
                <p class="text-sm text-blue-600 mt-2">Anda dapat membaca semua buku premium!</p>
            </div>
        <?php else: ?>
            <div class="bg-yellow-50 border border-yellow-400 rounded-lg p-6 mb-8">
                <h3 class="text-xl font-bold text-yellow-800 mb-2">💡 Belum Berlangganan</h3>
                <p class="text-yellow-700 mb-3">Berlangganan untuk akses unlimited ke semua buku premium!</p>
                <a href="subscription.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 inline-block">
                    Lihat Paket Langganan
                </a>
            </div>
        <?php endif; ?>

        <!-- Buku yang Sudah Dibeli -->
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Buku yang Sudah Dibeli</h2>
        
        <?php if (mysqli_num_rows($purchased_result) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php while ($book = mysqli_fetch_assoc($purchased_result)): ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                        <div class="h-64 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-6xl">
                            📘
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-lg mb-1"><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p class="text-gray-600 text-sm mb-2">Oleh: <?php echo htmlspecialchars($book['author']); ?></p>
                            <p class="text-xs text-gray-500 mb-3">
                                Dibeli: <?php echo date('d M Y', strtotime($book['purchased_at'])); ?>
                            </p>
                            <a href="read-book.php?id=<?php echo $book['id']; ?>" 
                               class="block text-center bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                                📖 Baca Sekarang
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-gray-100 rounded-lg p-12 text-center">
                <div class="text-6xl mb-4">📚</div>
                <p class="text-gray-600 text-lg mb-4">Anda belum membeli buku apapun</p>
                <a href="index.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 inline-block">
                    Jelajahi Koleksi Buku
                </a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2025 Perpustakaan Online. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>