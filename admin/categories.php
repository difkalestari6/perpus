<?php
session_start();
require_once '../config.php';
require_once '../functions.php'; // Pastikan ada ini

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
    <!-- Copy navbar dan sidebar dari books.php -->
    <!-- ... -->
    
    <main class="flex-1 p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Kelola Kategori</h1>
        
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Form Tambah/Edit -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-bold mb-4"><?php echo $edit_cat ? 'Edit' : 'Tambah'; ?> Kategori</h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $edit_cat ? 'update' : 'create'; ?>">
                <?php if ($edit_cat): ?>
                    <input type="hidden" name="cat_id" value="<?php echo $edit_cat['id']; ?>">
                <?php endif; ?>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Nama Kategori</label>
                    <input type="text" name="name" required
                           value="<?php echo $edit_cat ? htmlspecialchars($edit_cat['name']) : ''; ?>"
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Deskripsi</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border rounded-lg"><?php echo $edit_cat ? htmlspecialchars($edit_cat['description']) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    <?php echo $edit_cat ? 'Update' : 'Tambah'; ?>
                </button>
                <?php if ($edit_cat): ?>
                    <a href="categories.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 ml-2">Batal</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Tabel Kategori -->
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">ID</th>
                        <th class="px-6 py-3 text-left">Nama</th>
                        <th class="px-6 py-3 text-left">Deskripsi</th>
                        <th class="px-6 py-3 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                        <tr class="border-b">
                            <td class="px-6 py-4"><?php echo $cat['id']; ?></td>
                            <td class="px-6 py-4 font-semibold"><?php echo htmlspecialchars($cat['name']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($cat['description']); ?></td>
                            <td class="px-6 py-4 space-x-2">
                                <a href="?edit=<?php echo $cat['id']; ?>" class="text-blue-600 hover:underline">Edit</a>
                                <a href="?delete=<?php echo $cat['id']; ?>" 
                                   onclick="return confirm('Yakin hapus?')"
                                   class="text-red-600 hover:underline">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>