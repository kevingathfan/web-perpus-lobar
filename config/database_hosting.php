<?php
// config/database_hosting.php (Gunakan ini saat di-hosting)

$host = 'sql113.infinityfree.com';
$port = '3306'; 
$dbname = 'if0_41117668_monitoring_perpus_db'; 
$username = 'if0_41117668'; 
$password = 'sandiKuis112'; 

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>
