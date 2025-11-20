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
define('SMTP_HOST', 'smtp.gmail.com');        // Host SMTP
define('SMTP_PORT', 587);                      // Port SMTP
define('SMTP_USER', 'skybooking04@gmail.com');   // Email Anda - GANTI INI
define('SMTP_PASS', 'mqhn zyii witx khdw');      // App Password Gmail - GANTI INI
define('SMTP_FROM', 'lestaridifka@gmail.com');   // Email pengirim - GANTI INI
define('SMTP_FROM_NAME', 'Perpustakaan Online'); // Nama pengirim

// Konfigurasi Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include file functions - SEMUA fungsi helper ada di sini
require_once __DIR__ . '/functions.php';