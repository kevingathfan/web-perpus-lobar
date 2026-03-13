<?php
// Debug script untuk cek urutan pertanyaan di database
session_start();
require '../config/database.php';
require '../config/admin_auth.php';

echo "<h1>DEBUG: Urutan Pertanyaan di Database</h1>";

$jenis = $_GET['jenis'] ?? 'IPLM';
$stmt = $pdo->prepare("SELECT id, jenis_kuesioner, kategori_bagian, urutan, teks_pertanyaan FROM master_pertanyaan WHERE jenis_kuesioner = ? ORDER BY kategori_bagian ASC, urutan ASC, id ASC");
$stmt->execute([$jenis]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p><strong>Jenis Kuesioner:</strong> " . htmlspecialchars($jenis) . "</p>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>No Global</th><th>ID</th><th>Kategori Bagian</th><th>Urutan</th><th>Soal</th></tr>";

$nomor = 1;
foreach ($data as $row) {
    echo "<tr>";
    echo "<td><strong>" . $nomor . "</strong></td>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['kategori_bagian']) . "</td>";
    echo "<td>" . $row['urutan'] . "</td>";
    echo "<td>" . htmlspecialchars(substr($row['teks_pertanyaan'], 0, 50)) . "...</td>";
    echo "</tr>";
    $nomor++;
}

echo "</table>";
echo "<hr>";
echo "<p><a href='debug_pertanyaan.php?jenis=IPLM'>View IPLM</a> | <a href='debug_pertanyaan.php?jenis=TKM'>View TKM</a></p>";
?>
