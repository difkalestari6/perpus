<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'pending';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'midtrans';

$user_id = getUserId();

// Jika mode mock, langsung update ke success
if ($mode === 'mock' && $status === 'success') {
    mysqli_begin_transaction($conn);
    
    try {
        if ($type === 'subscription') {
            // Update subscription
            $query = "UPDATE user_subscriptions 
                      SET payment_status = 'success', is_active = 1 
                      WHERE order_id = ? AND user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "si", $order_id, $user_id);
            mysqli_stmt_execute($stmt);
            
            // Nonaktifkan subscription lama
            $query2 = "UPDATE user_subscriptions 
                       SET is_active = 0 
                       WHERE user_id = ? AND order_id != ? AND is_active = 1";
            $stmt2 = mysqli_prepare($conn, $query2);
            mysqli_stmt_bind_param($stmt2, "is", $user_id, $order_id);
            mysqli_stmt_execute($stmt2);
            
        } elseif ($type === 'book') {
            // Update book purchase
            $query = "UPDATE book_purchases 
                      SET payment_status = 'success' 
                      WHERE order_id = ? AND user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "si", $order_id, $user_id);
            mysqli_stmt_execute($stmt);
        }
        
        // Update transactions
        $trans_query = "UPDATE transactions 
                        SET status = 'completed' 
                        WHERE order_id = ? AND user_id = ?";
        $stmt_trans = mysqli_prepare($conn, $trans_query);
        mysqli_stmt_bind_param($stmt_trans, "si", $order_id, $user_id);
        mysqli_stmt_execute($stmt_trans);
        
        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $status = 'error';
    }
}

// Get transaction info
$query = "SELECT * FROM transactions WHERE order_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "si", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$transaction = mysqli_fetch_assoc($result);

if (!$transaction) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pembayaran - Perpustakaan Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        .animate-bounce-slow {
            animation: bounce 2s infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 min-h-screen">
    <div class="container mx-auto px-4 py-12">
        <div class="max-w-2xl mx-auto">
            
            <?php if ($status === 'success'): ?>
                <!-- Success -->
                <div class="bg-white rounded-3xl shadow-2xl p-12 text-center animate-fade-in">
                    <div class="text-8xl mb-6 animate-bounce-slow">🎉</div>
                    <h1 class="text-4xl font-bold text-green-600 mb-4">Pembayaran Berhasil!</h1>
                    <?php if ($mode === 'mock'): ?>
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded-lg mb-6">
                        <p class="text-sm">⚠️ Mode Demo: Ini adalah simulasi pembayaran</p>
                    </div>
                    <?php endif; ?>
                    <p class="text-gray-600 text-lg mb-8">
                        Terima kasih! Transaksi Anda telah berhasil diproses.
                    </p>
                    
                    <div class="bg-gray-50 rounded-2xl p-6 mb-8 text-left">
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Order ID:</span>
                                <span class="font-semibold"><?php echo htmlspecialchars($order_id); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tipe:</span>
                                <span class="font-semibold"><?php echo ucfirst($type); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total:</span>
                                <span class="font-semibold text-green-600"><?php echo formatRupiah($transaction['amount']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="font-semibold text-green-600">✅ Sukses</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <?php if ($type === 'subscription'): ?>
                        <a href="my-books.php" class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 px-6 rounded-xl font-semibold hover:shadow-lg transition">
                            📚 Lihat Koleksi Buku
                        </a>
                        <?php else: ?>
                        <a href="my-books.php" class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 px-6 rounded-xl font-semibold hover:shadow-lg transition">
                            📖 Baca Buku
                        </a>
                        <?php endif; ?>
                        <a href="index.php" class="flex-1 bg-gray-200 text-gray-700 py-3 px-6 rounded-xl font-semibold hover:bg-gray-300 transition">
                            🏠 Beranda
                        </a>
                    </div>
                </div>
                
            <?php elseif ($status === 'pending'): ?>
                <!-- Pending -->
                <div class="bg-white rounded-3xl shadow-2xl p-12 text-center animate-fade-in">
                    <div class="text-8xl mb-6">⏳</div>
                    <h1 class="text-4xl font-bold text-yellow-600 mb-4">Pembayaran Pending</h1>
                    <p class="text-gray-600 text-lg mb-8">
                        Transaksi Anda sedang diproses. Silakan selesaikan pembayaran.
                    </p>
                    
                    <div class="bg-gray-50 rounded-2xl p-6 mb-8 text-left">
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Order ID:</span>
                                <span class="font-semibold"><?php echo htmlspecialchars($order_id); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="font-semibold text-yellow-600">⏳ Menunggu Pembayaran</span>
                            </div>
                        </div>
                    </div>
                    
                    <a href="transactions.php" class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 px-8 rounded-xl font-semibold hover:shadow-lg transition">
                        Lihat Status Transaksi
                    </a>
                </div>
                
            <?php else: ?>
                <!-- Error -->
                <div class="bg-white rounded-3xl shadow-2xl p-12 text-center animate-fade-in">
                    <div class="text-8xl mb-6">❌</div>
                    <h1 class="text-4xl font-bold text-red-600 mb-4">Pembayaran Gagal</h1>
                    <p class="text-gray-600 text-lg mb-8">
                        Maaf, terjadi kesalahan saat memproses pembayaran Anda.
                    </p>
                    
                    <div class="flex gap-4">
                        <a href="subscription.php" class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 px-6 rounded-xl font-semibold hover:shadow-lg transition">
                            🔄 Coba Lagi
                        </a>
                        <a href="index.php" class="flex-1 bg-gray-200 text-gray-700 py-3 px-6 rounded-xl font-semibold hover:bg-gray-300 transition">
                            🏠 Beranda
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</body>
</html>