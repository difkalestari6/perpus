<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = getUserId();

// Ambil semua paket langganan
$plans_query = "SELECT * FROM subscription_plans ORDER BY duration_days ASC";
$plans_result = mysqli_query($conn, $plans_query);

// Cek langganan aktif user
$active_subscription = getUserSubscription($conn, $user_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paket Langganan Premium - Perpustakaan Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes pulse-glow {
            0%, 100% { 
                box-shadow: 0 0 20px rgba(99, 102, 241, 0.5);
            }
            50% { 
                box-shadow: 0 0 40px rgba(99, 102, 241, 0.8);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        .pricing-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .pricing-card:hover {
            transform: translateY(-10px);
        }
        
        .pricing-card.featured {
            animation: pulse-glow 2s infinite;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .shimmer-bg {
            background: linear-gradient(
                90deg,
                transparent 0%,
                rgba(255, 255, 255, 0.3) 50%,
                transparent 100%
            );
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
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
                    <span class="text-2xl font-bold gradient-text">Perpustakaan Online</span>
                </a>
                <div class="flex items-center space-x-6">
                    <a href="index.php" class="text-gray-700 hover:text-indigo-600 font-medium transition">
                        🏠 Beranda
                    </a>
                    <a href="my-books.php" class="text-gray-700 hover:text-indigo-600 font-medium transition">
                        📖 Buku Saya
                    </a>
                    <div class="flex items-center space-x-2 bg-gray-100 px-4 py-2 rounded-full">
                        <div class="w-8 h-8 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                            <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                        </div>
                        <span class="font-semibold text-gray-700"><?php echo $_SESSION['full_name']; ?></span>
                    </div>
                    <a href="logout.php" class="text-red-600 hover:text-red-700 font-medium transition">🚪 Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-12">
        <!-- Header Section -->
        <div class="text-center mb-16 animate-fade-in-up">
            <div class="inline-block mb-4">
                <span class="text-8xl animate-float">👑</span>
            </div>
            <h1 class="text-6xl font-bold gradient-text mb-4">Paket Langganan Premium</h1>
            <p class="text-gray-600 text-xl max-w-3xl mx-auto">
                Akses <strong>unlimited</strong> ke ribuan buku premium! Baca sepuasnya tanpa batas.
            </p>
        </div>

        <!-- Active Subscription Status -->
        <?php if ($active_subscription): ?>
            <div class="mb-12 animate-fade-in-up">
                <div class="glass-effect border-2 border-green-400 rounded-3xl p-8 shadow-2xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="text-6xl">✅</div>
                            <div>
                                <h3 class="text-3xl font-bold text-green-900 mb-2">Langganan Aktif</h3>
                                <p class="text-green-700 text-lg">
                                    Paket: <strong class="text-green-900"><?php echo htmlspecialchars($active_subscription['plan_name']); ?></strong>
                                </p>
                                <p class="text-green-700 text-lg">
                                    Berlaku hingga: <strong class="text-green-900"><?php echo date('d F Y', strtotime($active_subscription['end_date'])); ?></strong>
                                </p>
                            </div>
                        </div>
                        <div class="bg-green-100 px-6 py-3 rounded-full">
                            <p class="text-green-800 font-bold text-lg">Status: AKTIF 🎉</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Benefits Section -->
        <div class="mb-16 animate-fade-in-up">
            <div class="glass-effect rounded-3xl p-10 shadow-xl">
                <h2 class="text-4xl font-bold text-center text-gray-800 mb-8">✨ Keuntungan Berlangganan</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="text-6xl mb-4">📚</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Akses Unlimited</h3>
                        <p class="text-gray-600">Baca semua buku premium tanpa batas sepuasnya</p>
                    </div>
                    <div class="text-center">
                        <div class="text-6xl mb-4">💾</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Download Offline</h3>
                        <p class="text-gray-600">Simpan dan baca buku kapan saja, bahkan tanpa internet</p>
                    </div>
                    <div class="text-center">
                        <div class="text-6xl mb-4">🎯</div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Update Terbaru</h3>
                        <p class="text-gray-600">Akses langsung ke buku-buku terbaru yang dirilis</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            <?php 
            $badges = ['🥉', '🥈', '🥇'];
            $colors = [
                'border-blue-400 bg-gradient-to-br from-blue-50 to-blue-100',
                'border-purple-400 bg-gradient-to-br from-purple-50 to-purple-100',
                'border-yellow-400 bg-gradient-to-br from-yellow-50 to-yellow-100'
            ];
            $button_colors = [
                'from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700',
                'from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700',
                'from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700'
            ];
            $index = 0;
            
            mysqli_data_seek($plans_result, 0);
            while ($plan = mysqli_fetch_assoc($plans_result)): 
                $is_featured = $plan['duration_days'] == 30;
            ?>
                <div class="pricing-card <?php echo $is_featured ? 'featured' : ''; ?> relative animate-fade-in-up" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                    <?php if ($is_featured): ?>
                        <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-2 rounded-full font-bold text-sm shadow-lg z-10 shimmer-bg">
                            ⭐ PALING POPULER
                        </div>
                    <?php endif; ?>
                    
                    <div class="bg-white border-4 <?php echo $colors[$index]; ?> rounded-3xl p-8 shadow-2xl h-full flex flex-col">
                        <div class="text-center mb-6">
                            <div class="text-6xl mb-3"><?php echo $badges[$index]; ?></div>
                            <h3 class="text-3xl font-bold text-gray-800 mb-2">
                                <?php echo htmlspecialchars($plan['name']); ?>
                            </h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars($plan['description']); ?></p>
                        </div>

                        <div class="text-center mb-8">
                            <div class="text-5xl font-bold text-gray-800 mb-2">
                                <?php echo formatRupiah($plan['price']); ?>
                            </div>
                            <p class="text-gray-600 text-lg">untuk <?php echo $plan['duration_days']; ?> hari</p>
                            <div class="mt-3 bg-gray-100 inline-block px-4 py-2 rounded-full">
                                <p class="text-sm text-gray-700">
                                    <strong><?php echo number_format($plan['price'] / $plan['duration_days'], 0, ',', '.'); ?></strong> /hari
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3 mb-8 flex-grow">
                            <div class="flex items-start space-x-3">
                                <span class="text-green-500 text-xl flex-shrink-0">✓</span>
                                <span class="text-gray-700">Akses unlimited semua buku premium</span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="text-green-500 text-xl flex-shrink-0">✓</span>
                                <span class="text-gray-700">Baca tanpa batas</span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="text-green-500 text-xl flex-shrink-0">✓</span>
                                <span class="text-gray-700">Download untuk baca offline</span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="text-green-500 text-xl flex-shrink-0">✓</span>
                                <span class="text-gray-700">Update konten terbaru</span>
                            </div>
                            <div class="flex items-start space-x-3">
                                <span class="text-green-500 text-xl flex-shrink-0">✓</span>
                                <span class="text-gray-700">Support 24/7</span>
                            </div>
                        </div>

                        <?php if ($active_subscription && $active_subscription['plan_id'] == $plan['id']): ?>
                            <button disabled class="w-full bg-gray-400 text-white py-4 rounded-xl font-bold text-lg cursor-not-allowed">
                                Paket Aktif Saat Ini ✅
                            </button>
                        <?php else: ?>
                            <!-- UPDATED: Redirect ke checkout page -->
                            <a href="checkout-subscription.php?plan_id=<?php echo $plan['id']; ?>" 
                               class="block text-center w-full bg-gradient-to-r <?php echo $button_colors[$index]; ?> text-white py-4 rounded-xl font-bold text-lg shadow-lg transform hover:scale-105 transition">
                                <span>🛒</span>
                                <span>Beli Sekarang</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php 
                $index++;
            endwhile; 
            ?>
        </div>

        <!-- FAQ Section -->
        <div class="animate-fade-in-up">
            <div class="glass-effect rounded-3xl p-10 shadow-xl">
                <h2 class="text-4xl font-bold text-center text-gray-800 mb-10">❓ Pertanyaan Umum</h2>
                <div class="space-y-6 max-w-3xl mx-auto">
                    <div class="bg-white rounded-xl p-6 shadow">
                        <h3 class="font-bold text-xl text-gray-800 mb-2">Apakah bisa dibatalkan?</h3>
                        <p class="text-gray-600">Langganan bersifat otomatis dan tidak dapat dibatalkan di tengah periode. Namun Anda bisa memilih untuk tidak memperpanjang setelah masa berakhir.</p>
                    </div>
                    <div class="bg-white rounded-xl p-6 shadow">
                        <h3 class="font-bold text-xl text-gray-800 mb-2">Bagaimana cara pembayaran?</h3>
                        <p class="text-gray-600">Pembayaran dilakukan melalui Midtrans dengan berbagai metode: kartu kredit/debit, transfer bank, e-wallet, dan gerai retail.</p>
                    </div>
                    <div class="bg-white rounded-xl p-6 shadow">
                        <h3 class="font-bold text-xl text-gray-800 mb-2">Apakah ada trial gratis?</h3>
                        <p class="text-gray-600">Anda bisa membaca buku-buku gratis tanpa berlangganan. Untuk buku premium, silakan pilih paket langganan.</p>
                    </div>
                    <div class="bg-white rounded-xl p-6 shadow">
                        <h3 class="font-bold text-xl text-gray-800 mb-2">Apa yang terjadi setelah langganan berakhir?</h3>
                        <p class="text-gray-600">Setelah langganan berakhir, Anda tidak bisa lagi mengakses buku premium. Namun buku yang sudah Anda beli tetap bisa diakses.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gradient-to-r from-gray-900 via-indigo-900 to-purple-900 text-white py-12 mt-20">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <span class="text-4xl">📚</span>
                        <span class="text-2xl font-bold">Perpustakaan Online</span>
                    </div>
                    <p class="text-gray-300">Platform perpustakaan digital terlengkap di Indonesia</p>
                </div>
                <div>
                    <h4 class="font-bold text-lg mb-4">Link Cepat</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="index.php" class="hover:text-yellow-300 transition">Beranda</a></li>
                        <li><a href="my-books.php" class="hover:text-yellow-300 transition">Buku Saya</a></li>
                        <li><a href="subscription.php" class="hover:text-yellow-300 transition">Langganan</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-lg mb-4">Hubungi Kami</h4>
                    <p class="text-gray-300">📧 info@perpustakaan.com</p>
                    <p class="text-gray-300">📱 +62 123 4567 890</p>
                </div>
            </div>
            <div class="border-t border-gray-700 pt-6 text-center text-gray-400">
                <p>&copy; 2025 Perpustakaan Online. All rights reserved. Made with ❤️</p>
            </div>
        </div>
    </footer>
</body>
</html>