<?php
// Override config for this script
$host = '127.0.0.1';
$port = '3306'; 
$dbname = 'monitoring_perpus_db'; 
$username = 'root'; 
$password = ''; 

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " | Type: " . $col['Type'] . " | Null: " . $col['Null'] . " | Key: " . $col['Key'] . " | Default: " . $col['Default'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
