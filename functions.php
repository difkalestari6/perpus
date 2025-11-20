<?php
// functions.php - Helper Functions untuk Perpustakaan Online

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

/**
 * ========================================
 * FUNGSI HELPER UMUM
 * ========================================
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Format angka ke Rupiah
 */
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Get User ID dari session
 */
function getUserId() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Cek apakah user sudah login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Cek apakah user adalah admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirect helper
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * ========================================
 * FUNGSI UNTUK BUKU DAN PEMBELIAN
 * ========================================
 */

/**
 * Cek apakah user bisa membaca buku
 */
function canReadBook($conn, $user_id, $book) {
    // Jika buku gratis, semua orang bisa baca
    if ($book['is_free'] == 1) {
        return true;
    }
    
    // Jika user tidak login, tidak bisa baca buku berbayar
    if (!$user_id) {
        return false;
    }
    
    // Cek apakah user punya subscription aktif
    if (hasActiveSubscription($conn, $user_id)) {
        return true;
    }
    
    // Cek apakah user sudah membeli buku ini
    return hasPurchasedBook($conn, $user_id, $book['id']);
}

/**
 * Cek apakah user sudah membeli buku tertentu
 */
function hasPurchasedBook($conn, $user_id, $book_id) {
    $query = "SELECT id FROM book_purchases 
              WHERE user_id = ? AND book_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $book_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_num_rows($result) > 0;
}

/**
 * Cek apakah user punya subscription aktif
 */
function hasActiveSubscription($conn, $user_id) {
    $query = "SELECT id FROM user_subscriptions 
              WHERE user_id = ? 
              AND is_active = 1 
              AND end_date >= NOW()
              LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_num_rows($result) > 0;
}

/**
 * Get subscription info user
 */
function getUserSubscription($conn, $user_id) {
    $query = "SELECT us.*, sp.name as plan_name, sp.duration_days 
              FROM user_subscriptions us
              JOIN subscription_plans sp ON us.plan_id = sp.id
              WHERE us.user_id = ? 
              AND us.is_active = 1 
              AND us.end_date >= NOW()
              ORDER BY us.end_date DESC
              LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

/**
 * Get daftar buku yang dibeli user
 */
function getUserPurchasedBooks($conn, $user_id) {
    $query = "SELECT b.*, bp.purchased_at, bp.price as paid_price
              FROM book_purchases bp
              JOIN books b ON bp.book_id = b.id
              WHERE bp.user_id = ?
              ORDER BY bp.purchased_at DESC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $books = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $books[] = $row;
    }
    
    return $books;
}

/**
 * ========================================
 * FUNGSI TRANSAKSI
 * ========================================
 */

/**
 * Proses pembelian buku
 */
function purchaseBook($conn, $user_id, $book_id, $amount) {
    mysqli_begin_transaction($conn);
    
    try {
        // Cek apakah sudah pernah beli
        if (hasPurchasedBook($conn, $user_id, $book_id)) {
            throw new Exception("Anda sudah membeli buku ini");
        }
        
        // Insert ke book_purchases
        $query = "INSERT INTO book_purchases (user_id, book_id, price) 
                  VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iid", $user_id, $book_id, $amount);
        mysqli_stmt_execute($stmt);
        
        $purchase_id = mysqli_insert_id($conn);
        
        // Insert ke transactions
        $query = "INSERT INTO transactions (user_id, type, reference_id, amount, status) 
                  VALUES (?, 'book', ?, ?, 'completed')";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iid", $user_id, $purchase_id, $amount);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        return array('success' => true, 'message' => 'Buku berhasil dibeli');
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return array('success' => false, 'message' => $e->getMessage());
    }
}

/**
 * Proses pembelian subscription
 */
function purchaseSubscription($conn, $user_id, $plan_id, $amount) {
    mysqli_begin_transaction($conn);
    
    try {
        // Get plan details
        $query = "SELECT * FROM subscription_plans WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $plan_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $plan = mysqli_fetch_assoc($result);
        
        if (!$plan) {
            throw new Exception("Paket subscription tidak ditemukan");
        }
        
        // Hitung tanggal mulai dan berakhir
        $start_date = date('Y-m-d H:i:s');
        $end_date = date('Y-m-d H:i:s', strtotime("+{$plan['duration_days']} days"));
        
        // Nonaktifkan subscription lama jika ada
        $query = "UPDATE user_subscriptions SET is_active = 0 WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        // Insert subscription baru
        $query = "INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, is_active) 
                  VALUES (?, ?, ?, ?, 1)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iiss", $user_id, $plan_id, $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        
        $subscription_id = mysqli_insert_id($conn);
        
        // Insert ke transactions
        $query = "INSERT INTO transactions (user_id, type, reference_id, amount, status) 
                  VALUES (?, 'subscription', ?, ?, 'completed')";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iid", $user_id, $subscription_id, $amount);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        return array('success' => true, 'message' => 'Subscription berhasil dibeli');
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return array('success' => false, 'message' => $e->getMessage());
    }
}

