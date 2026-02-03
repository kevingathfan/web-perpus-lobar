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
        <style>body{font-family:"Poppins",sans-serif;}
        /* 1. Latar Belakang Penuh */
    #global-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: #ffffff; /* Latar Putih Bersih */
        z-index: 99999; /* Pastikan di paling atas layer */
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.5s ease, visibility 0.5s ease;
    }

    /* 2. Container Konten */
    .loader-content {
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* 3. Spinner Modern Ganda */
    .spinner-brand {
        position: relative;
        width: 60px;
        height: 60px;
        margin-bottom: 15px;
    }

    .spinner-circle {
        position: absolute;
        width: 100%;
        height: 100%;
        border: 3px solid #e5e7eb; /* Abu-abu muda */
        border-top-color: #2c3e50; /* Navy Formal */
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .spinner-circle-inner {
        position: absolute;
        top: 15px;
        left: 15px;
        width: 30px;
        height: 30px;
        border: 3px solid #e5e7eb;
        border-bottom-color: #34495e; /* Warna Aksen */
        border-radius: 50%;
        animation: spin-reverse 1.5s linear infinite;
    }

    /* 4. Teks Loading Berdenyut */
    .loading-text {
        font-family: Segoe UI, sans-serif;
        font-size: 0.85rem;
        font-weight: 600;
        letter-spacing: 2px;
        color: #2c3e50;
        animation: pulse 1.5s ease-in-out infinite;
    }

    /* Animasi Keyframes */
    @keyframes spin { 100% { transform: rotate(360deg); } }
    @keyframes spin-reverse { 100% { transform: rotate(-360deg); } }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

    /* Kelas untuk Menyembunyikan Loader */
    .loader-hidden {
        opacity: 0;
        visibility: hidden;
    }</style>
    </head>
    <body class="d-flex align-items-center justify-content-center vh-100 bg-light">
    <div id="global-loader">
    <div class="loader-content">
        <div class="spinner-brand">
            <div class="spinner-circle"></div>
            <div class="spinner-circle-inner"></div>
        </div>
        <div class="loading-text">MEMUAT...</div>
    </div>
</div>
        <div class="text-center p-5 bg-white rounded-4 shadow-sm border" style="max-width:500px;">
            <div class="mb-4 text-danger display-1"><i class="bi bi-lock-fill"></i></div>
            <h2 class="fw-bold text-danger mb-3">Akses Ditutup</h2>
            <p class="text-muted mb-4">' . $pesanTutup . '</p>
            <a href="../index.php" class="btn btn-dark rounded-pill px-4 fw-bold">Kembali ke Beranda</a>
        </div>
        <script>
    // Hilangkan Preloader saat halaman selesai dimuat sepenuhnya
    window.addEventListener("load", function () {
        const loader = document.getElementById("global-loader");
        
        // Beri sedikit jeda agar animasi terlihat (opsional, bisa dihapus)
        setTimeout(function() {
            loader.classList.add("loader-hidden");
            
            // Hapus elemen dari DOM setelah transisi selesai agar tidak menghalangi klik
            loader.addEventListener("transitionend", function() {
                if (loader.parentNode) {
                    loader.parentNode.removeChild(loader);
                }
            });
        }, 500); // Durasi tampil minimal 0.5 detik
    });
</script>
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
    <style>body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa;} 
/* 1. Latar Belakang Penuh */
    #global-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: #ffffff; /* Latar Putih Bersih */
        z-index: 99999; /* Pastikan di paling atas layer */
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.5s ease, visibility 0.5s ease;
    }

    /* 2. Container Konten */
    .loader-content {
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* 3. Spinner Modern Ganda */
    .spinner-brand {
        position: relative;
        width: 60px;
        height: 60px;
        margin-bottom: 15px;
    }

    .spinner-circle {
        position: absolute;
        width: 100%;
        height: 100%;
        border: 3px solid #e5e7eb; /* Abu-abu muda */
        border-top-color: #2c3e50; /* Navy Formal */
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .spinner-circle-inner {
        position: absolute;
        top: 15px;
        left: 15px;
        width: 30px;
        height: 30px;
        border: 3px solid #e5e7eb;
        border-bottom-color: #34495e; /* Warna Aksen */
        border-radius: 50%;
        animation: spin-reverse 1.5s linear infinite;
    }

    /* 4. Teks Loading Berdenyut */
    .loading-text {
        font-family: 'Segoe UI', sans-serif;
        font-size: 0.85rem;
        font-weight: 600;
        letter-spacing: 2px;
        color: #2c3e50;
        animation: pulse 1.5s ease-in-out infinite;
    }

    /* Animasi Keyframes */
    @keyframes spin { 100% { transform: rotate(360deg); } }
    @keyframes spin-reverse { 100% { transform: rotate(-360deg); } }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

    /* Kelas untuk Menyembunyikan Loader */
    .loader-hidden {
        opacity: 0;
        visibility: hidden;
    }</style>
</head>
<body>
    <div id="global-loader">
    <div class="loader-content">
        <div class="spinner-brand">
            <div class="spinner-circle"></div>
            <div class="spinner-circle-inner"></div>
        </div>
        <div class="loading-text">MEMUAT...</div>
    </div>
</div>
    <div class="container py-5" style="max-width: 900px;">
        <a href="pilih_perpustakaan.php?target=iplm" class="text-decoration-none text-dark fw-bold mb-3 d-inline-block">&larr; Kembali</a>
        
        <h2 class="text-center fw-bold mb-4">FORMULIR IPLM</h2>
        
        <?php render_dynamic_form($pdo, 'IPLM', $library_id, $auto_isi); ?>
        
    </div>
    <script>
    // Hilangkan Preloader saat halaman selesai dimuat sepenuhnya
    window.addEventListener("load", function () {
        const loader = document.getElementById("global-loader");
        
        // Beri sedikit jeda agar animasi terlihat (opsional, bisa dihapus)
        setTimeout(function() {
            loader.classList.add("loader-hidden");
            
            // Hapus elemen dari DOM setelah transisi selesai agar tidak menghalangi klik
            loader.addEventListener("transitionend", function() {
                if (loader.parentNode) {
                    loader.parentNode.removeChild(loader);
                }
            });
        }, 500); // Durasi tampil minimal 0.5 detik
    });
</script>
</body>
</html>