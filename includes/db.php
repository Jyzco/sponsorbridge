<?php
// includes/db.php - Koneksi Database MySQL menggunakan MySQLi
// Sesuai Standar BINUS University: PHP Native & MySQLi

// Cegah re-definisi koneksi jika file di-include berulang kali
if (!isset($conn) || !$conn instanceof mysqli) {
    $db_host = '127.0.0.1';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'sponsorbridge_db';

    // Inisialisasi koneksi MySQLi
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        die("Koneksi Database Gagal: " . $conn->connect_error . ". Pastikan MySQL berjalan di XAMPP.");
    }

    // Set karakter encoding ke utf8mb4
    $conn->set_charset("utf8mb4");
}

// Konfigurasi URL dasar aplikasi (mendukung via Apache XAMPP maupun PHP Built-in Server)
if (!defined('BASE_URL')) {
    $script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    if (strpos($script_name, '/sponsorbridge/') !== false) {
        define('BASE_URL', '/sponsorbridge');
    } else {
        define('BASE_URL', '');
    }
}
