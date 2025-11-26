<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isAdmin()) {
    redirect('../login.php');
}

// Pagination
$limit = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter & Search
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Build query
$where = array();
$params = array();
$types = "";

if (!empty($search)) {
    $where[] = "u.username LIKE ?";
    $search_param = "%$search%";
    $params[] = $search_param;
    $types .= "s";
}

if (!empty($type_filter)) {
    $where[] = "t.type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $where[] = "t.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_from)) {
    $where[] = "DATE(t.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $where[] = "DATE(t.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count total
$count_query = "SELECT COUNT(*) as total FROM transactions t 
                JOIN users u ON t.user_id = u.id 
                $where_clause";
$count_stmt = mysqli_prepare($conn, $count_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$total_transactions = mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];
$total_pages = ceil($total_transactions / $limit);

// Get transactions with details
$query = "SELECT t.*, u.username, u.email 
          FROM transactions t 
          JOIN users u ON t.user_id = u.id 
          $where_clause 
          ORDER BY t.created_at DESC 
          LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$transactions_result = mysqli_stmt_get_result($stmt);

// Statistics
$stats_query = "SELECT 
    COUNT(*) as total_count,
    SUM(amount) as total_revenue,
    SUM(CASE WHEN type = 'book' THEN amount ELSE 0 END) as book_revenue,
    SUM(CASE WHEN type = 'subscription' THEN amount ELSE 0 END) as subscription_revenue,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
FROM transactions t 
JOIN users u ON t.user_id = u.id 
$where_clause";

$stats_stmt = mysqli_prepare($conn, $stats_query);
if (!empty($where)) {
    // Remove limit and offset parameters
    $stats_types = substr($types, 0, -2);
    $stats_params = array_slice($params, 0, -2);
    mysqli_stmt_bind_param($stats_stmt, $stats_types, ...$stats_params);
}
mysqli_stmt_execute($stats_stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stats_stmt));

// Handle Actions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $transaction_id = (int)$_POST['transaction_id'];
        $new_status = sanitize($_POST['new_status']);
        
        if (in_array($new_status, ['pending', 'completed', 'failed'])) {
            $update_query = "UPDATE transactions SET status = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "si", $new_status, $transaction_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $success = "Status transaksi berhasil diupdate!";
            } else {
                $error = "Gagal mengupdate status transaksi!";
            }
        } else {
            $error = "Status tidak valid!";
        }
    }
}

