<?php
// web-perpus-v1/admin/pengaduan.php
session_start();
require '../config/database.php';

// Hapus Pengaduan
if (isset($_POST['aksi']) && $_POST['aksi'] == 'hapus') {
    $stmt = $pdo->prepare("DELETE FROM pengaduan WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    header("Location: pengaduan.php"); exit;
}

// Ambil Data
$stmt = $pdo->query("SELECT * FROM pengaduan ORDER BY created_at DESC");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Pengaduan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* CSS DISAMAKAN DENGAN DASHBOARD.PHP */
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; overflow-x: hidden; }
        
        .sidebar { min-height: 100vh; width: 260px; background-color: #ffffff; border-right: 1px solid #e0e0e0; position: fixed; top: 0; left: 0; padding: 40px 20px; z-index: 100; }
        .sidebar-header { font-weight: 800; font-size: 24px; margin-bottom: 50px; color: #000; text-align: center; letter-spacing: 1px; }
        
        .nav-link { color: #666; font-weight: 600; font-size: 15px; padding: 12px 20px; margin-bottom: 8px; border-radius: 8px; transition: all 0.3s; display: flex; align-items: center; gap: 10px; }
        .nav-link:hover, .nav-link.active { background-color: #000; color: #fff; }
        
        .main-content { margin-left: 260px; padding: 40px 50px; }
        
        /* STYLE KHUSUS KARTU PESAN */
        .card-msg { border: 1px solid #e0e0e0; border-radius: 16px; background: #fff; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); transition: transform 0.2s; }
        .card-msg:hover { transform: translateY(-3px); }
        .bg-pesan { background-color: #f8f9fa; border-radius: 12px; padding: 15px; border: 1px dashed #dee2e6; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="sidebar-header">DISARPUS</div>
        <div class="nav flex-column">
            <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-fill"></i> DASHBOARD</a>
            <a href="perpustakaan.php" class="nav-link"><i class="bi bi-building"></i> PERPUSTAKAAN</a>
            <a href="atur_pertanyaan.php" class="nav-link"><i class="bi bi-file-text"></i> KUISIONER</a>
            <a href="pengaduan.php" class="nav-link active"><i class="bi bi-chat-left-text"></i> PENGADUAN</a>
            <div class="mt-5 pt-5 border-top">
                <a href="../index.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> KELUAR</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold m-0">Layanan Pengaduan</h2>
                <p class="text-muted m-0">Daftar kritik, saran, dan laporan masuk.</p>
            </div>
            <button class="btn btn-dark btn-sm rounded-pill px-3"><?= date('d M Y') ?></button>
        </div>

        <?php if(empty($data)): ?>
            <div class="alert alert-light border text-center py-5 rounded-4">
                <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
                <h5 class="fw-bold">Belum ada pesan masuk</h5>
                <p class="text-muted">Kotak masuk pengaduan masih kosong.</p>
            </div>
        <?php else: ?>
            <?php foreach($data as $row): ?>
            <div class="card-msg">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 45px; height: 45px; font-size: 18px;">
                            <?= strtoupper(substr($row['nama'], 0, 1)) ?>
                        </div>
                        <div>
                            <h6 class="fw-bold m-0 text-uppercase"><?= htmlspecialchars($row['nama']) ?></h6>
                            <small class="text-muted"><i class="bi bi-envelope-fill me-1"></i> <?= htmlspecialchars($row['kontak']) ?></small>
                        </div>
                    </div>
                    <span class="badge bg-light text-dark border rounded-pill px-3">
                        <i class="bi bi-clock me-1"></i> <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                    </span>
                </div>
                
                <div class="bg-pesan mb-3">
                    <p class="m-0 text-secondary" style="white-space: pre-line;"><?= htmlspecialchars($row['pesan']) ?></p>
                </div>

                <div class="text-end">
                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pesan ini? Data tidak bisa dikembalikan.')">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-4 fw-bold">
                            <i class="bi bi-trash-fill me-1"></i> Hapus Pesan
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

</body>
</html>