<?php
// web-perpus-v1/pustakawan/kuisioner_iplm.php
require '../config/database.php';

// --- CEK STATUS & JADWAL ---
$stmtSet = $pdo->query("SELECT * FROM settings");
$settings = $stmtSet->fetchAll(PDO::FETCH_KEY_PAIR);

$mode = $settings['iplm_mode'] ?? 'manual';
$isOpen = false;
$pesanTutup = "Periode Pengisian Kuisioner Belum Di buka";

if ($mode == 'manual') {
    // Mode Manual: Cek status_iplm
    if (($settings['status_iplm'] ?? 'buka') == 'buka') {
        $isOpen = true;
    }
} else {
    // Mode Auto: Cek Tanggal
    $now = date('Y-m-d H:i:s');
    $start = $settings['iplm_start'] ?? '';
    $end = $settings['iplm_end'] ?? '';

    if ($start && $end) {
        if ($now >= $start && $now <= $end) {
            $isOpen = true;
        } elseif ($now < $start) {
            $pesanTutup = "Kuesioner belum dibuka.<br>Jadwal Buka: <strong>" . date('d M Y H:i', strtotime($start)) . "</strong>";
        } else {
            $pesanTutup = "Kuesioner sudah ditutup.<br>Batas Akhir: <strong>" . date('d M Y H:i', strtotime($end)) . "</strong>";
        }
    } else {
        $pesanTutup = "Jadwal pengisian belum diatur oleh admin.";
    }
}

// JIKA DITUTUP, TAMPILKAN LAYAR BLOKIR
if (!$isOpen) {
    die('
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Akses Ditutup</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
        <style>body{font-family:"Poppins",sans-serif;}</style>
    </head>
    <body class="d-flex align-items-center justify-content-center vh-100 bg-light">
        <div class="text-center p-5 bg-white rounded-4 shadow-sm border" style="max-width:500px;">
            <div class="mb-4 text-danger display-1"><i class="bi bi-lock-fill"></i></div>
            <h2 class="fw-bold text-danger mb-3">Akses Ditutup</h2>
            <p class="text-muted mb-4">' . $pesanTutup . '</p>
            <a href="../index.php" class="btn btn-dark rounded-pill px-4 fw-bold">Kembali ke Beranda</a>
        </div>
    </body>
    </html>
    ');
}

// JIKA DIBUKA, LANJUT KE LOGIKA FORM
require 'render_kuesioner.php'; 

// 1. Tangkap Data dari URL (Pilih Perpustakaan)
$library_id = isset($_GET['library_id']) ? $_GET['library_id'] : '';
$kat_utama  = isset($_GET['kategori_utama']) ? $_GET['kategori_utama'] : '';
$kat_sub    = isset($_GET['kategori_sub']) ? $_GET['kategori_sub'] : '';
$nama_text  = isset($_GET['nama_perpus_text']) ? $_GET['nama_perpus_text'] : '';

// 2. Format Data untuk Auto-Fill (Menggunakan Kode Kunci)
$auto_isi = [
    'core_jenis'    => "Perpustakaan " . $kat_utama,
    'core_subjenis' => $kat_sub,
    'core_nama'     => $nama_text
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Kuesioner IPLM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>
    <div class="container py-5" style="max-width: 900px;">
        <a href="pilih_perpustakaan.php?target=iplm" class="text-decoration-none text-dark fw-bold mb-3 d-inline-block">&larr; Kembali</a>
        
        <h2 class="text-center fw-bold mb-4">FORMULIR IPLM</h2>
        
        <?php render_dynamic_form($pdo, 'IPLM', $library_id, $auto_isi); ?>
        
    </div>
</body>
</html>