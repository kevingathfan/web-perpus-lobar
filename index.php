<?php
// web-perpus-v1/index.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dinas Kearsipan dan Perpustakaan - Lombok Barat</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/loader.css">
    <link rel="stylesheet" href="assets/public-responsive.css">

    <style>
        body {
            background-color: #ffffff;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-wrapper {
            flex: 1; /* Agar footer selalu di bawah jika konten sedikit */
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
        }

        /* ================= HEADER ================= */
        .header-box {
            width:100%;
            max-width: 1000px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 40px;
            background: #fff;
            background-image: linear-gradient(to bottom, #000 0px, #000 1px, transparent 1px, transparent 4px, #000 4px, #000 5px);
            background-repeat: no-repeat;
            background-size: 100% 5px;
            background-position: bottom;
        }

        .header-text { text-align: center; flex-grow: 1; }
        .header-text h2 { font-size: 26px; font-weight: 600; margin: 0; }
        .logo-img { height: 150px; width: 150px; object-fit: contain; }

        /* ================= INFO BOX ================= */
        .info-box {
            max-width: 1000px;
            background: #fff;
            border: 1px solid #000;
            border-radius: 15px;
            padding: 40px;
            margin: 0 40px 40px 40px;
            box-shadow: 0px 4px 6px rgba(0,0,0,0.05);
            text-align: center;
        }

        .info-box h4 { font-size: 18px; line-height: 1.6; margin: 0; font-weight: 500; }
        .info-footer { margin-top: 50px; text-align: left; }

        /* ================= CARD MENU ================= */
        .card-menu {
            position: relative;
            background: #fff;
            border: 1px solid #000;
            border-radius: 15px;
            padding: 30px 20px;
            text-align: center;
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #000;
            box-shadow: 0px 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card-menu h3 { font-size: 20px; font-weight: 500; line-height: 1.4; margin: 0; }
        .card-menu::before {
            content: ""; position: absolute; top: 0; left: 0; width: 6px; height: 100%;
            background-color: #0d6efd; transform: scaleY(0); transform-origin: top; transition: transform 0.3s ease;
        }
        .card-menu:hover { transform: translateY(-7px); box-shadow: 0 18px 32px rgba(0, 0, 0, 0.18); }
        .card-menu:hover::before { transform: scaleY(1); }

        /* Khusus Tombol Pengaduan */
        .card-aduan {
            height: auto; min-height: 100px;
            border-color: #198754; background-color: #f8fff9;
        }
        .card-aduan::before { background-color: #198754; }
        .card-aduan h3 { font-size: 18px; font-weight: 600; }

        /* ================= MAIN FOOTER ================= */
        .main-footer {
            background-color: #212529; /* Warna Gelap */
            color: #adb5bd;
            padding: 60px 0 20px 0;
            margin-top: auto;
            border-top: 5px solid #0d6efd;
        }
        .main-footer h5 { color: #fff; font-weight: 700; margin-bottom: 20px; text-transform: uppercase; font-size: 16px; letter-spacing: 1px; }
        .main-footer p, .main-footer li { font-size: 14px; line-height: 1.8; }
        .main-footer a { color: #adb5bd; text-decoration: none; transition: 0.3s; }
        .main-footer a:hover { color: #fff; padding-left: 5px; }
        .footer-bottom { border-top: 1px solid #343a40; padding-top: 20px; margin-top: 40px; text-align: center; font-size: 13px; }
        
        .map-container iframe { width: 100%; height: 200px; border-radius: 10px; border:0; filter: grayscale(20%); }
        .map-container iframe:hover { filter: grayscale(0%); transition: 0.5s; }
        
        .contact-item { display: flex; align-items: start; gap: 10px; margin-bottom: 15px; }
        .contact-item i { color: #0d6efd; margin-top: 4px; font-size: 1.1rem; }

    </style>
</head>
<body>
    <?php include __DIR__ . '/config/loader.php'; ?>
    <div class="main-wrapper">
        <div class="header-box">
            <img src="assets/logo_lobar.png" alt="Logo" class="logo-img">
            <div class="header-text">
                <h2>Dinas Kearsipan dan Perpustakaan</h2>
                <p class="mb-0">Kabupaten Lombok Barat</p>
            </div>
            <img src="assets/logo_disarpus.png" alt="Logo" class="logo-img">
        </div>

        <div class="info-box">
            <h4>
                Kuisioner Pengukuran Indeks Pembangunan Literasi Masyarakat
                dan Kegemaran Membaca Kabupaten Lombok Barat
            </h4>
        </div>

        <div class="mb-4 fs-5 fw-bold">Pilih Layanan</div>

        <div class="container" style="max-width: 900px;">
            <div class="row g-4">
                <div class="col-md-6">
                    <a href="pustakawan/pilih_perpustakaan.php?target=iplm" class="card-menu">
                        <h3>
                            Kuisioner Pengukuran<br>
                            Indeks Pembangunan<br>
                            Literasi Masyarakat<br>
                            (IPLM)
                        </h3>
                    </a>
                </div>

                <div class="col-md-6">
                    <a href="pustakawan/kuisioner_tkm.php?target=tkm" class="card-menu">
                        <h3>
                            Kuisioner<br>
                            Tingkat Kegemaran<br>
                            Membaca (TKM)
                        </h3>
                    </a>
                </div>

                <div class="col-12">
                    <a href="pustakawan/form_pengaduan.php" class="card-menu card-aduan">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-envelope-paper-heart-fill fs-1 text-success"></i>
                            <div class="text-start">
                                <h3>Layanan Pengaduan & Saran</h3>
                                <small class="text-muted">Punya kritik, saran, atau keluhan? Sampaikan kepada kami disini.</small>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <div class="info-box info-footer">
            <h6 class="fw-bold"><i class="bi bi-info-circle-fill me-2"></i>Mengapa kuisioner ini penting?</h6>
            <p class="text-muted small mb-0">
                Data yang Anda isikan sangat berharga untuk mengukur kemajuan literasi di daerah kita.
                Hasil survei ini akan digunakan sebagai dasar pengambilan kebijakan pengembangan
                perpustakaan di masa depan.
            </p>
        </div>
    </div>

    <footer class="main-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <h5>Tentang Kami</h5>
                    <p class="mb-4">Dinas Kearsipan dan Perpustakaan Kabupaten Lombok Barat berkomitmen meningkatkan minat baca dan pengelolaan arsip yang profesional.</p>
                    
                    <div class="contact-item">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>Jln. Raya BIL KM 21 Giri Menang Gerung, Kode Pos 83363</span>
                    </div>
                    <div class="contact-item">
                        <i class="bi bi-telephone-fill"></i>
                        <span>(0370) 681239 | Fax (0370) 681250</span>
                    </div>
                    <div class="contact-item">
                        <i class="bi bi-envelope-fill"></i>
                        <span>disarpus@lombokbaratkab.go.id</span>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 ps-lg-5">
                    <h5>Tautan Cepat</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php"><i class="bi bi-chevron-right small me-1"></i> Beranda</a></li>
                        <li class="mb-2"><a href="pustakawan/pilih_perpustakaan.php?target=iplm"><i class="bi bi-chevron-right small me-1"></i> Survei IPLM</a></li>
                        <li class="mb-2"><a href="pustakawan/kuisioner_tkm.php?target=tkm"><i class="bi bi-chevron-right small me-1"></i> Survei TKM</a></li>
                        <li class="mb-2"><a href="pustakawan/form_pengaduan.php"><i class="bi bi-chevron-right small me-1"></i> Pengaduan Masyarakat</a></li>
                        <li class="mt-3"><a href="admin/login.php" class="text-white fw-bold"><i class="bi bi-lock-fill me-1"></i> Login Administrator</a></li>
                    </ul>
                </div>

                <div class="col-lg-4 col-md-12">
                    <h5>Lokasi Kantor</h5>
                    <div class="map-container">
                        <iframe src="https://maps.google.com/maps?q=Perpustakaan+Daerah+Lombok+Barat&t=&z=15&ie=UTF8&iwloc=&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p class="mb-0">© <?= date('Y') ?> Dinas Kearsipan dan Perpustakaan Kab. Lombok Barat. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
    <script src="assets/loader.js"></script>

</body>
</html>
