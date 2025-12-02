<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'perpustakaan_online');

// Membuat koneksi database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset ke UTF-8
mysqli_set_charset($conn, "utf8");

// Konfigurasi Aplikasi
define('BASE_URL', 'http://localhost/perpus/');
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Konfigurasi Email (PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'skybooking04@gmail.com');
define('SMTP_PASS', 'ursd jsju rtdc vjdb');
define('SMTP_FROM', 'skybooking04@gmail.com');
define('SMTP_FROM_NAME', 'Perpustakaan Online');

// ========================================
// KONFIGURASI PAYMENT MODE
// ========================================

// PAYMENT_MODE Options:
// 'midtrans' = Gunakan Midtrans payment gateway (perlu kredensial valid)
// 'mock'     = Simulasi pembayaran (untuk testing/demo)
define('PAYMENT_MODE', 'midtrans');

// ========================================
// KONFIGURASI MIDTRANS
// ========================================

// PENTING: Copy PERSIS dari dashboard, JANGAN tambah prefix "SB-"
define('MIDTRANS_SERVER_KEY', '');
define('MIDTRANS_CLIENT_KEY', '');
define('MIDTRANS_IS_PRODUCTION', false);
define('MIDTRANS_SNAP_URL', MIDTRANS_IS_PRODUCTION ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js');
define('MIDTRANS_API_URL', MIDTRANS_IS_PRODUCTION ? 'https://api.midtrans.com/v2' : 'https://api.sandbox.midtrans.com/v2');

// Konfigurasi Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include file functions
require_once __DIR__ . '/functions.php';