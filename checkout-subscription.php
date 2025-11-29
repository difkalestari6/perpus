<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$plan_id = isset($_GET['plan_id']) ? (int)$_GET['plan_id'] : 0;
$user_id = getUserId();

if ($plan_id <= 0) {
    redirect('subscription.php');
}

// Ambil data paket
$query = "SELECT * FROM subscription_plans WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $plan_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$plan = mysqli_fetch_assoc($result);

if (!$plan) {
    redirect('subscription.php');
}

// Ambil data user
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);

// Generate Order ID
$order_id = 'SUB-' . $plan_id . '-' . $user_id . '-' . time();

// Cek payment mode
$is_mock_mode = (PAYMENT_MODE === 'mock');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Langganan - <?php echo htmlspecialchars($plan['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php if (!$is_mock_mode): ?>
    <script type="text/javascript" src="<?php echo MIDTRANS_SNAP_URL; ?>" data-client-key="<?php echo MIDTRANS_CLIENT_KEY; ?>"></script>
    <?php endif; ?>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 min-h-screen">
    <!-- Navbar -->
    <nav class="glass-effect shadow-xl sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="flex items-center space-x-3 group">
                    <span class="text-4xl transition-transform group-hover:scale-110">📚</span>
                    <span class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Perpustakaan Online</span>
                </a>
                <div class="flex items-center space-x-4">
                    <a href="subscription.php" class="text-gray-600 hover:text-indigo-600 transition">
                        ← Kembali
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <?php if ($is_mock_mode): ?>
    <!-- Mock Payment Mode Banner -->
    <div class="container mx-auto px-4 mt-4">
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-lg animate-fade-in">
            <div class="flex items-center">
                <span class="text-2xl mr-3">⚠️</span>
                <div>
                    <p class="font-bold">Mode Demo/Testing</p>
                    <p class="text-sm">Pembayaran akan disimulasikan. Tidak ada transaksi real yang terjadi.</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="container mx-auto px-4 py-12">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12 animate-fade-in">
                <span class="text-6xl mb-4 inline-block">👑</span>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">
                    Checkout Langganan Premium
                </h1>
                <p class="text-gray-600">Tinggal selangkah lagi untuk akses unlimited!</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Detail Paket -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Paket Info -->
                    <div class="bg-white rounded-3xl shadow-xl p-8 animate-fade-in">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <span class="text-3xl mr-3">⭐</span>
                            Detail Paket Langganan
                        </h2>
                        <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-2xl p-6 border-2 border-purple-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-3xl font-bold text-purple-900"><?php echo htmlspecialchars($plan['name']); ?></h3>
                                <span class="text-5xl">
                                    <?php 
                                    if($plan['duration_days'] == 7) echo '🥉';
                                    else if($plan['duration_days'] == 30) echo '🥈';
                                    else echo '🥇';
                                    ?>
                                </span>
                            </div>
                            <p class="text-gray-700 mb-6 text-lg"><?php echo htmlspecialchars($plan['description']); ?></p>
                            
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="bg-white rounded-xl p-4 shadow-sm">
                                    <p class="text-sm text-gray-600 mb-1">⏱️ Durasi</p>
                                    <p class="text-2xl font-bold text-indigo-600"><?php echo $plan['duration_days']; ?> Hari</p>
                                </div>
                                <div class="bg-white rounded-xl p-4 shadow-sm">
                                    <p class="text-sm text-gray-600 mb-1">💰 Harga</p>
                                    <p class="text-2xl font-bold text-indigo-600"><?php echo formatRupiah($plan['price']); ?></p>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl p-4 shadow-sm">
                                <p class="font-semibold text-gray-800 mb-3 flex items-center">
                                    <span class="text-xl mr-2">✨</span>
                                    Keuntungan Berlangganan:
                                </p>
                                <div class="space-y-2">
                                    <div class="flex items-center space-x-3 text-gray-700">
                                        <span class="text-green-500 text-xl">✓</span>
                                        <span>Akses unlimited semua buku premium</span>
                                    </div>
                                    <div class="flex items-center space-x-3 text-gray-700">
                                        <span class="text-green-500 text-xl">✓</span>
                                        <span>Baca tanpa batas waktu dan kuota</span>
                                    </div>
                                    <div class="flex items-center space-x-3 text-gray-700">
                                        <span class="text-green-500 text-xl">✓</span>
                                        <span>Download untuk baca offline</span>
                                    </div>
                                    <div class="flex items-center space-x-3 text-gray-700">
                                        <span class="text-green-500 text-xl">✓</span>
                                        <span>Akses konten terbaru lebih dulu</span>
                                    </div>
                                    <div class="flex items-center space-x-3 text-gray-700">
                                        <span class="text-green-500 text-xl">✓</span>
                                        <span>Support prioritas 24/7</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Pembeli -->
                    <div class="bg-white rounded-3xl shadow-xl p-8 animate-fade-in">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <span class="text-3xl mr-3">👤</span>
                            Informasi Pembeli
                        </h2>
                        <div class="bg-gray-50 rounded-2xl p-6 space-y-4">
                            <div>
                                <label class="text-gray-600 text-sm font-semibold block mb-1">Nama Lengkap</label>
                                <p class="text-gray-800 text-lg font-semibold"><?php echo htmlspecialchars($user['full_name']); ?></p>
                            </div>
                            <div>
                                <label class="text-gray-600 text-sm font-semibold block mb-1">Email</label>
                                <p class="text-gray-800 text-lg"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div>
                                <label class="text-gray-600 text-sm font-semibold block mb-1">Username</label>
                                <p class="text-gray-800 text-lg"><?php echo htmlspecialchars($user['username']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan & Pembayaran -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-3xl shadow-xl p-8 sticky top-24 animate-fade-in">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <span class="text-3xl mr-3">💳</span>
                            Ringkasan
                        </h2>
                        
                        <div class="space-y-4 mb-6 pb-6 border-b-2 border-gray-200">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Paket Langganan</span>
                                <span class="font-semibold text-gray-800"><?php echo formatRupiah($plan['price']); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Durasi</span>
                                <span class="font-semibold text-gray-800"><?php echo $plan['duration_days']; ?> hari</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Biaya Admin</span>
                                <span class="font-semibold text-green-600">Gratis ✨</span>
                            </div>
                        </div>

                        <div class="bg-indigo-50 rounded-xl p-4 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-gray-800">Total Pembayaran</span>
                                <span class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                    <?php echo formatRupiah($plan['price']); ?>
                                </span>
                            </div>
                        </div>

                        <button id="pay-button" class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white py-4 rounded-2xl font-bold text-lg shadow-2xl transform hover:scale-105 transition flex items-center justify-center space-x-2">
                            <span class="text-2xl">👑</span>
                            <span><?php echo $is_mock_mode ? 'Simulasi Pembayaran' : 'Bayar Sekarang'; ?></span>
                        </button>

                        <p class="text-xs text-gray-500 text-center mt-4">
                            <?php echo $is_mock_mode ? '🎭 Mode Demo - Tidak ada transaksi real' : '🔒 Pembayaran aman melalui Midtrans'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const payButton = document.getElementById('pay-button');
        const isMockMode = <?php echo $is_mock_mode ? 'true' : 'false'; ?>;
        
        payButton.addEventListener('click', function() {
            payButton.disabled = true;
            payButton.innerHTML = '<span class="text-2xl">⏳</span><span>Memproses...</span>';
            
            // Request payment
            fetch('payment-process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'subscription',
                    plan_id: <?php echo $plan_id; ?>,
                    amount: <?php echo $plan['price']; ?>,
                    order_id: '<?php echo $order_id; ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.mode === 'mock') {
                        // Mock payment - langsung redirect ke success
                        window.location.href = 'payment-finish.php?order_id=' + data.order_id + '&status=success&type=subscription&mode=mock';
                    } else {
                        // Real Midtrans payment
                        snap.pay(data.snap_token, {
                            onSuccess: function(result) {
                                window.location.href = 'payment-finish.php?order_id=' + result.order_id + '&status=success&type=subscription';
                            },
                            onPending: function(result) {
                                window.location.href = 'payment-finish.php?order_id=' + result.order_id + '&status=pending&type=subscription';
                            },
                            onError: function(result) {
                                window.location.href = 'payment-finish.php?order_id=' + result.order_id + '&status=error&type=subscription';
                            },
                            onClose: function() {
                                payButton.disabled = false;
                                payButton.innerHTML = '<span class="text-2xl">👑</span><span>Bayar Sekarang</span>';
                                alert('⚠️ Anda menutup popup pembayaran. Silakan coba lagi.');
                            }
                        });
                    }
                } else {
                    alert('❌ Error: ' + data.message);
                    payButton.disabled = false;
                    payButton.innerHTML = '<span class="text-2xl">👑</span><span>' + (isMockMode ? 'Simulasi Pembayaran' : 'Bayar Sekarang') + '</span>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.');
                payButton.disabled = false;
                payButton.innerHTML = '<span class="text-2xl">👑</span><span>' + (isMockMode ? 'Simulasi Pembayaran' : 'Bayar Sekarang') + '</span>';
            });
        });
    </script>
</body>
</html>