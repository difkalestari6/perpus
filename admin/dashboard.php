<?php
session_start();
require_once '../config.php';
require_once '../functions.php'; // Pastikan ada ini

if (!isAdmin()) {
    redirect('../login.php');
}

// Statistik
$total_books_query = "SELECT COUNT(*) as total FROM books";
$total_books = mysqli_fetch_assoc(mysqli_query($conn, $total_books_query))['total'];

$total_users_query = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
$total_users = mysqli_fetch_assoc(mysqli_query($conn, $total_users_query))['total'];

$total_transactions_query = "SELECT COUNT(*) as total, SUM(amount) as revenue FROM transactions";
$transactions_data = mysqli_fetch_assoc(mysqli_query($conn, $total_transactions_query));

$active_subscriptions_query = "SELECT COUNT(*) as total FROM user_subscriptions WHERE is_active = 1 AND end_date > NOW()";
$active_subscriptions = mysqli_fetch_assoc(mysqli_query($conn, $active_subscriptions_query))['total'];

// Transaksi terbaru
$recent_transactions = "SELECT t.*, u.username FROM transactions t 
                        JOIN users u ON t.user_id = u.id 
                        ORDER BY t.created_at DESC LIMIT 10";
$recent_trans_result = mysqli_query($conn, $recent_transactions);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Perpustakaan Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-gray-800 text-white">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-2">
                    <span class="text-2xl">🔧</span>
                    <span class="text-xl font-bold">Admin Panel</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../index.php" class="hover:text-gray-300">Lihat Website</a>
                    <span><?php echo $_SESSION['full_name']; ?></span>
                    <a href="../logout.php" class="bg-red-600 px-4 py-2 rounded hover:bg-red-700">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white h-screen shadow-lg">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Menu Admin</h2>
                <nav class="space-y-2">
                    <a href="dashboard.php" class="block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        📊 Dashboard
                    </a>
                    <a href="books.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        📚 Kelola Buku
                    </a>
                    <a href="categories.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        🏷️ Kelola Kategori
                    </a>
                    <a href="users.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        👥 Kelola User
                    </a>
                    <a href="transactions.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">
                        💰 Transaksi
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">Dashboard</h1>

            <!-- Statistik Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Total Buku</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $total_books; ?></p>
                        </div>
                        <div class="text-5xl">📚</div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Total User</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $total_users; ?></p>
                        </div>
                        <div class="text-5xl">👥</div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Langganan Aktif</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $active_subscriptions; ?></p>
                        </div>
                        <div class="text-5xl">⭐</div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Total Pendapatan</p>
                            <p class="text-2xl font-bold text-green-600">
                                <?php echo formatRupiah($transactions_data['revenue'] ?? 0); ?>
                            </p>
                        </div>
                        <div class="text-5xl">💰</div>
                    </div>
                </div>
            </div>

            <!-- Transaksi Terbaru -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-bold text-gray-800">Transaksi Terbaru</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($trans = mysqli_fetch_assoc($recent_trans_result)): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">#<?php echo $trans['id']; ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($trans['username']); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded <?php echo $trans['type'] == 'book' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                            <?php echo $trans['type'] == 'book' ? 'Buku' : 'Langganan'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                        <?php echo formatRupiah($trans['amount']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">
                                            <?php echo ucfirst($trans['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo date('d M Y H:i', strtotime($trans['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>