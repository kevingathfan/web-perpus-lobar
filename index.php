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
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@500;600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/loader.css">
    <link rel="stylesheet" href="assets/public-responsive.css">

    <style>
        :root {
            --ink: #0b1220;
            --muted: #5c6676;
            --brand: #0b2b5b;
            --brand-strong: #0a2246;
            --accent: #c8a34a;
            --paper: #ffffff;
            --mist: #f3f5f8;
            --border: rgba(11, 18, 32, 0.12);
            --shadow: 0 18px 45px rgba(11, 18, 32, 0.12);
        }

        body {
            background: radial-gradient(1100px 500px at 10% -10%, #e9f0ff 0%, rgba(233,240,255,0) 60%),
                        radial-gradient(900px 600px at 90% 0%, #f6efe2 0%, rgba(246,239,226,0) 55%),
                        var(--mist);
            font-family: 'Manrope', sans-serif;
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-wrapper {
            flex: 1; /* Agar footer selalu di bawah jika konten sedikit */
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 28px 20px 56px;
        }

        .content-wrap {
            width: 100%;
            max-width: 1100px;
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        /* ================= HEADER ================= */
        .header-box {
            width: 100%;
            max-width: 1100px;
            padding: 18px 26px;
            display: grid;
            grid-template-columns: 140px 1fr 140px;
            align-items: center;
            gap: 10px;
            margin-bottom: 0;
            background: rgba(255,255,255,0.92);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            backdrop-filter: blur(6px);
        }

        .header-text { text-align: center; }
        .header-text h2 {
            font-size: 32px;
            font-weight: 600;
            margin: 0;
            font-family: 'Crimson Pro', serif;
            letter-spacing: 0.3px;
        }
        .header-text p { margin: 6px 0 0; color: var(--muted); font-weight: 500; font-size: 16px; }
        .logo-img { height: 110px; width: 110px; object-fit: contain; margin: 0 auto; }

        /* ================= HERO ================= */
        .hero-panel {
            background: linear-gradient(135deg, #0a2447 0%, #12386c 58%, #1f4a85 100%);
            color: #f7f9fc;
            border-radius: 18px;
            padding: 30px 32px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }
        .hero-panel::after {
            content: "";
            position: absolute;
            right: -80px;
            top: -120px;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(200,163,74,0.45), rgba(200,163,74,0) 70%);
        }
        .hero-panel h1 {
            font-family: 'Crimson Pro', serif;
            font-size: 30px;
            margin: 0 0 8px 0;
            letter-spacing: 0.2px;
        }
        .hero-panel p {
            margin: 0;
            color: rgba(255,255,255,0.78);
            font-size: 15px;
        }
        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 16px;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 12px;
            color: #0a2447;
            background: rgba(255,255,255,0.85);
            padding: 6px 10px;
            border-radius: 999px;
        }
        .stat-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-top: 14px;
        }
        .stat-card {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            padding: 14px 16px;
            border-radius: 12px;
            color: #f7f9fc;
        }
        .stat-card strong { display: block; font-size: 18px; }
        .stat-card span { font-size: 12px; color: rgba(255,255,255,0.7); }

        /* ================= INFO BOX ================= */
        .info-box {
            max-width: 1100px;
            background: var(--paper);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px 26px;
            margin: 0;
        }

        .info-box h4 { font-size: 18px; line-height: 1.6; margin: 6px 0; font-weight: 600; }
        .info-footer { margin-top: 36px; text-align: left; }

        .section-lead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 8px 6px 0;
        }
        .section-lead h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: var(--ink);
        }
        .section-lead p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        /* ================= CARD MENU ================= */
        .card-menu {
            position: relative;
            background: var(--paper);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 22px 24px;
            text-align: center;
            min-height: 210px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            text-decoration: none;
            color: var(--ink);
            transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
            overflow: hidden;
            gap: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            animation: cardReveal 0.45s ease both;
            will-change: transform, box-shadow;
        }

        .card-menu .card-title { font-size: 20px; font-weight: 700; line-height: 1.3; margin: 0 0 6px 0; }
        .card-menu .card-desc { font-size: 14px; color: var(--muted); margin: 0; }
        .card-menu .card-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            background: rgba(11,43,91,0.08);
            color: var(--brand);
        }
        .card-menu .card-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            color: var(--brand);
        }
        .card-menu .card-icon {
            height: 52px;
            width: 52px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(11,43,91,0.10);
            color: var(--brand);
            font-size: 24px;
            flex: 0 0 auto;
        }
        .card-menu::after {
            content: "";
            position: absolute;
            right: -30px;
            top: -30px;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(11,43,91,0.12), rgba(11,43,91,0) 70%);
        }
        .card-menu:hover {
            border-color: rgba(11,43,91,0.28);
            transform: translateY(-4px);
            box-shadow: 0 18px 30px rgba(15, 23, 42, 0.12);
        }
        .card-menu:focus-visible {
            outline: 3px solid rgba(11, 43, 91, 0.25);
            outline-offset: 4px;
        }
        .card-menu:hover .card-icon {
            transform: translateY(-2px);
        }
        .card-menu .card-icon {
            transition: transform 0.22s ease;
        }

        .row.g-4 > :nth-child(1) .card-menu { animation-delay: 0.05s; }
        .row.g-4 > :nth-child(2) .card-menu { animation-delay: 0.12s; }
        .row.g-4 > :nth-child(3) .card-menu { animation-delay: 0.18s; }

        @keyframes cardReveal {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Khusus Tombol Pengaduan */
        .card-aduan {
            border-color: rgba(200,163,74,0.45);
            background: linear-gradient(135deg, #fff9ef 0%, #fff 70%);
        }
        .card-aduan .card-icon {
            background: rgba(200,163,74,0.18);
            color: #8a6b21;
        }
        .card-aduan .card-cta { color: #8a6b21; }

        /* ================= MAIN FOOTER ================= */
        .main-footer {
            background: linear-gradient(135deg, #0a1b33 0%, #0c2442 60%, #0f2d54 100%);
            color: #c7d0dd;
            padding: 70px 0 22px 0;
            margin-top: auto;
            border-top: 5px solid var(--accent);
        }
        .main-footer h5 {
            color: #fff;
            font-weight: 700;
            margin-bottom: 18px;
            text-transform: uppercase;
            font-size: 15px;
            letter-spacing: 1px;
        }
        .main-footer p, .main-footer li { font-size: 14px; line-height: 1.8; }
        .main-footer a { color: #c7d0dd; text-decoration: none; transition: 0.3s; }
        .main-footer a:hover { color: #fff; padding-left: 5px; }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.15); padding-top: 18px; margin-top: 40px; text-align: center; font-size: 13px; }

        .map-container iframe { width: 100%; height: 200px; border-radius: 10px; border:0; filter: grayscale(20%); }
        .map-container iframe:hover { filter: grayscale(0%); transition: 0.5s; }

        .contact-item { display: flex; align-items: start; gap: 10px; margin-bottom: 15px; }
        .contact-item i { color: var(--accent); margin-top: 4px; font-size: 1.1rem; }

        @media (max-width: 768px) {
            .header-box { grid-template-columns: 1fr; text-align: center; }
            .header-text h2 { font-size: 26px; }
            .header-text p { font-size: 14px; }
            .logo-img { height: 88px; width: 88px; }
            .section-lead { flex-direction: column; text-align: center; }
            .stat-strip { grid-template-columns: 1fr; }
        }

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

        <div class="content-wrap">
            <div class="hero-panel">
                <h1>Kuisioner Indeks Pembangunan Literasi Masyarakat</h1>
                <p>Instrumen resmi untuk mengukur literasi dan kegemaran membaca di Kabupaten Lombok Barat.</p>
                <div class="stat-strip">
                    <div class="stat-card">
                        <strong>IPLM</strong>
                        <span>Indeks Pembangunan Literasi</span>
                    </div>
                    <div class="stat-card">
                        <strong>TKM</strong>
                        <span>Tingkat Kegemaran Membaca</span>
                    </div>
                    <div class="stat-card">
                        <strong>Layanan Publik</strong>
                        <span>Pengaduan & Saran</span>
                    </div>
                </div>
            </div>

            <div class="section-lead">
                <h3>Pilih Layanan</h3>
                <p>Mulai dari layanan utama atau kirim pengaduan secara resmi.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <a href="pustakawan/pilih_perpustakaan.php?target=iplm" class="card-menu">
                        <div class="d-flex flex-column text-start">
                            <div class="card-chip mb-2"><i class="bi bi-clipboard-check"></i> Kuesioner Resmi</div>
                            <div class="card-title">Indeks Pembangunan Literasi Masyarakat (IPLM)</div>
                            <p class="card-desc">Ukur perkembangan literasi masyarakat secara menyeluruh.</p>
                            <div class="card-cta mt-3">Mulai Kuesioner <i class="bi bi-arrow-right"></i></div>
                        </div>
                        <div class="card-icon">
                            <i class="bi bi-bar-chart-line-fill"></i>
                        </div>
                    </a>
                </div>

                <div class="col-md-6">
                    <a href="pustakawan/kuisioner_tkm.php?target=tkm" class="card-menu">
                        <div class="d-flex flex-column text-start">
                            <div class="card-chip mb-2"><i class="bi bi-clipboard-check"></i> Kuesioner Resmi</div>
                            <div class="card-title">Tingkat Kegemaran Membaca (TKM)</div>
                            <p class="card-desc">Gambarkan minat baca masyarakat di Lombok Barat.</p>
                            <div class="card-cta mt-3">Mulai Kuesioner <i class="bi bi-arrow-right"></i></div>
                        </div>
                        <div class="card-icon">
                            <i class="bi bi-book-half"></i>
                        </div>
                    </a>
                </div>

                <div class="col-12">
                    <a href="pustakawan/form_pengaduan.php" class="card-menu card-aduan">
                        <div class="d-flex flex-column text-start">
                            <div class="card-chip mb-2" style="background: rgba(200,163,74,0.18); color: #8a6b21;"><i class="bi bi-chat-heart-fill"></i> Layanan Publik</div>
                            <div class="card-title">Pengaduan & Saran</div>
                            <p class="card-desc">Sampaikan kritik, saran, atau keluhan agar layanan makin baik.</p>
                            <div class="card-cta mt-2">Kirim Pengaduan <i class="bi bi-arrow-right"></i></div>
                        </div>
                        <div class="card-icon">
                            <i class="bi bi-envelope-paper-heart-fill"></i>
                        </div>
                    </a>
                </div>
            </div>

            <div class="info-box info-footer">
                <h6 class="fw-bold"><i class="bi bi-info-circle-fill me-2"></i>Mengapa kuisioner ini penting?</h6>
                <p class="text-muted small mb-0">
                    Data yang Anda isikan sangat berharga untuk mengukur kemajuan literasi di daerah kita.
                    Hasil survei ini menjadi dasar pengambilan kebijakan pengembangan perpustakaan yang akurat,
                    transparan, dan berkelanjutan.
                </p>
            </div>
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
                <p class="mb-0">&copy; <?= date('Y') ?> Dinas Kearsipan dan Perpustakaan Kab. Lombok Barat. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
    <script src="assets/loader.js"></script>

</body>
</html>
