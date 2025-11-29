<?php
require_once 'config.php';

header('Content-Type: application/json');

// Logging function
function logError($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data) {
        $log .= "\n" . print_r($data, true);
    }
    error_log($log . "\n\n", 3, __DIR__ . '/payment-errors.log');
}

try {
    // Cek user login
    if (!isLoggedIn()) {
        throw new Exception('User tidak login');
    }

    $user_id = getUserId();

    // Ambil data dari request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Validasi JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON format');
    }

    $type = isset($data['type']) ? $data['type'] : '';
    $order_id = isset($data['order_id']) ? $data['order_id'] : '';
    $amount = isset($data['amount']) ? (float)$data['amount'] : 0;

    // Validasi data
    if (empty($type) || empty($order_id) || $amount <= 0) {
        throw new Exception('Data tidak lengkap');
    }

    // Ambil data user
    $user_query = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $user_query);
    
    if (!$stmt) {
        throw new Exception('Database error: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user_result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($user_result);

    if (!$user) {
        throw new Exception('User tidak ditemukan');
    }

    // Item details
    $item_details = [];
    $ref_id = 0;

    // Mulai transaction database
    mysqli_begin_transaction($conn);

    if ($type === 'book') {
        $book_id = isset($data['book_id']) ? (int)$data['book_id'] : 0;
        
        if ($book_id <= 0) {
            throw new Exception('Book ID tidak valid');
        }
        
        // Ambil data buku
        $book_query = "SELECT * FROM books WHERE id = ?";
        $stmt = mysqli_prepare($conn, $book_query);
        mysqli_stmt_bind_param($stmt, "i", $book_id);
        mysqli_stmt_execute($stmt);
        $book_result = mysqli_stmt_get_result($stmt);
        $book = mysqli_fetch_assoc($book_result);
        
        if (!$book) {
            throw new Exception('Buku tidak ditemukan');
        }
        
        // Cek apakah sudah pernah beli
        $check_query = "SELECT id FROM book_purchases WHERE user_id = ? AND book_id = ? AND payment_status = 'success'";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $book_id);
        mysqli_stmt_execute($stmt);
        $check_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            throw new Exception('Anda sudah membeli buku ini');
        }
        
        $item_details[] = [
            'id' => 'BOOK-' . $book_id,
            'price' => (int)$amount,
            'quantity' => 1,
            'name' => substr($book['title'], 0, 50)
        ];
        
        // Hapus pending purchase lama
        $delete_query = "DELETE FROM book_purchases WHERE user_id = ? AND book_id = ? AND payment_status = 'pending'";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $book_id);
        mysqli_stmt_execute($stmt);
        
        // Save pending transaction
        $insert_query = "INSERT INTO book_purchases (order_id, user_id, book_id, price, payment_status) 
                         VALUES (?, ?, ?, ?, 'pending')";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "siid", $order_id, $user_id, $book_id, $amount);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Gagal menyimpan data pembelian');
        }
        
        $ref_id = $book_id;
        
    } elseif ($type === 'subscription') {
        $plan_id = isset($data['plan_id']) ? (int)$data['plan_id'] : 0;
        
        if ($plan_id <= 0) {
            throw new Exception('Plan ID tidak valid');
        }
        
        // Ambil data paket
        $plan_query = "SELECT * FROM subscription_plans WHERE id = ?";
        $stmt = mysqli_prepare($conn, $plan_query);
        mysqli_stmt_bind_param($stmt, "i", $plan_id);
        mysqli_stmt_execute($stmt);
        $plan_result = mysqli_stmt_get_result($stmt);
        $plan = mysqli_fetch_assoc($plan_result);
        
        if (!$plan) {
            throw new Exception('Paket tidak ditemukan');
        }
        
        $item_details[] = [
            'id' => 'SUB-' . $plan_id,
            'price' => (int)$amount,
            'quantity' => 1,
            'name' => $plan['name'] . ' - ' . $plan['duration_days'] . ' hari'
        ];
        
        // Calculate dates
        $start_date = date('Y-m-d H:i:s');
        $end_date = date('Y-m-d H:i:s', strtotime("+{$plan['duration_days']} days"));
        
        // Hapus pending subscription lama
        $delete_query = "DELETE FROM user_subscriptions WHERE order_id = ? AND payment_status = 'pending'";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "s", $order_id);
        mysqli_stmt_execute($stmt);
        
        // Save pending subscription
        $insert_query = "INSERT INTO user_subscriptions (order_id, user_id, plan_id, start_date, end_date, is_active, payment_status) 
                         VALUES (?, ?, ?, ?, ?, 0, 'pending')";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "siiss", $order_id, $user_id, $plan_id, $start_date, $end_date);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Gagal menyimpan data subscription');
        }
        
        $ref_id = $plan_id;
        
    } else {
        throw new Exception('Tipe transaksi tidak valid');
    }

    // Hapus transaksi pending lama
    $delete_trans_query = "DELETE FROM transactions WHERE order_id = ? AND status = 'pending'";
    $stmt = mysqli_prepare($conn, $delete_trans_query);
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    mysqli_stmt_execute($stmt);

    // Save to transactions table
    $trans_query = "INSERT INTO transactions (order_id, user_id, type, reference_id, amount, status, snap_token) 
                    VALUES (?, ?, ?, ?, ?, 'pending', '')";
    $stmt = mysqli_prepare($conn, $trans_query);
    mysqli_stmt_bind_param($stmt, "sisid", $order_id, $user_id, $type, $ref_id, $amount);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Gagal menyimpan transaksi');
    }

    // Commit database transaction
    mysqli_commit($conn);

    // ========================================
    // CEK MODE PAYMENT
    // ========================================
    
    if (PAYMENT_MODE === 'mock') {
        // MODE SIMULASI - Langsung generate token mock
        $mock_token = 'MOCK-TOKEN-' . bin2hex(random_bytes(16));
        
        // Update snap_token
        $update_query = "UPDATE transactions SET snap_token = ? WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ss", $mock_token, $order_id);
        mysqli_stmt_execute($stmt);
        
        logError('MOCK PAYMENT', ['order_id' => $order_id, 'amount' => $amount, 'type' => $type]);
        
        echo json_encode([
            'success' => true,
            'snap_token' => $mock_token,
            'order_id' => $order_id,
            'mode' => 'mock'
        ]);
        exit;
    }
    
    // ========================================
    // MODE MIDTRANS (Real Payment)
    // ========================================
    
    $transaction_details = [
        'order_id' => $order_id,
        'gross_amount' => (int)$amount
    ];

    $customer_details = [
        'first_name' => $user['full_name'],
        'email' => $user['email'],
    ];

    $params = [
        'transaction_details' => $transaction_details,
        'customer_details' => $customer_details,
        'item_details' => $item_details
    ];

    logError('MIDTRANS REQUEST', $params);

    // Call Midtrans Snap API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, MIDTRANS_API_URL . '/snap/transactions');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
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

    logError('MIDTRANS RESPONSE', [
        'http_code' => $http_code,
        'response' => $response,
        'curl_error' => $curl_error
    ]);

    if ($curl_error) {
        throw new Exception('Network error: ' . $curl_error);
    }

    if (empty($response)) {
        throw new Exception('Empty response from Midtrans');
    }

    $result = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON from Midtrans: ' . substr($response, 0, 200));
    }

    if ($http_code === 201 && isset($result['token'])) {
        $update_query = "UPDATE transactions SET snap_token = ? WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ss", $result['token'], $order_id);
        mysqli_stmt_execute($stmt);
        
        echo json_encode([
            'success' => true,
            'snap_token' => $result['token'],
            'order_id' => $order_id,
            'mode' => 'midtrans'
        ]);
    } else {
        $error_message = 'Unknown error';
        if (isset($result['error_messages'][0])) {
            $error_message = $result['error_messages'][0];
        } elseif (isset($result['message'])) {
            $error_message = $result['message'];
        }
        throw new Exception($error_message . ' (HTTP ' . $http_code . ')');
    }

} catch (Exception $e) {
    if (isset($conn)) {
        mysqli_rollback($conn);
    }
    
    logError('EXCEPTION', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Gagal membuat transaksi: ' . $e->getMessage()
    ]);
}