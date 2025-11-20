<?php
session_start();
require_once 'config.php';
require_once 'functions.php'; // Pastikan ada ini

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
    $can_read = canReadBook($conn, $user_id, $book_id);
    $already_purchased = hasPurchasedBook($conn, $user_id, $book_id);
}

// Handle pembelian buku
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_book'])) {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    
    if (!$already_purchased) {
        // Insert pembelian
        $insert_purchase = "INSERT INTO book_purchases (user_id, book_id, price) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_purchase);
        mysqli_stmt_bind_param($stmt, "iid", $user_id, $book_id, $book['price']);
        mysqli_stmt_execute($stmt);
        
        // Insert transaksi
        $insert_transaction = "INSERT INTO transactions (user_id, type, reference_id, amount) VALUES (?, 'book', ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_transaction);
        mysqli_stmt_bind_param($stmt, "iid", $user_id, $book_id, $book['price']);
        mysqli_stmt_execute($stmt);
        
        redirect('book-detail.php?id=' . $book_id . '&success=1');
    }
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
                    <?php if (isLoggedIn()): ?>
                        <a href="subscription.php" class="text-blue-600 hover:text-blue-800 font-semibold">⭐ Langganan</a>
                        <a href="my-books.php" class="text-gray-700 hover:text-gray-900">Buku Saya</a>
                        <span class="text-gray-700"><?php echo $_SESSION['full_name']; ?></span>
                        <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-700 hover:text-gray-900">Login</a>
                        <a href="register.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                ✅ Pembelian berhasil! Anda sekarang dapat membaca buku ini.
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Cover Buku -->
                <div class="md:col-span-1">
                    <div class="bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg h-96 flex items-center justify-center text-white text-9xl shadow-xl">
                        📘
                    </div>
                </div>

                <!-- Detail Buku -->
                <div class="md:col-span-2">
                    <?php if ($book['is_free']): ?>
                        <span class="bg-green-100 text-green-800 text-sm px-3 py-1 rounded-full">GRATIS</span>
                    <?php else: ?>
                        <span class="bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full">PREMIUM</span>
                    <?php endif; ?>
                    
                    <h1 class="text-4xl font-bold text-gray-800 mt-4 mb-2"><?php echo htmlspecialchars($book['title']); ?></h1>
                    <p class="text-xl text-gray-600 mb-4">Oleh: <?php echo htmlspecialchars($book['author']); ?></p>
                    
                    <div class="flex items-center space-x-4 mb-6">
                        <span class="text-gray-600">
                            <strong>Kategori:</strong> <?php echo htmlspecialchars($book['category_name']); ?>
                        </span>
                    </div>

                    <div class="mb-6">
                        <h3 class="font-bold text-lg mb-2">Deskripsi</h3>
                        <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                    </div>

                    <div class="border-t pt-6">
                        <p class="text-3xl font-bold text-blue-600 mb-6">
                            <?php echo $book['is_free'] ? 'Gratis' : formatRupiah($book['price']); ?>
                        </p>

                        <?php if ($book['is_free']): ?>
                            <a href="read-book.php?id=<?php echo $book['id']; ?>" 
                               class="inline-block bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 font-semibold text-lg">
                                📖 Baca Sekarang
                            </a>
                        <?php elseif ($can_read): ?>
                            <a href="read-book.php?id=<?php echo $book['id']; ?>" 
                               class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 font-semibold text-lg">
                                📖 Baca Sekarang
                            </a>
                            <?php if ($already_purchased): ?>
                                <p class="text-green-600 mt-3">✓ Anda sudah membeli buku ini</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (isLoggedIn()): ?>
                                <form method="POST" class="inline-block">
                                    <button type="submit" name="buy_book"
                                            class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 font-semibold text-lg"
                                            onclick="return confirm('Apakah Anda yakin ingin membeli buku ini?')">
                                        🛒 Beli Sekarang
                                    </button>
                                </form>
                                <p class="text-gray-600 mt-4">
                                    Atau <a href="subscription.php" class="text-blue-600 hover:underline font-semibold">berlangganan</a> 
                                    untuk akses unlimited ke semua buku premium!
                                </p>
                            <?php else: ?>
                                <a href="login.php" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 font-semibold text-lg">
                                    Login untuk Membeli
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <a href="index.php" class="text-blue-600 hover:underline">← Kembali ke Beranda</a>
        </div>
    </div>

    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2025 Perpustakaan Online. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>