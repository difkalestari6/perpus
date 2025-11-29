<?php
require_once 'config.php';

echo "<h1>Test Koneksi Midtrans</h1>";

// Test 1: Config
echo "<h2>1. Config Check</h2>";
echo "Server Key: " . substr(MIDTRANS_SERVER_KEY, 0, 15) . "...<br>";
echo "Client Key: " . substr(MIDTRANS_CLIENT_KEY, 0, 15) . "...<br>";
echo "API URL: " . MIDTRANS_API_URL . "<br>";
echo "Is Production: " . (MIDTRANS_IS_PRODUCTION ? 'Yes' : 'No') . "<br>";

// Test 2: Ping Midtrans
echo "<h2>2. Network Test</h2>";

$test_params = [
    'transaction_details' => [
        'order_id' => 'TEST-' . time(),
        'gross_amount' => 10000
    ],
    'customer_details' => [
        'first_name' => 'Test User',
        'email' => 'test@example.com'
    ],
    'item_details' => [
        [
            'id' => 'TEST-1',
            'price' => 10000,
            'quantity' => 1,
            'name' => 'Test Item'
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, MIDTRANS_API_URL . '/snap/transactions');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: <strong>" . $http_code . "</strong><br>";
echo "Curl Error: " . ($curl_error ?: 'None') . "<br><br>";

echo "<h3>Raw Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($http_code === 201) {
    echo "<h3 style='color: green;'>✅ SUCCESS! Midtrans connection working!</h3>";
    $result = json_decode($response, true);
    if (isset($result['token'])) {
        echo "Token received: " . substr($result['token'], 0, 30) . "...<br>";
    }
} else {
    echo "<h3 style='color: red;'>❌ ERROR! Check your Midtrans credentials!</h3>";
    
    // Try to decode
    $result = json_decode($response, true);
    if ($result) {
        echo "<h3>Decoded Response:</h3>";
        echo "<pre>" . print_r($result, true) . "</pre>";
    } else {
        echo "<h3>JSON Decode Error: " . json_last_error_msg() . "</h3>";
    }
}