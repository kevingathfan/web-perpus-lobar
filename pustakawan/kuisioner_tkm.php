<?php
// web-perpus-v1/pustakawan/kuisioner_tkm.php
require '../config/database.php';

// --- CEK STATUS & JADWAL ---
$stmtSet = $pdo->query("SELECT * FROM settings");
$settings = $stmtSet->fetchAll(PDO::FETCH_KEY_PAIR);

$mode = $settings['tkm_mode'] ?? 'manual';
$isOpen = false;
$pesanTutup = "Perioder Pengisian Kuisioner Belum Di buka";

if ($mode == 'manual') {
    if (($settings['status_tkm'] ?? 'buka') == 'buka') {
        $isOpen = true;
    }
} else {
    $now = date('Y-m-d H:i:s');
    $start = $settings['tkm_start'] ?? '';
    $end = $settings['tkm_end'] ?? '';

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

// JIKA DITUTUP
if (!$isOpen) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Akses Ditutup</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../assets/loader.css">
        <style>body{font-family:"Poppins",sans-serif;}</style>
    </head>
    <body class="d-flex align-items-center justify-content-center vh-100 bg-light">
        <?php include __DIR__ . '/../config/loader.php'; ?>
        <div class="text-center p-5 bg-white rounded-4 shadow-sm border" style="max-width:500px;">
            <div class="mb-4 text-danger display-1"><i class="bi bi-lock-fill"></i></div>
            <h2 class="fw-bold text-danger mb-3">Akses Ditutup</h2>
            <p class="text-muted mb-4"><?= $pesanTutup ?></p>
            <a href="../index.php" class="btn btn-dark rounded-pill px-4 fw-bold">Kembali ke Beranda</a>
        </div>
        <script src="../assets/loader.js"></script>
    </body>
    </html>
    <?php
    exit;
}

// JIKA DIBUKA
require 'render_kuesioner.php';

// (TKM biasanya tidak butuh data perpustakaan spesifik, jadi null)
$library_id = isset($_GET['library_id']) ? $_GET['library_id'] : '';

// Data Auto Fill untuk TKM (Biasanya kosong/bebas)
$auto_isi = []; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Kuesioner TKM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/loader.css">
    <link rel="stylesheet" href="../assets/public-responsive.css">
    <style>body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }</style>
</head>
<body>
    <?php include __DIR__ . '/../config/loader.php'; ?>
    <div class="container py-5" style="max-width: 900px;">
        <a href="../index.php" class="text-decoration-none text-dark fw-bold mb-3 d-inline-block">&larr; Kembali</a>
        
        <h2 class="text-center fw-bold mb-4">FORMULIR TKM</h2>
        
        <?php render_dynamic_form($pdo, 'TKM', $library_id, $auto_isi); ?>
        
    </div>
    <script src="../assets/loader.js"></script>
</body>
</html>
