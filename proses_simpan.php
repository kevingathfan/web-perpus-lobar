<?php
// web-perpus-v1/proses_simpan.php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // [PERBAIKAN] Cek apakah library_id kosong? Jika ya, ubah jadi NULL
    $library_id = !empty($_POST['library_id']) ? $_POST['library_id'] : null;
    
    $jenis = $_POST['jenis_kuesioner'];
    $jawaban = $_POST['jawaban']; // Array [id_soal => isi_jawaban]

    try {
        $pdo->beginTransaction();

        // 1. Simpan Header
        $stmtHeader = $pdo->prepare("INSERT INTO trans_header (library_id, jenis_kuesioner, periode_bulan, periode_tahun) VALUES (?, ?, ?, ?) RETURNING id");
        
        // Eksekusi dengan $library_id yang sudah divalidasi (bisa angka atau NULL)
        $stmtHeader->execute([$library_id, $jenis, date('m'), date('Y')]);
        
        $header_id = $stmtHeader->fetchColumn(); 

        // 2. Simpan Detail
        $stmtDetail = $pdo->prepare("INSERT INTO trans_detail (header_id, pertanyaan_id, jawaban) VALUES (?, ?, ?)");

        foreach ($jawaban as $soal_id => $isi) {
            $stmtDetail->execute([$header_id, $soal_id, $isi]);
        }

        $pdo->commit();
        
        // Redirect kembali ke index dengan pesan sukses
        echo "<script>
            alert('Terima kasih! Data $jenis berhasil disimpan.'); 
            window.location='index.php';
        </script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        // Tampilkan error jika masih ada masalah lain
        die("Error System: " . $e->getMessage());
    }
}
?>