// Get transaction detail for modal
$transaction_detail = null;
if (isset($_GET['detail'])) {
    $detail_id = (int)$_GET['detail'];
    $detail_query = "SELECT t.*, u.username, u.email, u.full_name 
                     FROM transactions t 
                     JOIN users u ON t.user_id = u.id 
                     WHERE t.id = ?";
    $detail_stmt = mysqli_prepare($conn, $detail_query);
    mysqli_stmt_bind_param($detail_stmt, "i", $detail_id);
    mysqli_stmt_execute($detail_stmt);
    $transaction_detail = mysqli_fetch_assoc(mysqli_stmt_get_result($detail_stmt));
    
    if ($transaction_detail) {
        // Get item details based on type
        if ($transaction_detail['type'] === 'book') {
            $item_query = "SELECT b.title, b.author, b.price 
                          FROM book_purchases bp 
                          JOIN books b ON bp.book_id = b.id 
                          WHERE bp.id = ?";
            $item_stmt = mysqli_prepare($conn, $item_query);
            mysqli_stmt_bind_param($item_stmt, "i", $transaction_detail['reference_id']);
            mysqli_stmt_execute($item_stmt);
            $transaction_detail['item'] = mysqli_fetch_assoc(mysqli_stmt_get_result($item_stmt));
        } else {
            $item_query = "SELECT sp.name, sp.duration_days, us.start_date, us.end_date 
                          FROM user_subscriptions us 
                          JOIN subscription_plans sp ON us.plan_id = sp.id 
                          WHERE us.id = ?";
            $item_stmt = mysqli_prepare($conn, $item_query);
            mysqli_stmt_bind_param($item_stmt, "i", $transaction_detail['reference_id']);
            mysqli_stmt_execute($item_stmt);
            $transaction_detail['item'] = mysqli_fetch_assoc(mysqli_stmt_get_result($item_stmt));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Transaksi - Admin</title>
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
                    <a href="dashboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded">
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
                    <a href="transactions.php" class="block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        💰 Transaksi
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Kelola Transaksi</h1>
                <p class="text-gray-600">Monitor dan kelola semua transaksi</p>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Total Transaksi</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_count']; ?></p>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Total Pendapatan</p>
                    <p class="text-2xl font-bold text-green-600"><?php echo formatRupiah($stats['total_revenue'] ?? 0); ?></p>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Pendapatan Buku</p>
                    <p class="text-xl font-bold text-blue-600"><?php echo formatRupiah($stats['book_revenue'] ?? 0); ?></p>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Pendapatan Subscription</p>
                    <p class="text-xl font-bold text-purple-600"><?php echo formatRupiah($stats['subscription_revenue'] ?? 0); ?></p>
                </div>
            </div>

            <!-- Filter & Search -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search User</label>
                        <input type="text" name="search" placeholder="Username..." value="<?php echo htmlspecialchars($search); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe</label>
                        <select name="type" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Tipe</option>
                            <option value="book" <?php echo $type_filter === 'book' ? 'selected' : ''; ?>>Buku</option>
                            <option value="subscription" <?php echo $type_filter === 'subscription' ? 'selected' : ''; ?>>Subscription</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Dari Tanggal</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sampai Tanggal</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            🔍 Filter
                        </button>
                        <a href="transactions.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Status Summary -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-sm text-gray-600">Completed</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $stats['completed_count']; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Pending</p>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending_count']; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Failed</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo $stats['failed_count']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-bold text-gray-800">Daftar Transaksi (<?php echo $total_transactions; ?>)</h2>
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($trans = mysqli_fetch_assoc($transactions_result)): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">#<?php echo $trans['id']; ?></td>
                                    <td class="px-6 py-4">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($trans['username']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($trans['email']); ?></p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded <?php echo $trans['type'] === 'book' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                            <?php echo $trans['type'] === 'book' ? '📚 Buku' : '⭐ Subscription'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                        <?php echo formatRupiah($trans['amount']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded 
                                            <?php 
                                            if ($trans['status'] === 'completed') echo 'bg-green-100 text-green-800';
                                            elseif ($trans['status'] === 'pending') echo 'bg-yellow-100 text-yellow-800';
                                            else echo 'bg-red-100 text-red-800';
                                            ?>">
                                            <?php echo ucfirst($trans['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo date('d M Y H:i', strtotime($trans['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex gap-2">
                                            <a href="?detail=<?php echo $trans['id']; ?>" class="text-blue-600 hover:text-blue-800" title="Detail">👁️</a>
                                            <button onclick="openStatusModal(<?php echo $trans['id']; ?>, '<?php echo $trans['status']; ?>')" class="text-green-600 hover:text-green-800" title="Update Status">✏️</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="p-6 border-t">
                        <div class="flex justify-center gap-2">
                            <?php 
                            $query_params = http_build_query([
                                'search' => $search,
                                'type' => $type_filter,
                                'status' => $status_filter,
                                'date_from' => $date_from,
                                'date_to' => $date_to
                            ]);
                            
                            for ($i = 1; $i <= $total_pages; $i++): 
                            ?>
                                <a href="?page=<?php echo $i; ?>&<?php echo $query_params; ?>" 
                                   class="px-4 py-2 rounded <?php echo $page === $i ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <h2 class="text-2xl font-bold mb-6">Update Status Transaksi</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="transaction_id" id="status_transaction_id">
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status Baru</label>
                    <select name="new_status" id="new_status" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Update
                    </button>
                    <button type="button" onclick="closeStatusModal()" class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Detail Modal -->
    <?php if ($transaction_detail): ?>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Detail Transaksi #<?php echo $transaction_detail['id']; ?></h2>
                <a href="transactions.php" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</a>
            </div>
            
            <!-- Transaction Info -->
            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Username</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($transaction_detail['username']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Email</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($transaction_detail['email']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Tipe Transaksi</p>
                        <p class="font-semibold"><?php echo $transaction_detail['type'] === 'book' ? 'Pembelian Buku' : 'Subscription'; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Status</p>
                        <span class="px-2 py-1 text-xs rounded 
                            <?php 
                            if ($transaction_detail['status'] === 'completed') echo 'bg-green-100 text-green-800';
                            elseif ($transaction_detail['status'] === 'pending') echo 'bg-yellow-100 text-yellow-800';
                            else echo 'bg-red-100 text-red-800';
                            ?>">
                            <?php echo ucfirst($transaction_detail['status']); ?>
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Jumlah Pembayaran</p>
                        <p class="font-bold text-green-600 text-xl"><?php echo formatRupiah($transaction_detail['amount']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Tanggal Transaksi</p>
                        <p class="font-semibold"><?php echo date('d M Y H:i:s', strtotime($transaction_detail['created_at'])); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Item Details -->
            <?php if (isset($transaction_detail['item'])): ?>
                <div class="bg-blue-50 rounded-lg p-6">
                    <h3 class="text-lg font-bold mb-3">Detail Item</h3>
                    <?php if ($transaction_detail['type'] === 'book'): ?>
                        <div class="space-y-2">
                            <div>
                                <p class="text-sm text-gray-600">Judul Buku</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($transaction_detail['item']['title']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Penulis</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($transaction_detail['item']['author']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Harga Buku</p>
                                <p class="font-semibold"><?php echo formatRupiah($transaction_detail['item']['price']); ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <div>
                                <p class="text-sm text-gray-600">Paket Subscription</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($transaction_detail['item']['name']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Durasi</p>
                                <p class="font-semibold"><?php echo $transaction_detail['item']['duration_days']; ?> hari</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Periode</p>
                                <p class="font-semibold">
                                    <?php echo date('d M Y', strtotime($transaction_detail['item']['start_date'])); ?> - 
                                    <?php echo date('d M Y', strtotime($transaction_detail['item']['end_date'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function openStatusModal(transactionId, currentStatus) {
            document.getElementById('status_transaction_id').value = transactionId;
            document.getElementById('new_status').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }
    </script>
</body>
</html>