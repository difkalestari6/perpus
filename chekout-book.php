<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = getUserId();

if ($book_id <= 0) {
    redirect('index.php');
}

// Ambil data buku
$query = "SELECT * FROM books WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $book_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$book = mysqli_fetch_assoc($result);

if (!$book) {
    redirect('index.php');
}

// Cek apakah sudah dibeli
if (hasPurchasedBook($conn, $user_id, $book_id)) {
    redirect('book-detail.php?id=' . $book_id . '&error=already_purchased');
}

// Ambil data user
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);

// Generate Order ID
$order_id = 'BOOK-' . $book_id . '-' . $user_id . '-' . time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Buku - <?php echo htmlspecialchars($book['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="text/javascript" src="<?php echo MIDTRANS_SNAP_URL; ?>" data-client-key="<?php echo MIDTRANS_CLIENT_KEY; ?>"></script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <nav class="bg-white shadow-xl sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <a href="index.php" class="flex items-center space-x-3">
                <span class="text-4xl">📚</span>
                <span class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Perpustakaan Online</span>
            </a>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-12">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-4xl font-bold text-gray-800 mb-8 flex items-center">
                <span class="text-5xl mr-3">🛒</span>
                Checkout Pembelian Buku
            </h1>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Detail Pesanan -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-3xl shadow-xl p-8 mb-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <span class="text-3xl mr-2">📖</span>
                            Detail Buku
                        </h2>
                        <div class="flex items-start space-x-6">
                            <div class="w-32 h-48 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-xl flex items-center justify-center text-white text-6xl flex-shrink-0">
                                📘
                            </div>
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p class="text-gray-600 mb-1">✍️ <?php echo htmlspecialchars($book['author']); ?></p>
                                <p class="text-gray-500 text-sm mb-4"><?php echo excerpt($book['description'], 150); ?></p>
                                <div class="bg-indigo-50 px-4 py-2 rounded-lg inline-block">
                                    <p class="text-indigo-800 font-bold text-2xl"><?php echo formatRupiah($book['price']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Pembeli -->
                    <div class="bg-white rounded-3xl shadow-xl p-8">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <span class="text-3xl mr-2">👤</span>
                            Informasi Pembeli
                        </h2>
                        <div class="space-y-4">
                            <div>
                                <label class="text-gray-600 text-sm font-semibold">Nama Lengkap</label>
                                <p class="text-gray-800 text-lg font-semibold"><?php echo htmlspecialchars($user['full_name']); ?></p>
                            </div>
                            <div>
                                <label class="text-gray-600 text-sm font-semibold">Email</label>
                                <p class="text-gray-800 text-lg"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan & Pembayaran -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-3xl shadow-xl p-8 sticky top-24">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <span class="text-3xl mr-2">💳</span>
                            Ringkasan
                        </h2>
                        
                        <div class="space-y-4 mb-6 pb-6 border-b-2 border-gray-200">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Harga Buku</span>
                                <span class="font-semibold text-gray-800"><?php echo formatRupiah($book['price']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Biaya Admin</span>
                                <span class="font-semibold text-green-600">Gratis</span>
                            </div>
                        </div>

                        <div class="flex justify-between items-center mb-8">
                            <span class="text-xl font-bold text-gray-800">Total Pembayaran</span>
                            <span class="text-3xl font-bold text-indigo-600"><?php echo formatRupiah($book['price']); ?></span>
                        </div>

                        <button id="pay-button" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-4 rounded-2xl font-bold text-lg hover:shadow-2xl transform hover:scale-105 transition flex items-center justify-center space-x-2">
                            <span>💳</span>
                            <span>Bayar Sekarang</span>
                        </button>

                        <p class="text-xs text-gray-500 text-center mt-4">
                            Dengan melanjutkan, Anda menyetujui syarat dan ketentuan kami
                        </p>

                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <p class="text-sm text-gray-600 mb-3 font-semibold">Metode Pembayaran:</p>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="bg-gray-50 p-2 rounded text-center text-xs">💳 Kartu</div>
                                <div class="bg-gray-50 p-2 rounded text-center text-xs">🏦 Bank</div>
                                <div class="bg-gray-50 p-2 rounded text-center text-xs">🏪 Indomaret</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const payButton = document.getElementById('pay-button');
        
        payButton.addEventListener('click', function() {
            payButton.disabled = true;
            payButton.innerHTML = '<span>⏳</span><span>Memproses...</span>';
            
            // Request Snap Token
            fetch('payment-process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'book',
                    book_id: <?php echo $book_id; ?>,
                    amount: <?php echo $book['price']; ?>,
                    order_id: '<?php echo $order_id; ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Panggil Snap
                    snap.pay(data.snap_token, {
                        onSuccess: function(result) {
                            window.location.href = 'payment-finish.php?order_id=' + result.order_id + '&status=success';
                        },
                        onPending: function(result) {
                            window.location.href = 'payment-finish.php?order_id=' + result.order_id + '&status=pending';
                        },
                        onError: function(result) {
                            window.location.href = 'payment-finish.php?order_id=' + result.order_id + '&status=error';
                        },
                        onClose: function() {
                            payButton.disabled = false;
                            payButton.innerHTML = '<span>💳</span><span>Bayar Sekarang</span>';
                            alert('Anda menutup popup pembayaran');
                        }
                    });
                } else {
                    alert('Error: ' + data.message);
                    payButton.disabled = false;
                    payButton.innerHTML = '<span>💳</span><span>Bayar Sekarang</span>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memproses pembayaran');
                payButton.disabled = false;
                payButton.innerHTML = '<span>💳</span><span>Bayar Sekarang</span>';
            });
        });
    </script>
</body>
</html>