<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isAdmin()) {
    redirect('../login.php');
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter & Search
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$verified_filter = isset($_GET['verified']) ? sanitize($_GET['verified']) : '';

// Build query
$where = array();
$params = array();
$types = "";

if (!empty($search)) {
    $where[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($role_filter)) {
    $where[] = "role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if ($verified_filter !== '') {
    $where[] = "email_verified = ?";
    $params[] = (int)$verified_filter;
    $types .= "i";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count total
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
$count_stmt = mysqli_prepare($conn, $count_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$total_users = mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];
$total_pages = ceil($total_users / $limit);

// Get users
$query = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$users_result = mysqli_stmt_get_result($stmt);

// Handle Actions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $user_id = (int)$_POST['user_id'];
        
        // Cek jangan hapus admin terakhir
        $admin_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'admin'"))['total'];
        $user_to_delete = mysqli_fetch_assoc(mysqli_query($conn, "SELECT role FROM users WHERE id = $user_id"));
        
        if ($user_to_delete['role'] === 'admin' && $admin_count <= 1) {
            $error = "Tidak dapat menghapus admin terakhir!";
        } else {
            $delete_query = "DELETE FROM users WHERE id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
            
            if (mysqli_stmt_execute($delete_stmt)) {
                $success = "User berhasil dihapus!";
            } else {
                $error = "Gagal menghapus user!";
            }
        }
    }
    
    if ($action === 'verify') {
        $user_id = (int)$_POST['user_id'];
        $verify_query = "UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?";
        $verify_stmt = mysqli_prepare($conn, $verify_query);
        mysqli_stmt_bind_param($verify_stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($verify_stmt)) {
            $success = "Email berhasil diverifikasi!";
        } else {
            $error = "Gagal memverifikasi email!";
        }
    }
    
    if ($action === 'edit') {
        $user_id = (int)$_POST['user_id'];
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $full_name = sanitize($_POST['full_name']);
        $role = sanitize($_POST['role']);
        
        // Validasi
        if (empty($username) || empty($email) || empty($full_name)) {
            $error = "Semua field harus diisi!";
        } else {
            // Cek username/email duplikat
            $check_query = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "ssi", $username, $email, $user_id);
            mysqli_stmt_execute($check_stmt);
            
            if (mysqli_num_rows(mysqli_stmt_get_result($check_stmt)) > 0) {
                $error = "Username atau email sudah digunakan!";
            } else {
                $update_query = "UPDATE users SET username = ?, email = ?, full_name = ?, role = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "ssssi", $username, $email, $full_name, $role, $user_id);
                
                if (mysqli_stmt_execute($update_stmt)) {
                    $success = "User berhasil diupdate!";
                } else {
                    $error = "Gagal mengupdate user!";
                }
            }
        }
    }
}

// Get user detail for modal
$user_detail = null;
if (isset($_GET['detail'])) {
    $detail_id = (int)$_GET['detail'];
    $detail_query = "SELECT * FROM users WHERE id = ?";
    $detail_stmt = mysqli_prepare($conn, $detail_query);
    mysqli_stmt_bind_param($detail_stmt, "i", $detail_id);
    mysqli_stmt_execute($detail_stmt);
    $user_detail = mysqli_fetch_assoc(mysqli_stmt_get_result($detail_stmt));
    
    // Get purchases
    $purchases = getUserPurchasedBooks($conn, $detail_id);
    
    // Get subscription
    $subscription = getUserSubscription($conn, $detail_id);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin</title>
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
                    <a href="users.php" class="block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
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
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Kelola User</h1>
                <p class="text-gray-600">Manage semua user dan hak akses</p>
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

            <!-- Filter & Search -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" name="search" placeholder="Username, email, nama..." value="<?php echo htmlspecialchars($search); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select name="role" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Role</option>
                            <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status Verifikasi</label>
                        <select name="verified" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Status</option>
                            <option value="1" <?php echo $verified_filter === '1' ? 'selected' : ''; ?>>Terverifikasi</option>
                            <option value="0" <?php echo $verified_filter === '0' ? 'selected' : ''; ?>>Belum Verifikasi</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            🔍 Filter
                        </button>
                        <a href="users.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-bold text-gray-800">Daftar User (<?php echo $total_users; ?>)</h2>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Lengkap</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Terdaftar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">#<?php echo $user['id']; ?></td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($user['email_verified']): ?>
                                            <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">✓ Verified</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-800">⚠ Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex gap-2">
                                            <a href="?detail=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-800" title="Detail">👁️</a>
                                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" class="text-green-600 hover:text-green-800" title="Edit">✏️</button>
                                            <?php if (!$user['email_verified']): ?>
                                                <form method="POST" class="inline" onsubmit="return confirm('Verifikasi email user ini?')">
                                                    <input type="hidden" name="action" value="verify">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="text-yellow-600 hover:text-yellow-800" title="Verifikasi">✓</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Hapus user ini?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-800" title="Hapus">🗑️</button>
                                            </form>
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
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&verified=<?php echo urlencode($verified_filter); ?>" 
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

    <!-- Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full">
            <h2 class="text-2xl font-bold mb-6">Edit User</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <input type="text" name="username" id="edit_username" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" id="edit_email" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                    <input type="text" name="full_name" id="edit_full_name" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <select name="role" id="edit_role" class="w-full px-3 py-2 border rounded-lg">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Simpan
                    </button>
                    <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Detail Modal -->
    <?php if ($user_detail): ?>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Detail User</h2>
                <a href="users.php" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</a>
            </div>
            
            <div class="space-y-4 mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Username</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($user_detail['username']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Email</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($user_detail['email']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Nama Lengkap</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($user_detail['full_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Role</p>
                        <p class="font-semibold"><?php echo ucfirst($user_detail['role']); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="mb-6">
                <h3 class="text-lg font-bold mb-3">Subscription</h3>
                <?php if ($subscription): ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <p class="font-semibold text-green-800"><?php echo $subscription['plan_name']; ?></p>
                        <p class="text-sm text-green-600">Berakhir: <?php echo date('d M Y', strtotime($subscription['end_date'])); ?></p>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">Tidak ada subscription aktif</p>
                <?php endif; ?>
            </div>
            
            <div>
                <h3 class="text-lg font-bold mb-3">Buku yang Dibeli (<?php echo count($purchases); ?>)</h3>
                <?php if (!empty($purchases)): ?>
                    <div class="space-y-2">
                        <?php foreach ($purchases as $book): ?>
                            <div class="bg-gray-50 border rounded-lg p-3">
                                <p class="font-semibold"><?php echo htmlspecialchars($book['title']); ?></p>
                                <p class="text-sm text-gray-600">
                                    <?php echo formatRupiah($book['paid_price']); ?> • 
                                    <?php echo date('d M Y', strtotime($book['purchased_at'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">Belum ada pembelian</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
</body>
</html>