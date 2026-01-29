<?php
// web-perpus-v1/pustakawan/profil.php
require '../config/database.php';
$library_id = isset($_GET['library_id']) ? $_GET['library_id'] : '';

// Ambil data real
if($library_id) {
    $stmt = $pdo->prepare("SELECT * FROM libraries WHERE id = ?");
    $stmt->execute([$library_id]);
    $data = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Perpustakaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #ffffff; font-family: sans-serif; }
        .sidebar { width: 250px; background-color: #d9d9d9; min-height: 100vh; border-right: 1px solid #000; padding: 30px; position: fixed; top: 0; left: 0; }
        .sidebar h2 { font-weight: bold; margin-bottom: 50px; text-align: center; }
        .nav-link { color: #000; font-size: 18px; margin-bottom: 15px; text-decoration: none; display: block; padding: 5px 10px; }
        .nav-link:hover, .nav-link.fw-bold { font-weight: bold; }
        .main-content { margin-left: 250px; padding: 40px; }
        
        .form-section { background-color: #d9d9d9; border: 1px solid #000; border-radius: 8px; padding: 25px; }
        .btn-submit { background-color: #000; color: #fff; border: none; padding: 10px 40px; border-radius: 8px; font-weight: bold; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <h2>Logo</h2>
        <a href="dashboard.php?library_id=<?= $library_id ?>" class="nav-link">Beranda</a>
        <a href="kuisioner_iplm.php?library_id=<?= $library_id ?>" class="nav-link">Kuisioner IPLM</a>
        <a href="kuisioner_tkm.php?library_id=<?= $library_id ?>" class="nav-link">Kuisioner TKM</a>
        <a href="riwayat.php?library_id=<?= $library_id ?>" class="nav-link">Riwayat</a>
        <a href="#" class="nav-link fw-bold">Profil</a> </nav>

    <main class="main-content">
        <h3 class="mb-4">Profil Perpustakaan</h3>

        <div class="form-section">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Perpustakaan</label>
                    <input type="text" class="form-control border-dark" value="<?= htmlspecialchars($data['nama'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Jenis Perpustakaan</label>
                    <input type="text" class="form-control border-dark" value="<?= htmlspecialchars($data['jenis'] ?? '') ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Alamat Lengkap</label>
                    <textarea class="form-control border-dark" rows="3"><?= htmlspecialchars($data['alamat'] ?? '') ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Kecamatan</label>
                    <input type="text" class="form-control border-dark" value="<?= htmlspecialchars($data['kecamatan'] ?? '') ?>">
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn-submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </main>

</body>
</html>