<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isAdmin()) {
    redirect('../login.php');
}

$message = '';

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $query = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $name, $description);
        mysqli_stmt_execute($stmt);
        $message = 'Kategori berhasil ditambahkan!';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update') {
        $cat_id = (int)$_POST['cat_id'];
        $query = "UPDATE categories SET name=?, description=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssi", $name, $description, $cat_id);
        mysqli_stmt_execute($stmt);
        $message = 'Kategori berhasil diupdate!';
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $cat_id = (int)$_GET['delete'];
    $delete_query = "DELETE FROM categories WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $cat_id);
    mysqli_stmt_execute($stmt);
    $message = 'Kategori berhasil dihapus!';
}

// Get all categories
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

// Get category for edit
$edit_cat = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM categories WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $edit_cat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Admin</title>
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
                    <a href="categories.php" class="block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
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
            <h1 class="text-3xl font-bold text-gray-800 mb-8">Kelola Kategori</h1>
            
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Form Tambah/Edit -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <h2 class="text-xl font-bold mb-4">
                    <?php echo $edit_cat ? '✏️ Edit Kategori' : '➕ Tambah Kategori Baru'; ?>
                </h2>
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $edit_cat ? 'update' : 'create'; ?>">
                    <?php if ($edit_cat): ?>
                        <input type="hidden" name="cat_id" value="<?php echo $edit_cat['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Nama Kategori *</label>
                        <input type="text" name="name" required
                               value="<?php echo $edit_cat ? htmlspecialchars($edit_cat['name']) : ''; ?>"
                               placeholder="Contoh: Novel, Teknologi, Bisnis"
                               class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none">
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 font-semibold mb-2">Deskripsi</label>
                        <textarea name="description" rows="3"
                                  placeholder="Deskripsi kategori (opsional)"
                                  class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"><?php echo $edit_cat ? htmlspecialchars($edit_cat['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-semibold transition">
                            <?php echo $edit_cat ? '💾 Update Kategori' : '➕ Tambah Kategori'; ?>
                        </button>
                        <?php if ($edit_cat): ?>
                            <a href="categories.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 font-semibold transition">
                                ❌ Batal
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Tabel Kategori -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b bg-gray-50">
                    <h2 class="text-xl font-bold text-gray-800">📋 Daftar Kategori</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b-2 border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Dibuat</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php 
                            $no = 1;
                            while ($cat = mysqli_fetch_assoc($categories)): 
                            ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 text-sm text-gray-900 font-semibold">#<?php echo $cat['id']; ?></td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo $cat['description'] ? htmlspecialchars($cat['description']) : '<span class="text-gray-400 italic">Tidak ada deskripsi</span>'; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo date('d M Y', strtotime($cat['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            <a href="?edit=<?php echo $cat['id']; ?>" 
                                               class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 text-sm font-semibold transition">
                                                ✏️ Edit
                                            </a>
                                            <a href="?delete=<?php echo $cat['id']; ?>" 
                                               onclick="return confirm('Yakin ingin menghapus kategori \'<?php echo htmlspecialchars($cat['name']); ?>\'?')"
                                               class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 text-sm font-semibold transition">
                                                🗑️ Hapus
                                            </a>
                                        </div>
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