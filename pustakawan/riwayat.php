<?php
// web-perpus-v1/pustakawan/riwayat.php
require '../config/database.php';
$library_id = isset($_GET['library_id']) ? $_GET['library_id'] : '';

// Contoh: Ambil riwayat dari database (Kode PHP Placeholder)
// $stmt = $pdo->prepare("SELECT * FROM reports WHERE library_id = ?"); ...
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Laporan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #ffffff; font-family: sans-serif; }
        .sidebar { width: 250px; background-color: #d9d9d9; min-height: 100vh; border-right: 1px solid #000; padding: 30px; position: fixed; top: 0; left: 0; }
        .sidebar h2 { font-weight: bold; margin-bottom: 50px; text-align: center; }
        .nav-link { color: #000; font-size: 18px; margin-bottom: 15px; text-decoration: none; display: block; padding: 5px 10px; }
        .nav-link:hover, .nav-link.fw-bold { font-weight: bold; }
        .main-content { margin-left: 250px; padding: 40px; }
        
        .table-custom { width: 100%; border-collapse: collapse; margin-top: 20px; border: 1px solid #000; }
        .table-custom th, .table-custom td { border: 1px solid #000; padding: 15px; background-color: #d9d9d9; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <h2>Logo</h2>
        <a href="dashboard.php?library_id=<?= $library_id ?>" class="nav-link">Beranda</a>
        <a href="kuisioner_iplm.php?library_id=<?= $library_id ?>" class="nav-link">Kuisioner IPLM</a>
        <a href="kuisioner_tkm.php?library_id=<?= $library_id ?>" class="nav-link">Kuisioner TKM</a>
        <a href="#" class="nav-link fw-bold">Riwayat</a> <a href="profil.php?library_id=<?= $library_id ?>" class="nav-link">Profil</a>
    </nav>

    <main class="main-content">
        <h3 class="mb-4">Riwayat Laporan</h3>

        <div class="p-4" style="background-color: #d9d9d9; border: 1px solid #000; border-radius: 10px;">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Jenis Laporan</th>
                        <th>Tanggal Kirim</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Januari 2026</td>
                        <td>IPLM</td>
                        <td>28-01-2026</td>
                        <td><span class="badge bg-success border border-dark text-white">Disetujui</span></td>
                        <td><button class="btn btn-sm btn-dark">Lihat</button></td>
                    </tr>
                    <tr>
                        <td>Januari 2026</td>
                        <td>TKM</td>
                        <td>-</td>
                        <td><span class="badge bg-danger border border-dark text-white">Belum Mengisi</span></td>
                        <td><a href="kuisioner_tkm.php?library_id=<?= $library_id ?>" class="btn btn-sm btn-dark">Isi Sekarang</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>