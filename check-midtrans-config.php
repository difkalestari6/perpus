<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Midtrans Config</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-6 text-center">🔍 Midtrans Configuration Checker</h1>
            
            <!-- Payment Mode -->
            <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                <h2 class="font-bold text-lg mb-2">Payment Mode</h2>
                <p class="text-2xl">
                    <?php if (PAYMENT_MODE === 'mock'): ?>
                        🎭 <span class="text-yellow-600 font-bold">MOCK MODE</span> (Simulasi)
                    <?php else: ?>
                        💳 <span class="text-green-600 font-bold">MIDTRANS MODE</span> (Real Payment)
                    <?php endif; ?>
                </p>
            </div>

            <!-- Server Key Check -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h2 class="font-bold text-lg mb-2">Server Key</h2>
                <div class="font-mono text-sm mb-2 bg-white p-3 rounded border break-all">
                    <?php echo htmlspecialchars(MIDTRANS_SERVER_KEY); ?>
                </div>
                <div class="space-y-2">
                    <?php
                    $server_key = MIDTRANS_SERVER_KEY;
                    $server_key_length = strlen($server_key);
                    $has_correct_prefix = strpos($server_key, 'SB-Mid-server-') === 0;
                    $is_not_placeholder = strpos($server_key, 'GANTI') === false;
                    ?>
                    
                    <div class="flex items-center">
                        <?php if ($has_correct_prefix): ?>
                            <span class="text-green-500 text-xl mr-2">✓</span>
                            <span class="text-green-700">Format prefix benar (SB-Mid-server-)</span>
                        <?php else: ?>
                            <span class="text-red-500 text-xl mr-2">✗</span>
                            <span class="text-red-700">Format prefix salah (harus dimulai dengan SB-Mid-server-)</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center">
                        <?php if ($server_key_length >= 40): ?>
                            <span class="text-green-500 text-xl mr-2">✓</span>
                            <span class="text-green-700">Panjang key valid (<?php echo $server_key_length; ?> karakter)</span>
                        <?php else: ?>
                            <span class="text-red-500 text-xl mr-2">✗</span>
                            <span class="text-red-700">Panjang key terlalu pendek (<?php echo $server_key_length; ?> karakter, harus ≥40)</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center">
                        <?php if ($is_not_placeholder): ?>
                            <span class="text-green-500 text-xl mr-2">✓</span>
                            <span class="text-green-700">Bukan placeholder</span>
                        <?php else: ?>
                            <span class="text-red-500 text-xl mr-2">✗</span>
                            <span class="text-red-700">Masih menggunakan placeholder (harus diganti)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Client Key Check -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h2 class="font-bold text-lg mb-2">Client Key</h2>
                <div class="font-mono text-sm mb-2 bg-white p-3 rounded border break-all">
                    <?php echo htmlspecialchars(MIDTRANS_CLIENT_KEY); ?>
                </div>
                <div class="space-y-2">
                    <?php
                    $client_key = MIDTRANS_CLIENT_KEY;
                    $client_key_length = strlen($client_key);
                    $has_correct_prefix_client = strpos($client_key, 'SB-Mid-client-') === 0;
                    $is_not_placeholder_client = strpos($client_key, 'GANTI') === false;
                    ?>
                    
                    <div class="flex items-center">
                        <?php if ($has_correct_prefix_client): ?>
                            <span class="text-green-500 text-xl mr-2">✓</span>
                            <span class="text-green-700">Format prefix benar (SB-Mid-client-)</span>
                        <?php else: ?>
                            <span class="text-red-500 text-xl mr-2">✗</span>
                            <span class="text-red-700">Format prefix salah (harus dimulai dengan SB-Mid-client-)</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center">
                        <?php if ($client_key_length >= 40): ?>
                            <span class="text-green-500 text-xl mr-2">✓</span>
                            <span class="text-green-700">Panjang key valid (<?php echo $client_key_length; ?> karakter)</span>
                        <?php else: ?>
                            <span class="text-red-500 text-xl mr-2">✗</span>
                            <span class="text-red-700">Panjang key terlalu pendek (<?php echo $client_key_length; ?> karakter, harus ≥40)</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center">
                        <?php if ($is_not_placeholder_client): ?>
                            <span class="text-green-500 text-xl mr-2">✓</span>
                            <span class="text-green-700">Bukan placeholder</span>
                        <?php else: ?>
                            <span class="text-red-500 text-xl mr-2">✗</span>
                            <span class="text-red-700">Masih menggunakan placeholder (harus diganti)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- API URLs -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h2 class="font-bold text-lg mb-2">API Configuration</h2>
                <div class="space-y-2">
                    <div>
                        <span class="text-gray-600">Snap URL:</span>
                        <code class="ml-2 text-sm bg-white px-2 py-1 rounded"><?php echo MIDTRANS_SNAP_URL; ?></code>
                    </div>
                    <div>
                        <span class="text-gray-600">API URL:</span>
                        <code class="ml-2 text-sm bg-white px-2 py-1 rounded"><?php echo MIDTRANS_API_URL; ?></code>
                    </div>
                    <div>
                        <span class="text-gray-600">Environment:</span>
                        <code class="ml-2 text-sm bg-white px-2 py-1 rounded">
                            <?php echo MIDTRANS_IS_PRODUCTION ? 'Production' : 'Sandbox'; ?>
                        </code>
                    </div>
                </div>
            </div>

            <!-- Overall Status -->
            <?php
            $all_valid = $has_correct_prefix && $server_key_length >= 40 && $is_not_placeholder &&
                        $has_correct_prefix_client && $client_key_length >= 40 && $is_not_placeholder_client;
            ?>
            
            <div class="p-6 rounded-lg text-center <?php echo $all_valid ? 'bg-green-100' : 'bg-red-100'; ?>">
                <?php if ($all_valid): ?>
                    <div class="text-6xl mb-4">✅</div>
                    <h2 class="text-2xl font-bold text-green-800 mb-2">Konfigurasi Valid!</h2>
                    <p class="text-green-700 mb-4">Kredensial Midtrans Anda terlihat valid. Silakan test checkout.</p>
                    <a href="test-midtrans.php" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700">
                        Test Koneksi Midtrans
                    </a>
                <?php else: ?>
                    <div class="text-6xl mb-4">❌</div>
                    <h2 class="text-2xl font-bold text-red-800 mb-2">Kredensial Tidak Valid</h2>
                    <p class="text-red-700 mb-4">Silakan daftar di Midtrans dan update kredensial Anda.</p>
                    <div class="space-y-2">
                        <a href="https://dashboard.sandbox.midtrans.com/register" target="_blank" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700">
                            🔗 Daftar Midtrans
                        </a>
                        <br>
                        <a href="index.php" class="inline-block bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700">
                            Kembali ke Beranda
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (PAYMENT_MODE === 'mock'): ?>
            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-yellow-800">
                    <strong>💡 Info:</strong> Anda sedang menggunakan Mock Mode. Checkout akan berhasil tanpa perlu kredensial Midtrans.
                    Untuk menggunakan Midtrans real, ubah <code>PAYMENT_MODE</code> menjadi <code>'midtrans'</code> di config.php setelah kredensial valid.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>