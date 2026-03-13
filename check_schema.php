<?php
require 'config/database.php';

try {
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
