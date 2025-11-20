<?php
require_once 'config.php';

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($book_id <= 0) {
    redirect('index.php');
}

$query = "SELECT * FROM books WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $book_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$book = mysqli_fetch_assoc($result);

if (!$book) {
    redirect('index.php');
}

// Cek akses buku
$user_id = getUserId();
$can_access = false;

if ($book['is_free']) {
    $can_access = true;
} elseif ($user_id) {
    $can_access = canReadBook($conn, $user_id, $book_id);
}

if (!$can_access) {
    redirect('book-detail.php?id=' . $book_id);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baca: <?php echo htmlspecialchars($book['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .reading-content {
            font-family: 'Georgia', serif;
            font-size: 18px;
            line-height: 1.8;
            max-width: 800px;
            margin: 0 auto;
        }
        .reading-content p {
            margin-bottom: 1.5em;
            text-align: justify;
        }
        .reading-content h1, .reading-content h2, .reading-content h3 {
            margin-top: 2em;
            margin-bottom: 1em;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header Reader -->
    <div class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="book-detail.php?id=<?php echo $book['id']; ?>" class="text-blue-600 hover:text-blue-800">
                    ← Kembali
                </a>
                <h1 class="text-lg font-bold text-gray-800 truncate max-w-md">
                    <?php echo htmlspecialchars($book['title']); ?>
                </h1>
            </div>
            <div class="flex items-center space-x-4">
                <button onclick="decreaseFontSize()" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">A-</button>
                <button onclick="increaseFontSize()" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">A+</button>
                <button onclick="toggleNightMode()" id="nightModeBtn" class="px-3 py-1 bg-gray-800 text-white rounded hover:bg-gray-700">
                    🌙 Mode Malam
                </button>
            </div>
        </div>
    </div>

    <!-- Konten Buku -->
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-8 md:p-12" id="readerContainer">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($book['title']); ?></h1>
                <p class="text-xl text-gray-600">Oleh: <?php echo htmlspecialchars($book['author']); ?></p>
            </div>

            <div class="reading-content" id="bookContent">
                <?php echo nl2br(htmlspecialchars($book['content'])); ?>
            </div>

            <div class="mt-12 pt-8 border-t text-center">
                <p class="text-gray-600 mb-4">--- Selesai ---</p>
                <a href="book-detail.php?id=<?php echo $book['id']; ?>" 
                   class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                    Kembali ke Detail Buku
                </a>
            </div>
        </div>
    </div>

    <script>
        let fontSize = 18;
        let isNightMode = false;

        function increaseFontSize() {
            if (fontSize < 28) {
                fontSize += 2;
                document.querySelector('.reading-content').style.fontSize = fontSize + 'px';
            }
        }

        function decreaseFontSize() {
            if (fontSize > 14) {
                fontSize -= 2;
                document.querySelector('.reading-content').style.fontSize = fontSize + 'px';
            }
        }

        function toggleNightMode() {
            isNightMode = !isNightMode;
            const container = document.getElementById('readerContainer');
            const btn = document.getElementById('nightModeBtn');
            
            if (isNightMode) {
                container.style.backgroundColor = '#1a1a1a';
                container.style.color = '#e0e0e0';
                btn.textContent = '☀️ Mode Terang';
                btn.classList.remove('bg-gray-800');
                btn.classList.add('bg-yellow-500');
            } else {
                container.style.backgroundColor = '#ffffff';
                container.style.color = '#000000';
                btn.textContent = '🌙 Mode Malam';
                btn.classList.remove('bg-yellow-500');
                btn.classList.add('bg-gray-800');
            }
        }

        // Simpan progress membaca (opsional)
        window.addEventListener('scroll', function() {
            const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
            localStorage.setItem('readProgress_' + <?php echo $book_id; ?>, scrollPercent);
        });

        // Load progress terakhir
        window.addEventListener('load', function() {
            const savedProgress = localStorage.getItem('readProgress_' + <?php echo $book_id; ?>);
            if (savedProgress) {
                const scrollPosition = (savedProgress / 100) * (document.documentElement.scrollHeight - window.innerHeight);
                window.scrollTo(0, scrollPosition);
            }
        });
    </script>
</body>
</html>