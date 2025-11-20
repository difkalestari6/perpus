<?php
session_start();
require_once '../config.php';
require_once '../functions.php'; // Pastikan ada ini

if (!isAdmin()) {
    redirect('../login.php');
}

$message = '';
$error = '';

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $author = mysqli_real_escape_string($conn, $_POST['author']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $content = mysqli_real_escape_string($conn, $_POST['content']);
        $price = (float)$_POST['price'];
        $category_id = (int)$_POST['category_id'];
        $is_free = isset($_POST['is_free']) ? 1 : 0;
        
        if ($_POST['action'] === 'create') {
            $query = "INSERT INTO books (title, author, description, content, price, category_id, is_free) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssdiii", $title, $author, $description, $content, $price, $category_id, $is_free);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Buku berhasil ditambahkan!';
            } else {
                $error = 'Gagal menambahkan buku.';
            }
        } elseif ($_POST['action'] === 'update') {
            $book_id = (int)$_POST['book_id'];
            $query = "UPDATE books SET title=?, author=?, description=?, content=?, price=?, category_id=?, is_free=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssdiii", $title, $author, $description, $content, $price, $category_id, $is_free, $book_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Buku berhasil diupdate!';
            } else {
                $error = 'Gagal mengupdate buku.';
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $book_id = (int)$_GET['delete'];
    $delete_query = "DELETE FROM books WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $book_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = 'Buku berhasil dihapus!';
    } else {
        $error = 'Gagal menghapus buku.';
    }
}

// Get all books
$books_query = "SELECT b.*, c.name as category_name FROM books b 
                LEFT JOIN categories c ON b.category_id = c.id 
                ORDER BY b.created_at DESC";
$books_result = mysqli_query($conn, $books_query);

// Get categories for select
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);

// Get book for edit
$edit_book = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_query = "SELECT * FROM books WHERE id = ?";
    $stmt = mysqli_prepare($conn, $edit_query);
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $edit_result = mysqli_stmt_get_result($stmt);
    $edit_book = mysqli_fetch_assoc($edit_result);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Buku - Admin</title>
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
                    <a href="books.php" class="block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
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
            <h1 class="text-3xl font-bold text-gray-800 mb-8">Kelola Buku</h1>

            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Form Tambah/Edit Buku -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <?php echo $edit_book ? 'Edit Buku' : 'Tambah Buku Baru'; ?>
                </h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="<?php echo $edit_book ? 'update' : 'create'; ?>">
                    <?php if ($edit_book): ?>
                        <input type="hidden" name="book_id" value="<?php echo $edit_book['id']; ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Judul Buku *</label>
                            <input type="text" name="title" required
                                   value="<?php echo $edit_book ? htmlspecialchars($edit_book['title']) : ''; ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Penulis *</label>
                            <input type="text" name="author" required
                                   value="<?php echo $edit_book ? htmlspecialchars($edit_book['author']) : ''; ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Kategori *</label>
                            <select name="category_id" required
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Pilih Kategori</option>
                                <?php 
                                mysqli_data_seek($categories_result, 0);
                                while ($cat = mysqli_fetch_assoc($categories_result)): 
                                ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo ($edit_book && $edit_book['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Harga (Rp)</label>
                            <input type="number" name="price" step="1000" min="0"
                                   value="<?php echo $edit_book ? $edit_book['price'] : '0'; ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Deskripsi *</label>
                        <textarea name="description" rows="3" required
                                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo $edit_book ? htmlspecialchars($edit_book['description']) : ''; ?></textarea>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Konten Buku *</label>
                        <textarea name="content" rows="10" required
                                  placeholder="Masukkan konten lengkap buku di sini..."
                                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo $edit_book ? htmlspecialchars($edit_book['content']) : ''; ?></textarea>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_free" value="1"
                                   <?php echo ($edit_book && $edit_book['is_free']) ? 'checked' : ''; ?>
                                   class="mr-2">
                            <span class="text-gray-700 font-semibold">Buku Gratis (Dapat dibaca tanpa login)</span>
                        </label>
                    </div>

                    <div class="flex space-x-4">
                        <button type="submit" 
                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-semibold">
                            <?php echo $edit_book ? 'Update Buku' : 'Tambah Buku'; ?>
                        </button>
                        <?php if ($edit_book): ?>
                            <a href="books.php" 
                               class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 font-semibold">
                                Batal
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Daftar Buku -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-bold text-gray-800">Daftar Buku</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Judul</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Penulis</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Harga</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while ($book = mysqli_fetch_assoc($books_result)): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo $book['id']; ?></td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($book['title']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($book['author']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($book['category_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo formatRupiah($book['price']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($book['is_free']): ?>
                                            <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">Gratis</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">Premium</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm space-x-2">
                                        <a href="books.php?edit=<?php echo $book['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-800 font-semibold">Edit</a>
                                        <a href="books.php?delete=<?php echo $book['id']; ?>" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus buku ini?')"
                                           class="text-red-600 hover:text-red-800 font-semibold">Hapus</a>
                                        <a href="../book-detail.php?id=<?php echo $book['id']; ?>" 
                                           target="_blank"
                                           class="text-gray-600 hover:text-gray-800 font-semibold">Lihat</a>
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