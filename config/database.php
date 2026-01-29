<?php
// config/database.php

$host = 'localhost';
$port = '5432'; // Port default PostgreSQL
$dbname = 'monitoring_perpus_db'; // Pastikan database ini sudah dibuat di pgAdmin/Postgres
$username = 'postgres'; // Username default Postgres
$password = '@kevin14'; // Masukkan password user postgres Anda di sini

try {
    // Perhatikan string "pgsql:" di awal
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
    $pdo = new PDO($dsn, $username, $password);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // echo "Koneksi PostgreSQL Berhasil!"; // Uncomment untuk tes
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>