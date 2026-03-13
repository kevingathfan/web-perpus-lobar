<?php
// web-perpus-v1/pustakawan/beranda.php
session_start();
require '../config/database.php';

// 1. Ambil ID Perpustakaan dari URL (jika ada), lalu simpan ke session
if (!empty($_GET['library_id'])) {
    $_SESSION['pustakawan_ctx']['library_id'] = $_GET['library_id'];
    header("Location: beranda.php");
    exit;
}

$library_id = $_SESSION['pustakawan_ctx']['library_id'] ?? '';

// Jika tidak ada ID, kembalikan ke halaman pilih
if (!$library_id) {
    header("Location: pilih_perpustakaan.php");
    exit;
}

// 2. Ambil Data Perpustakaan (untuk ditampilkan jika perlu)
$stmt = $pdo->prepare("SELECT nama FROM libraries WHERE id = ?");
$stmt->execute([$library_id]);
$library = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Dinas Kearsipan dan Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/loader.css">
    <link rel="stylesheet" href="../assets/public-responsive.css">

    <style>
        body {
            background-color: #ffffff;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
        }

        /* 1. HEADER BOX */
        .header-box {
            width: 100%;
            max-width: 1000px;
            border: 1px solid #000;
            border-radius: 20px; /* Sudut melengkung */
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 50px;
            background: #fff;
        }

        .header-text {
            text-align: center;
            flex-grow: 1;
        }

        .header-text h2 {
            font-size: 28px;
            font-weight: 600;
            margin: 0;
            color: #000;
        }
        
        .header-text p {
            margin: 0;
            font-size: 16px;
            color: #1e293b;
            /* Darker for better readability */
            font-weight: 500;
        }

        .logo-img {
            height: 80px;
            width: auto;
            object-fit: contain;
            /* Prevent distortion */
            flex-shrink: 0;
        }

        /* 2. TITLE BOX */
        .title-box {
            background: #fff;
            width: 100%;
            max-width: 900px;
            padding: 20px 40px;
            text-align: center;
            border: 1px solid #ddd;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.15);
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .title-box h4 {
            font-size: 20px;
            line-height: 1.6;
            /* Better line spacing */
            margin: 0;
            font-weight: 600;
            /* Bolder for better readability */
            color: #0f172a;
            /* Darker text */
        }

        /* 3. INSTRUCTION TEXT */
        .instruction-text {
            font-size: 18px;
            margin-bottom: 30px;
            color: #1e293b;
            /* Darker for better readability */
            font-weight: 600;
        }

        /* 4. SELECTION CARDS (BUTTONS) */
        .card-menu {
            background: #fff;
            border: 2px solid #0f172a;
            /* Thicker border for better visibility */
            border-radius: 15px;
            padding: 40px 20px;
            text-align: center;
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
            text-decoration: none;
            color: #0f172a;
            box-shadow: 0px 4px 6px rgba(0,0,0,0.1);
        }

        .card-menu:hover {
            transform: translateY(-5px);
            box-shadow: 0px 8px 15px rgba(0,0,0,0.2);
            border-color: #0F52BA;
            /* Primary color on hover */
            color: #0F52BA;
            background: #eff6ff;
            /* Light blue background on hover */
        }

        .card-menu h3 {
            font-size: 22px;
            font-weight: 600;
            /* Bolder for better readability */
            line-height: 1.5;
            /* Better line spacing */
        }

        /* 5. FOOTER INFO */
        .footer-info {
            width: 100%;
            max-width: 1000px;
            border: 1px solid #000;
            border-radius: 8px;
            padding: 15px 20px;
            margin-top: 50px;
            min-height: 100px; /* Sesuai gambar yang kotak kosong panjang */
        }
        
        .footer-info h6 {
            font-weight: 600;
            margin-bottom: 10px;
        }

    </style>
</head>
<body>
    <?php include __DIR__ . '/../config/loader.php'; ?>

    <div class="header-box">
        <img src="../assets/logo_lobar.png" alt="Logo Lobar" class="logo-img">
        
        <div class="header-text">
            <h2>Dinas Kearsipan dan Perpustakaan</h2>
            <p>Kabupaten Lombok Barat</p>
        </div>

        <img src="../assets/logo_disarpus.png" alt="Logo Disarpus" class="logo-img">
    </div>

    <div class="title-box">
        <h4>Kuisioner Pengukuran Indeks Pembangunan Literasi Masyarakat dan Kegemaran Membaca Kabupaten Lombok Barat</h4>
    </div>

    <div class="instruction-text">Pilih Kategori Kuisioner</div>

    <div class="container" style="max-width: 900px;">
        <div class="row g-4">
            <div class="col-md-6">
                <a href="kuisioner_iplm.php" class="card-menu">
                    <h3>Kuisioner Pengukuran<br>Indeks Pembangunan<br>Literasi Masyarakat<br>(IPLM)</h3>
                </a>
            </div>

            <div class="col-md-6">
                <a href="kuisioner_tkm.php" class="card-menu">
                    <h3>Kuisioner<br>Tingkat Kegemaran<br>Membaca (TKM)</h3>
                </a>
            </div>
        </div>
    </div>

    <div class="footer-info">
        <h6>Mengapa kuisioner ini penting ?</h6>
        <p class="text-muted small">
            Data yang Anda isikan sangat berharga untuk mengukur kemajuan literasi di daerah kita. 
            Hasil survei ini akan digunakan sebagai dasar pengambilan kebijakan pengembangan perpustakaan di masa depan.
        </p>
    </div>

    <div class="mt-4">
        <a href="dashboard.php" class="text-decoration-none text-muted small">
            &larr; Kembali ke Dashboard Statistik
        </a>
    </div>

    <script src="../assets/loader.js"></script>
</body>
</html>
