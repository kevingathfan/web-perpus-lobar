<?php
// web-perpus-v1/pustakawan/form_pengaduan.php
require_once __DIR__ . '/../config/public_security.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Layanan Pengaduan & Saran - Royal GovTech</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/govtech.css">
    <link rel="stylesheet" href="../assets/loader.css">
    <style>
        :root {
            --primary: #0F52BA;
            --primary-dark: #0a3d8f;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }
        body {
            background-color: #f8fafc;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
            position: relative;
            overflow-x: hidden;
            margin: 0;
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
            background-position: 0 0, 0 0, 0 0, 0 0;
            mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
        }

        .form-container {
            width: 100%;
            max-width: 700px;
            z-index: 1;
        }

        .hero-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 20px;
            box-shadow: 0 10px 20px rgba(15, 82, 186, 0.2);
            transform: rotate(-5deg);
        }

        .main-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
            padding: 40px;
        }

        .brand-logo { height: 35px !important; width: auto !important; object-fit: contain; opacity: 0.9; }

        .form-label {
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .btn-primary-gov {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 16px;
            border-radius: 50px;
            font-weight: 800;
            color: white;
            box-shadow: 0 4px 12px rgba(15, 82, 186, 0.25);
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-primary-gov:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 82, 186, 0.35);
        }

        .back-btn {
            position: absolute;
            top: 30px;
            left: 30px;
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.8);
            padding: 10px 20px;
            border-radius: 50px;
            border: 1px solid var(--border);
            transition: 0.3s;
        }
        .back-btn:hover {
            color: var(--primary);
            background: #fff;
            transform: translateX(-5px);
        }

        @media (max-width: 768px) {
            .back-btn { 
                position: relative; 
                top: 0; 
                left: 0; 
                margin: 0 auto 30px; 
                display: flex; 
                width: fit-content;
                justify-content: center;
            }
            body { display: block; padding: 20px 12px; }
            .form-container { padding: 0; }
            .main-card { padding: 24px 16px; border-radius: 20px; }
            .hero-icon { width: 60px; height: 60px; font-size: 1.5rem; }
            h2 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../config/loader.php'; ?>
    <div class="bg-pattern"></div>

    <a href="../index.php" class="back-btn">
        <i class="bi bi-arrow-left"></i> Kembali ke Beranda
    </a>

    <div class="form-container">
        <div class="text-center mb-5">
            <div class="d-flex justify-content-center gap-3 mb-4">
                <img src="../assets/logo_lobar.png" alt="Logo Lobar" class="brand-logo">
                <img src="../assets/logo_disarpus.png" alt="Logo Disarpus" class="brand-logo">
            </div>
            <div class="hero-icon">
                <i class="bi bi-envelope-open-heart"></i>
            </div>
            <h2 class="fw-extrabold text-dark mb-1">KOTAK ASPIRASI</h2>
            <p class="text-muted">Sampaikan saran, kritik, atau pengaduan Anda</p>
        </div>

        <div class="main-card">
            <form action="proses_pengaduan.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= public_csrf_token() ?>">
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px; border-color: var(--border);"><i class="bi bi-person text-muted"></i></span>
                            <input type="text" name="nama" class="form-control border-start-0 ps-0" style="border-radius: 0 12px 12px 0; border-color: var(--border); height: 50px;" placeholder="Boleh anonim">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">WhatsApp / Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px; border-color: var(--border);"><i class="bi bi-send text-muted"></i></span>
                            <input type="text" name="kontak" class="form-control border-start-0 ps-0" style="border-radius: 0 12px 12px 0; border-color: var(--border); height: 50px;" placeholder="Untuk tanggapan">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Isi Laporan / Pesan <span class="text-danger">*</span></label>
                        <textarea name="pesan" class="form-control shadow-sm" rows="5" required placeholder="Tuliskan pengalaman atau saran Anda secara detail di sini..." style="border-radius: 16px; border-color: var(--border); resize: none; padding: 15px;"></textarea>
                    </div>

                    <div class="col-12 pt-3">
                        <button type="submit" class="btn btn-primary-gov w-100">
                            <i class="bi bi-send-fill me-2"></i> Kirim Pesan Sekarang
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="text-center mt-5 text-muted small fw-bold">
            &copy; <?= date('Y') ?> Dinas Kearsipan & Perpustakaan. All rights reserved.
        </div>
    </div>

    <script src="../assets/loader.js"></script>
</body>
</html>
