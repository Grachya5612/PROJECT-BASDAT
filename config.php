<?php
// Konfigurasi Database
$host = "localhost";
$user = "root";
$pass = "@yuma2005";
$db = "db_inventory_penjualan";

// Koneksi ke database
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset UTF-8 untuk support emoji
mysqli_set_charset($conn, "utf8mb4");

// Timezone
date_default_timezone_set('Asia/Jakarta');
?>