/**
 * ========================================
 * FUNGSI EMAIL
 * ========================================
 */

/**
 * Kirim Email menggunakan PHPMailer
 */
function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        $mail->send();
        return array('success' => true, 'message' => 'Email berhasil dikirim');
    } catch (Exception $e) {
        return array('success' => false, 'message' => "Email gagal dikirim: {$mail->ErrorInfo}");
    }
}

/**
 * Kirim Email Verifikasi
 */
function sendVerificationEmail($conn, $user_id, $email, $username) {
    // Generate token
    $token = bin2hex(random_bytes(32));
    
    // Simpan token ke database
    $query = "UPDATE users SET verification_token = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $token, $user_id);
    mysqli_stmt_execute($stmt);
    
    // Buat link verifikasi
    $verification_link = BASE_URL . "verify-email.php?token=" . $token;
    
    // Template email
    $subject = "Verifikasi Email - Perpustakaan Online";
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .button { display: inline-block; padding: 12px 30px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Selamat Datang di Perpustakaan Online</h2>
            </div>
            <div class='content'>
                <p>Halo <strong>{$username}</strong>,</p>
                <p>Terima kasih telah mendaftar di Perpustakaan Online. Untuk mengaktifkan akun Anda, silakan klik tombol verifikasi di bawah ini:</p>
                <p style='text-align: center;'>
                    <a href='{$verification_link}' class='button'>Verifikasi Email</a>
                </p>
                <p>Atau salin link berikut ke browser Anda:</p>
                <p style='word-break: break-all; color: #666;'>{$verification_link}</p>
                <p>Link verifikasi ini berlaku selama 24 jam.</p>
                <p>Jika Anda tidak mendaftar di situs kami, abaikan email ini.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2024 Perpustakaan Online. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}

/**
 * Kirim Email Reset Password
 */
function sendResetPasswordEmail($conn, $email) {
    // Cek apakah email terdaftar
    $query = "SELECT id, username FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        return array('success' => false, 'message' => 'Email tidak terdaftar');
    }
    
    $user = mysqli_fetch_assoc($result);
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    $expire = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Simpan token ke database
    $query = "UPDATE users SET reset_token = ?, reset_token_expire = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssi", $token, $expire, $user['id']);
    mysqli_stmt_execute($stmt);
    
    // Buat link reset
    $reset_link = BASE_URL . "reset-password.php?token=" . $token;
    
    // Template email
    $subject = "Reset Password - Perpustakaan Online";
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #f44336; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .button { display: inline-block; padding: 12px 30px; background: #f44336; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Reset Password</h2>
            </div>
            <div class='content'>
                <p>Halo <strong>{$user['username']}</strong>,</p>
                <p>Kami menerima permintaan untuk mereset password akun Anda. Klik tombol di bawah ini untuk mereset password:</p>
                <p style='text-align: center;'>
                    <a href='{$reset_link}' class='button'>Reset Password</a>
                </p>
                <p>Atau salin link berikut ke browser Anda:</p>
                <p style='word-break: break-all; color: #666;'>{$reset_link}</p>
                <p>Link ini berlaku selama 1 jam.</p>
                <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2024 Perpustakaan Online. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}

/**
 * ========================================
 * FUNGSI UTILITAS LAINNYA
 * ========================================
 */

/**
 * Format tanggal Indonesia
 */
function formatTanggal($date) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    
    $timestamp = strtotime($date);
    $tanggal = date('d', $timestamp);
    $bulan_num = date('n', $timestamp);
    $tahun = date('Y', $timestamp);
    
    return $tanggal . ' ' . $bulan[$bulan_num] . ' ' . $tahun;
}

/**
 * Generate excerpt dari text
 */
function excerpt($text, $limit = 150) {
    if (strlen($text) <= $limit) {
        return $text;
    }
    
    $text = substr($text, 0, $limit);
    $text = substr($text, 0, strrpos($text, ' '));
    
    return $text . '...';
}

/**
 * Upload file image
 */
function uploadImage($file, $target_dir = 'uploads/') {
    // Validasi file
    $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_types)) {
        return array('success' => false, 'message' => 'Hanya file JPG, JPEG, PNG & GIF yang diperbolehkan');
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5000000) {
        return array('success' => false, 'message' => 'Ukuran file terlalu besar (max 5MB)');
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
    $target_file = $target_dir . $new_filename;
    
    // Create directory if not exists
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return array('success' => true, 'filename' => $new_filename);
    } else {
        return array('success' => false, 'message' => 'Gagal mengupload file');
    }
}