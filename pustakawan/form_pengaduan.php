<?php
// web-perpus-v1/pustakawan/form_pengaduan.php
require_once __DIR__ . '/../config/public_security.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Pengaduan & Saran</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/loader.css">
    <link rel="stylesheet" href="../assets/public-responsive.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; padding: 40px 20px; }
        .card-form { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .form-control { border-radius: 8px; padding: 12px; }
        .btn-kirim { border-radius: 8px; padding: 12px; font-weight: bold; background-color: #198754; border:none; width: 100%; color: white;}
        .btn-kirim:hover { background-color: #146c43; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../config/loader.php'; ?>

    <div class="container" style="max-width: 600px;">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Layanan Pengaduan & Saran</h3>
            <p class="text-muted">Masukan Anda sangat berarti bagi kemajuan layanan kami.</p>
        </div>

        <div class="card card-form p-4 bg-white">
            <form action="proses_pengaduan.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= public_csrf_token() ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Lengkap (Opsional)</label>
                    <input type="text" name="nama" class="form-control" placeholder="Boleh dikosongkan jika ingin anonim">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Kontak (Email / No. HP)</label>
                    <input type="text" name="kontak" class="form-control" placeholder="Agar kami bisa merespons (Opsional)">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Isi Pengaduan / Saran <span class="text-danger">*</span></label>
                    <textarea name="pesan" class="form-control" rows="5" required placeholder="Tuliskan laporan atau saran Anda di sini secara detail..."></textarea>
                </div>

                <button type="submit" class="btn-kirim">KIRIM LAPORAN</button>
            </form>
        </div>

        <div class="text-center mt-4">
            <a href="../index.php" class="text-decoration-none text-muted">&larr; Kembali ke Menu Utama</a>
        </div>
    </div>
    <script src="../assets/loader.js"></script>

</body>
</html>
