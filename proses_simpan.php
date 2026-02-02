<?php
// pustakawan/proses_simpan.php
require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $library_id = $_POST['library_id'];
    $jenis = $_POST['jenis_kuesioner'];
    $jawaban = $_POST['jawaban']; // Array [id_soal => isi_jawaban]

    try {
        $pdo->beginTransaction();

        // 1. Simpan Header (Satu kali per pengisian)
        $stmtHeader = $pdo->prepare("INSERT INTO trans_header (library_id, jenis_kuesioner, periode_bulan, periode_tahun) VALUES (?, ?, ?, ?) RETURNING id");
        // Catatan: Jika pakai MySQL ganti RETURNING id jadi $pdo->lastInsertId()
        $stmtHeader->execute([$library_id, $jenis, date('m'), date('Y')]);
        $header_id = $stmtHeader->fetchColumn(); 

        // 2. Simpan Detail (Looping jawaban)
        $stmtDetail = $pdo->prepare("INSERT INTO trans_detail (header_id, pertanyaan_id, jawaban) VALUES (?, ?, ?)");

        foreach ($jawaban as $soal_id => $isi) {
            $stmtDetail->execute([$header_id, $soal_id, $isi]);
        }

        $pdo->commit();
        echo "<script>alert('Terima kasih! Data $jenis berhasil disimpan.'); window.location='../index.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}
?>