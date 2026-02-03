<?php
// web-perpus-v1/pustakawan/proses_pengaduan.php
require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = !empty($_POST['nama']) ? $_POST['nama'] : 'Anonim';
    $kontak = !empty($_POST['kontak']) ? $_POST['kontak'] : '-';
    $pesan = $_POST['pesan'];

    try {
        $sql = "INSERT INTO pengaduan (nama, kontak, pesan, tanggal) VALUES (?, ?, ?, CURRENT_DATE)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $kontak, $pesan]);

        echo "<script>
            alert('Terima kasih! Laporan/Saran Anda telah kami terima.'); 
            window.location='../index.php';
        </script>";

    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}
?>