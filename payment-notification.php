<?php
require_once 'config.php';

// Terima notifikasi dari Midtrans
$json = file_get_contents('php://input');
$notification = json_decode($json, true);

// Log notification untuk debugging (opsional)
file_put_contents('midtrans_notification.log', date('Y-m-d H:i:s') . " - " . $json . "\n", FILE_APPEND);

// Verifikasi signature key
$order_id = $notification['order_id'];
$status_code = $notification['status_code'];
$gross_amount = $notification['gross_amount'];
$signature_key = $notification['signature_key'];

$server_key = MIDTRANS_SERVER_KEY;
$hashed = hash('sha512', $order_id . $status_code . $gross_amount . $server_key);

if ($hashed !== $signature_key) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
    exit;
}

// Proses notifikasi berdasarkan transaction_status
$transaction_status = $notification['transaction_status'];
$fraud_status = isset($notification['fraud_status']) ? $notification['fraud_status'] : '';
$payment_type = $notification['payment_type'];

// Update transaction di database
$query = "UPDATE transactions SET 
          status = ?,
          payment_type = ?,
          transaction_time = ?,
          gross_amount = ?,
          fraud_status = ?
          WHERE order_id = ?";
$stmt = mysqli_prepare($conn, $query);

$db_status = 'pending';
$transaction_time = date('Y-m-d H:i:s');

// Tentukan status berdasarkan transaction_status
if ($transaction_status == 'capture') {
    if ($fraud_status == 'accept') {
        $db_status = 'completed';
    }
} elseif ($transaction_status == 'settlement') {
    $db_status = 'completed';
} elseif ($transaction_status == 'pending') {
    $db_status = 'pending';
} elseif ($transaction_status == 'deny' || $transaction_status == 'expire' || $transaction_status == 'cancel') {
    $db_status = 'failed';
}

mysqli_stmt_bind_param($stmt, "sssdss", $db_status, $payment_type, $transaction_time, $gross_amount, $fraud_status, $order_id);
mysqli_stmt_execute($stmt);

// Jika pembayaran berhasil, update status pembelian/subscription
if ($db_status === 'completed') {
    // Cek apakah ini pembelian buku atau subscription
    if (strpos($order_id, 'BOOK-') === 0) {
        // Update book purchase
        $update_purchase = "UPDATE book_purchases SET payment_status = 'success' WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $update_purchase);
        mysqli_stmt_bind_param($stmt, "s", $order_id);
        mysqli_stmt_execute($stmt);
        
        // Insert ke transactions jika belum ada
        $check_trans = "SELECT id FROM transactions WHERE order_id = ? AND type = 'book' AND status = 'completed'";
        $stmt = mysqli_prepare($conn, $check_trans);
        mysqli_stmt_bind_param($stmt, "s", $order_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 0) {
            // Get book purchase data
            $get_purchase = "SELECT * FROM book_purchases WHERE order_id = ?";
            $stmt = mysqli_prepare($conn, $get_purchase);
            mysqli_stmt_bind_param($stmt, "s", $order_id);
            mysqli_stmt_execute($stmt);
            $purchase_result = mysqli_stmt_get_result($stmt);
            $purchase = mysqli_fetch_assoc($purchase_result);
            
            if ($purchase) {
                $trans_query = "INSERT INTO transactions (order_id, user_id, type, reference_id, amount, status) 
                               VALUES (?, ?, 'book', ?, ?, 'completed')";
                $stmt = mysqli_prepare($conn, $trans_query);
                mysqli_stmt_bind_param($stmt, "siid", $order_id, $purchase['user_id'], $purchase['book_id'], $purchase['price']);
                mysqli_stmt_execute($stmt);
            }
        }
        
    } elseif (strpos($order_id, 'SUB-') === 0) {
        // Update subscription
        $update_sub = "UPDATE user_subscriptions SET payment_status = 'success', is_active = 1 WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $update_sub);
        mysqli_stmt_bind_param($stmt, "s", $order_id);
        mysqli_stmt_execute($stmt);
        
        // Nonaktifkan subscription lama
        $get_sub = "SELECT user_id FROM user_subscriptions WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $get_sub);
        mysqli_stmt_bind_param($stmt, "s", $order_id);
        mysqli_stmt_execute($stmt);
        $sub_result = mysqli_stmt_get_result($stmt);
        $sub_data = mysqli_fetch_assoc($sub_result);
        
        if ($sub_data) {
            $deactivate = "UPDATE user_subscriptions SET is_active = 0 
                          WHERE user_id = ? AND order_id != ? AND is_active = 1";
            $stmt = mysqli_prepare($conn, $deactivate);
            mysqli_stmt_bind_param($stmt, "is", $sub_data['user_id'], $order_id);
            mysqli_stmt_execute($stmt);
            
            // Insert ke transactions jika belum ada
            $check_trans = "SELECT id FROM transactions WHERE order_id = ? AND type = 'subscription' AND status = 'completed'";
            $stmt = mysqli_prepare($conn, $check_trans);
            mysqli_stmt_bind_param($stmt, "s", $order_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) == 0) {
                $get_sub_detail = "SELECT * FROM user_subscriptions WHERE order_id = ?";
                $stmt = mysqli_prepare($conn, $get_sub_detail);
                mysqli_stmt_bind_param($stmt, "s", $order_id);
                mysqli_stmt_execute($stmt);
                $sub_detail_result = mysqli_stmt_get_result($stmt);
                $sub_detail = mysqli_fetch_assoc($sub_detail_result);
                
                if ($sub_detail) {
                    // Get plan price
                    $plan_query = "SELECT price FROM subscription_plans WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $plan_query);
                    mysqli_stmt_bind_param($stmt, "i", $sub_detail['plan_id']);
                    mysqli_stmt_execute($stmt);
                    $plan_result = mysqli_stmt_get_result($stmt);
                    $plan = mysqli_fetch_assoc($plan_result);
                    
                    if ($plan) {
                        $trans_query = "INSERT INTO transactions (order_id, user_id, type, reference_id, amount, status) 
                                       VALUES (?, ?, 'subscription', ?, ?, 'completed')";
                        $stmt = mysqli_prepare($conn, $trans_query);
                        mysqli_stmt_bind_param($stmt, "siid", $order_id, $sub_detail['user_id'], $sub_detail['plan_id'], $plan['price']);
                        mysqli_stmt_execute($stmt);
                    }
                }
            }
        }
    }
} elseif ($db_status === 'failed') {
    // Update status ke failed
    if (strpos($order_id, 'BOOK-') === 0) {
        $update_purchase = "UPDATE book_purchases SET payment_status = 'failed' WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $update_purchase);
        mysqli_stmt_bind_param($stmt, "s", $order_id);
        mysqli_stmt_execute($stmt);
    } elseif (strpos($order_id, 'SUB-') === 0) {
        $update_sub = "UPDATE user_subscriptions SET payment_status = 'failed', is_active = 0 WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $update_sub);
        mysqli_stmt_bind_param($stmt, "s", $order_id);
        mysqli_stmt_execute($stmt);
    }
}

http_response_code(200);
echo json_encode(['status' => 'success', 'message' => 'Notification processed']);