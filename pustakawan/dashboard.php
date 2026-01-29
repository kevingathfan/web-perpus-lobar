<?php
// web-perpus-v1/pustakawan/dashboard.php
require '../config/database.php';

// 1. Ambil ID Perpustakaan
$library_id = isset($_GET['library_id']) ? $_GET['library_id'] : '';

// Jika tidak ada ID, kembalikan ke halaman pilih
if (!$library_id) {
    header("Location: pilih_perpustakaan.php");
    exit;
}

// 2. Ambil Data Perpustakaan untuk ditampilkan (Opsional, untuk header)
$stmt = $pdo->prepare("SELECT nama FROM libraries WHERE id = ?");
$stmt->execute([$library_id]);
$library = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($library['nama'] ?? 'Perpustakaan') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #ffffff; font-family: sans-serif; overflow-x: hidden; }
        
        /* SIDEBAR STYLE (Konsisten di semua halaman) */
        .sidebar {
            width: 250px; background-color: #d9d9d9; min-height: 100vh;
            border-right: 1px solid #000; padding: 30px;
            position: fixed; top: 0; left: 0;
        }
        .sidebar h2 { font-weight: bold; margin-bottom: 50px; text-align: center; }
        .nav-link { color: #000; font-size: 18px; margin-bottom: 15px; text-decoration: none; display: block; padding: 5px 10px; }
        .nav-link:hover, .nav-link.active { font-weight: bold; }

        /* CONTENT STYLE */
        .main-content { margin-left: 250px; padding: 40px; }
        .stat-card {
            background-color: #d9d9d9; border: 1px solid #000; border-radius: 8px;
            padding: 20px; text-align: center; height: 100%;
            display: flex; flex-direction: column; justify-content: center;
        }
        .stat-card h5 { font-weight: normal; font-size: 1.2rem; margin-bottom: 15px; }
        .stat-card h1 { font-size: 2.5rem; font-weight: 500; }
        
        .btn-custom {
            background-color: #d9d9d9; border: 1px solid #000; color: #000;
            border-radius: 8px; padding: 8px 15px; font-size: 14px;
        }
        .btn-custom:hover { background-color: #c0c0c0; }

        .table-container {
            background-color: #d9d9d9; border: 1px solid #000; border-radius: 10px;
            padding: 20px; margin-top: 30px; min-height: 500px;
        }
        .filter-box { border: 1px solid #000; border-radius: 8px; padding: 5px 15px; display: flex; align-items: center; }
        .search-input { background-color: #d9d9d9; border: 1px solid #000; border-radius: 5px; }
        
        .table-custom { width: 100%; border-collapse: collapse; margin-top: 20px; border: 1px solid #000; }
        .table-custom th, .table-custom td { border: 1px solid #000; padding: 10px; background-color: #d9d9d9; }
        .table-custom thead th { text-transform: uppercase; font-size: 14px; font-weight: normal; }
        .badge-status { border: 1px solid #000; border-radius: 20px; padding: 5px 15px; font-size: 12px; }
        .empty-row td { height: 35px; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <h2>Logo</h2>
        
        <a href="dashboard.php?library_id=<?= $library_id ?>" class="nav-link fw-bold">Beranda</a>
        
        <a href="kuisioner_iplm.php?library_id=<?= $library_id ?>" class="nav-link">Kuisioner IPLM</a>
        <a href="kuisioner_tkm.php?library_id=<?= $library_id ?>" class="nav-link">Kuisioner TKM</a>
        
        <a href="riwayat.php?library_id=<?= $library_id ?>" class="nav-link">Riwayat</a>
        <a href="profil.php?library_id=<?= $library_id ?>" class="nav-link">Profil</a>

        <div class="mt-5 pt-5 border-top border-dark">
             <a href="pilih_perpustakaan.php" class="nav-link text-danger small">Keluar</a>
        </div>
    </nav>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h3><?= htmlspecialchars($library['nama'] ?? 'Dashboard') ?></h3>
            <div class="d-flex gap-3">
                <button class="btn btn-custom">Export Directory</button>
                <button class="btn btn-custom">Tambahkan Perpustakaan</button>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <h5>Total Perpustakaan</h5>
                    <h1>900</h1>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h5>Sudah Isi</h5>
                    <h1>333</h1>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h5>Belum Isi</h5>
                    <h1>567</h1>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="row mb-3 align-items-center">
                <div class="col-md-3">
                    <div class="filter-box d-flex justify-content-between">
                        <span>Periode: Januari 2026</span>
                        <span>▼</span> 
                    </div>
                </div>
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control search-input text-center" placeholder="search">
                        <button class="btn btn-custom px-4">filter</button>
                    </div>
                </div>
            </div>

            <table class="table-custom">
                <thead>
                    <tr>
                        <th width="30%">NAMA PERPUSTAKAAN</th>
                        <th width="15%">SUB-JENIS</th>
                        <th width="25%">ALAMAT</th>
                        <th width="30%">KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Perpustakaan SDN 1 Sandik</td>
                        <td>Sekolah</td>
                        <td>Jl.lorem ipsum</td>
                        <td class="text-center">
                            <span class="badge-status">Sudah Mengisi Kuisioner</span>
                        </td>
                    </tr>
                    <?php for($i=0; $i<8; $i++): ?>
                    <tr class="empty-row">
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            <div style="height: 50px;"></div>
        </div>
    </main>

</body>
</html>