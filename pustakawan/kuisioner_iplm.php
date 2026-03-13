<?php
// web-perpus-v1/pustakawan/kuisioner_iplm.php
session_start();
require '../config/database.php';

// --- CEK STATUS & JADWAL ---
date_default_timezone_set('Asia/Makassar');
$stmtSet = $pdo->query("SELECT * FROM settings");
$settings = $stmtSet->fetchAll(PDO::FETCH_KEY_PAIR);

$mode = $settings['iplm_mode'] ?? 'manual';
$isOpen = false;
$pesanTutup = "Periode Pengisian Kuisioner Belum Dibuka";

if ($mode == 'manual') {
    // Mode Manual: Cek status_iplm
    if (($settings['status_iplm'] ?? 'buka') == 'buka') {
        $isOpen = true;
    }
} else {
    // Mode Auto: Cek Tanggal
    $now = time();
    $start = str_replace('T', ' ', $settings['iplm_start'] ?? '');
    $end = str_replace('T', ' ', $settings['iplm_end'] ?? '');

    if ($start && $end) {
        $startTs = strtotime($start);
        $endTs = strtotime($end);
        if ($startTs && $endTs && $now >= $startTs && $now <= $endTs) {
            $isOpen = true;
        } elseif ($startTs && $now < $startTs) {
            $pesanTutup = "Kuesioner belum dibuka.<br>Jadwal Buka: <strong>" . date('d M Y H:i', $startTs) . "</strong>";
        } else {
            $pesanTutup = "Kuesioner sudah ditutup.<br>Batas Akhir: <strong>" . date('d M Y H:i', $endTs ?: strtotime($end)) . "</strong>";
        }
    } else {
        $pesanTutup = "Jadwal pengisian belum diatur oleh admin.";
    }
}

// JIKA DITUTUP, TAMPILKAN LAYAR BLOKIR
if (!$isOpen) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Akses Ditutup - DISARPUS</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <link rel="stylesheet" href="../assets/govtech.css">
        <link rel="stylesheet" href="../assets/loader.css">
        <style>
            body { 
                font-family: 'Plus Jakarta Sans', sans-serif; 
                background-color: #f8fafc;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                overflow: hidden;
            }
            .bg-pattern {
                position: fixed;
                top: 0; left: 0; width: 100%; height: 100%;
                z-index: -1;
                background-image: 
                    radial-gradient(circle at 10% 20%, rgba(15, 82, 186, 0.04) 0%, transparent 40%),
                    radial-gradient(circle at 90% 80%, rgba(244, 196, 48, 0.04) 0%, transparent 40%),
                    linear-gradient(#e2e8f0 1px, transparent 1px),
                    linear-gradient(90deg, #e2e8f0 1px, transparent 1px);
                background-size: 100% 100%, 100% 100%, 40px 40px, 40px 40px;
                mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
            }
            .main-card {
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border: 1px solid #e2e8f0;
                border-radius: 24px;
                padding: 50px 40px;
                max-width: 500px;
                width: 100%;
                text-align: center;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
            }
            .icon-box {
                width: 80px;
                height: 80px;
                background: #fff5f5;
                color: #e3342f;
                border-radius: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2.5rem;
                margin: 0 auto 25px;
                border: 1px solid #fee2e2;
            }
            .btn-gov {
                background: #0f172a;
                color: white;
                padding: 12px 30px;
                border-radius: 50px;
                font-weight: 700;
                text-decoration: none;
                transition: 0.3s;
                display: inline-flex;
                align-items: center;
                gap: 10px;
            }
            .btn-gov:hover {
                background: #000;
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                color: white;
            }
        </style>
    </head>
    <body>
        <?php include __DIR__ . '/../config/loader.php'; ?>
        <div class="bg-pattern"></div>
        <div class="main-card">
            <div class="icon-box">
                <i class="bi bi-lock-fill"></i>
            </div>
            <h2 class="fw-bold text-dark mb-2">Akses Ditutup</h2>
            <p class="text-muted mb-4 fs-6"><?= $pesanTutup ?></p>
            <a href="../index.php" class="btn-gov">
                <i class="bi bi-arrow-left"></i> Kembali ke Beranda
            </a>
        </div>
        <script src="../assets/loader.js"></script>
    </body>
    </html>
    <?php
    exit;
}

// JIKA DIBUKA, LANJUT KE LOGIKA FORM
require 'render_kuesioner.php'; 

// 1. Tangkap Data (POST/GET -> session, lalu bersihkan URL)
if (!empty($_POST) || !empty($_GET)) {
    $incoming = [
        'library_id' => $_POST['library_id'] ?? ($_GET['library_id'] ?? null),
        'kategori_utama' => $_POST['kategori_utama'] ?? ($_GET['kategori_utama'] ?? null),
        'kategori_sub' => $_POST['kategori_sub'] ?? ($_GET['kategori_sub'] ?? null),
        'nama_perpus_text' => $_POST['nama_perpus_text'] ?? ($_GET['nama_perpus_text'] ?? null),
    ];
    $hasAny = false;
    foreach ($incoming as $k => $v) {
        if ($v !== null && $v !== '') {
            $_SESSION['pustakawan_ctx'][$k] = $v;
            $hasAny = true;
        }
    }
    if ($hasAny || !empty($_GET)) {
        header("Location: kuisioner_iplm.php");
        exit;
    }
}

$library_id = $_SESSION['pustakawan_ctx']['library_id'] ?? '';
$kat_utama  = $_SESSION['pustakawan_ctx']['kategori_utama'] ?? '';
$kat_sub    = $_SESSION['pustakawan_ctx']['kategori_sub'] ?? '';
$nama_text  = $_SESSION['pustakawan_ctx']['nama_perpus_text'] ?? '';

if (!$library_id) {
    header("Location: pilih_perpustakaan.php");
    exit;
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/loader.css">
    <link rel="stylesheet" href="../assets/public-responsive.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-body, #f8fafc); position: relative; overflow-x: hidden; min-height: 100vh; }
        
        /* Animated Background Pattern consistent with index */
        .bg-pattern {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(15, 82, 186, 0.04) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(244, 196, 48, 0.04) 0%, transparent 40%),
                linear-gradient(#e2e8f0 1px, transparent 1px),
                linear-gradient(90deg, #e2e8f0 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 40px 40px, 40px 40px;
            background-position: 0 0, 0 0, 0 0, 0 0;
            mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../config/loader.php'; ?>
    <div class="bg-pattern"></div>
    <div class="container py-5" style="max-width: 900px;">
        <a href="pilih_perpustakaan.php" class="text-decoration-none text-dark fw-bold mb-3 d-inline-block">&larr; Kembali</a>
        
        <h2 class="text-center fw-bold mb-4">FORMULIR IPLM</h2>
        
        <?php render_dynamic_form($pdo, 'IPLM', $library_id, $auto_isi); ?>
        
    </div>
    <script src="../assets/loader.js"></script>
</body>
</html>
