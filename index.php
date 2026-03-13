<?php
// web-perpus-v1/index.php
date_default_timezone_set('Asia/Makassar');
$hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$bulanIndo = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$tgl_sekarang = $hari[date('w')] . ', ' . date('d') . ' ' . $bulanIndo[date('m')] . ' ' . date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disarpus Lombok Barat - Portal Layanan Digital</title>

    <!-- Google Fonts: Plus Jakarta Sans (Modern, Geometric, Trustworthy) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/loader.css">
    
    <style>
        :root {
            /* Palette: Premium Royal GovTech */
            --primary: #0F52BA;       /* Sapphire Blue */
            --primary-dark: #0a3d8f;  /* Deep Royal */
            --primary-light: #eff6ff;
            --accent: #F4C430;        /* Saffron/Gold */
            --accent-glow: rgba(244, 196, 48, 0.3);
            
            --bg-body: #f8fafc;       /* Cool Gray 50 */
            --bg-surface: #ffffff;
            --bg-soft: #f1f5f9;
            --text-main: #0f172a;     /* Slate 900 */
            --text-muted: #64748b;    /* Slate 500 */
            --border: #e2e8f0;        /* Slate 200 */
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-float: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            --shadow-glow: 0 0 15px rgba(15, 82, 186, 0.15);
        }

        /* Base Reset */
        *, *::before, *::after { box-sizing: border-box; outline: none; }
        
        html, body { 
            margin: 0; padding: 0; 
            width: 100%; 
            overflow-x: hidden;
            scroll-behavior: smooth;
        }
        body {
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Animated Background Pattern */
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

        a { text-decoration: none; color: inherit; transition: all 0.2s ease; }
        h1, h2, h3, h4, h5 { font-weight: 700; color: var(--text-main); margin-top: 0; letter-spacing: -0.02em; }
        
        /* Utils */
        .wrapper { max-width: 1200px; margin: 0 auto; padding: 0 24px; position: relative; z-index: 2; }
        .text-gradient {
            background: linear-gradient(135deg, #0F52BA 0%, #0a3d8f 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            cursor: pointer;
            border: none;
            position: relative;
            overflow: hidden;
            z-index: 1;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(15, 82, 186, 0.25);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 82, 186, 0.35);
            color: #fff;
        }
        .btn-primary::after {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(255,255,255,0.2), transparent);
            opacity: 0; transition: 0.3s;
        }
        .btn-primary:hover::after { opacity: 1; }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border); /* Slightly stronger border */
            color: var(--text-main);
            box-shadow: var(--shadow-sm);
        }
        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: #fff;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.5em 1em;
            font-size: 0.75em;
            font-weight: 700;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: rgba(15, 82, 186, 0.08); /* Light Blue Tint */
            color: var(--primary);
            border: 1px solid rgba(15, 82, 186, 0.1);
        }
        
        /* --- Navigation --- */
        .navbar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 1rem 0;
            transition: all 0.3s ease;
        }
        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: var(--shadow-md);
            padding: 0.75rem 0;
        }
        
        .nav-content { display: flex; justify-content: space-between; align-items: center; }

        .brand { display: flex; align-items: center; gap: 14px; }
        .brand img { height: 40px !important; width: auto !important; transition: 0.3s; }
        .brand-text { display: flex; flex-direction: column; line-height: 1.15; }
        .brand-title { font-weight: 800; font-size: 1.15rem; color: var(--primary); letter-spacing: -0.5px; }
        .brand-subtitle { font-size: 0.8rem; color: var(--text-muted); font-weight: 500; letter-spacing: 0.2px; }

        /* --- Hero Section --- */
        .hero {
            padding: 5rem 0 4rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
        }
        
        /* Floating abstract shapes */
        .shape-blob {
            position: absolute;
            z-index: -1;
            filter: blur(80px);
            opacity: 0.6;
            animation: float 10s infinite ease-in-out;
        }
        .shape-1 { top: -10%; left: -10%; width: 400px; height: 400px; background: rgba(15, 82, 186, 0.15); animation-delay: 0s; }
        .shape-2 { bottom: 10%; right: -10%; width: 300px; height: 300px; background: rgba(244, 196, 48, 0.1); animation-delay: 2s; }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        .hero h1 {
            font-size: 4rem;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            letter-spacing: -1.5px;
            max-width: 900px;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .hero p {
            font-size: 1.25rem;
            color: var(--text-muted);
            max-width: 650px;
            margin-bottom: 2.5rem;
            line-height: 1.7;
        }

        .stats-strip {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 4rem;
            padding: 1.5rem 2rem;
            background: #fff;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-md);
            max-width: 900px;
            width: 100%;
        }

        .stat-item {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .stat-item:not(:last-child)::after {
            content: '';
            position: absolute;
            right: -0.75rem;
            top: 50%;
            transform: translateY(-50%);
            height: 40px;
            width: 1px;
            background: var(--border);
        }

        .stat-val { display: block; font-size: 2rem; font-weight: 800; color: var(--primary); line-height: 1; margin-bottom: 4px; }
        .stat-lbl { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }

        /* --- Services Grid --- */
        .services-section { padding: 4rem 0 6rem; flex-grow: 1; }
        
        .section-header { text-align: center; margin-bottom: 3.5rem; }
        .section-header h2 { font-size: 2.25rem; margin-bottom: 0.75rem; }
        .section-header p { color: var(--text-muted); font-size: 1.1rem; }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }

        /* Enhanced Card Design */
        .feature-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2.5rem 2rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: 100%;
            text-decoration: none;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 6px;
            background: var(--border);
            opacity: 0.5;
            transition: 0.3s;
        }

        /* Hover States tailored by card type for subtle variety */
        .card-iplm:hover::before { background: var(--primary); }
        .card-tkm:hover::before { background: #10b981; } /* Green/Teal for Reading */
        .card-aduan:hover::before { background: var(--accent); }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-float);
            border-color: transparent;
        }

        .icon-box {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: var(--bg-soft);
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1.75rem;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .icon-box { transform: scale(1.1) rotate(-5deg); color: #fff; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        
        .card-iplm:hover .icon-box { background: var(--primary); }
        .card-tkm:hover .icon-box { background: #10b981; }
        .card-aduan:hover .icon-box { background: var(--accent); }

        .feature-card h3 { font-size: 1.35rem; margin-bottom: 1rem; color: var(--text-main); }
        .feature-card p { color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 2.5rem; flex-grow: 1; }

        .card-footer {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text-main);
            padding-top: 1.5rem;
            border-top: 1px solid var(--bg-soft);
        }
        .card-footer i { 
            width: 32px; height: 32px; background: var(--bg-soft); border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; transition: 0.3s;
        }
        .feature-card:hover .card-footer i { background: var(--text-main); color: #fff; transform: translateX(5px); }

        /* --- Footer --- */
        .site-footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 0 0 2rem;
            margin-top: auto;
            position: relative;
            overflow: hidden;
            width: 100%;
        }
        
        .footer-wave {
            display: block;
            width: 100%;
            height: auto;
            color: #0f172a; /* Match footer background */
        }
        
        /* Subtle Footer Pattern using CSS instead of image for performance */
        .footer-bg {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: 0;
            background: radial-gradient(circle at 80% 20%, rgba(15, 82, 186, 0.15) 0%, transparent 50%);
            opacity: 0.6;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 1.5fr 0.8fr 1fr 1.5fr; /* 4 Columns: Brand, Links, Contact, Map */
            gap: 3rem;
            margin-bottom: 3rem;
            position: relative;
            z-index: 1;
            padding-top: 2rem;
        }

        .footer-brand h2 { color: #fff; font-size: 1.5rem; margin-bottom: 1.25rem; font-family: 'Plus Jakarta Sans', sans-serif; }
        .footer-brand p { font-size: 0.9rem; line-height: 1.7; opacity: 0.8; max-width: 300px; margin-bottom: 2rem; }

        .social-links { display: flex; gap: 12px; }
        .social-btn {
            width: 40px; height: 40px; border-radius: 10px;
            background: rgba(255,255,255,0.05); color: #fff;
            display: flex; align-items: center; justify-content: center;
            transition: 0.3s; border: 1px solid rgba(255,255,255,0.05);
            text-decoration: none;
        }
        .social-btn:hover { background: var(--primary); border-color: var(--primary); transform: translateY(-3px); color: #fff; }

        .footer-links h4 { color: #fff; font-size: 1rem; margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; }
        .footer-links ul { list-style: none; padding: 0; margin: 0; }
        .footer-links li { margin-bottom: 0.85rem; }
        .footer-links a { 
            color: #94a3b8; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; 
            transition: 0.2s;
        }
        .footer-links a:hover { color: #fff; padding-left: 5px; }
        .footer-links a i { font-size: 0.8rem; opacity: 0.5; }

        /* Small Map Styles */
        .footer-map-container {
            border-radius: 16px;
            overflow: hidden;
            height: 180px;
            width: 100%;
            position: relative;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .footer-map-container iframe {
            width: 100%; height: 100%; border: 0;
            filter: grayscale(100%) invert(92%) contrast(83%); /* Dark Mode Map Effect */
            transition: 0.3s;
        }
        .footer-map-container:hover iframe { filter: none; }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.08);
            padding-top: 2rem;
            text-align: center;
            font-size: 0.85rem;
            color: #64748b;
            position: relative;
            z-index: 1;
        }

        /* --- Mobile Responsive --- */
        @media (max-width: 768px) {
            .wrapper { padding: 0 16px; width: 100%; box-sizing: border-box; }
            .cards-grid { grid-template-columns: 1fr; gap: 1.25rem; width: 100%; }
            .hero { padding: 2.5rem 0; width: 100%; }
            .hero h1 { font-size: 2rem; line-height: 1.25; }
            .hero p { font-size: 0.95rem; margin-bottom: 2rem; }
            .stats-strip { 
                flex-direction: column; 
                gap: 1.25rem; 
                padding: 1.5rem; 
                margin-top: 2rem; 
                width: 100%; 
                max-width: none; 
            }
            .stat-item:not(:last-child)::after { display: none; }
            .stat-item { border-bottom: 1px solid var(--border); padding-bottom: 1rem; width: 100%; }
            .stat-item:last-child { border-bottom: none; padding-bottom: 0; }
            
            .footer-content { grid-template-columns: 1fr; gap: 2rem; }
            .nav-content { flex-direction: column; gap: 1rem; text-align: center; width: 100%; } 
            .brand { justify-content: center; width: 100%; }
            .nav-content .btn { width: 100%; justify-content: center; }
            .nav-content .d-flex { width: 100%; flex-direction: column; gap: 0.75rem; }
            .services-section { padding: 2.5rem 0 4rem; width: 100%; } 
        }

        @media (max-width: 480px) {
            .hero h1 { font-size: 1.85rem; }
            .feature-card { padding: 2rem 1.5rem; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>

    <?php include __DIR__ . '/config/loader.php'; ?>
    <div class="bg-pattern"></div>

    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="wrapper nav-content">
            <div class="brand">
                <div class="d-flex align-items-center gap-2">
                    <img src="assets/logo_lobar.png" alt="Logo Lobar">
                    <img src="assets/logo_disarpus.png" alt="Logo Disarpus">
                </div>
                <div class="brand-text">
                    <span class="brand-title">DISARPUS</span>
                    <span class="brand-subtitle">Kab. Lombok Barat</span>
                </div>
            </div>
            <div class="d-flex gap-2 gap-md-3">
                 <!-- Show on all screens, adjusting gap -->
                 <a href="#footer-map" class="btn btn-outline" style="padding: 0.6rem 1.25rem; font-size: 0.85rem;">
                    <i class="bi bi-geo-alt-fill"></i> Kunjungi Kami
                 </a>
                 <a href="pustakawan/form_pengaduan.php" class="btn btn-primary" style="padding: 0.6rem 1.25rem; font-size: 0.85rem;">
                    <i class="bi bi-chat-quote-fill"></i> Layanan Pengaduan
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero wrapper">
        <!-- Abstract Shapes -->
        <div class="shape-blob shape-1"></div>
        <div class="shape-blob shape-2"></div>
        
        <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
            <div class="badge"><i class="bi bi-patch-check-fill"></i> Portal Resmi Layanan Publik</div>
            <div class="badge bg-white shadow-sm border text-muted" style="text-transform: none; font-weight: 600;">
                <i class="bi bi-calendar3 text-primary"></i> <?= $tgl_sekarang ?>
            </div>
        </div>
        
        <h1>Transformasi Digital<br><span class="text-gradient">Literasi & Kearsipan</span></h1>
        
        <p>Akses terintegrasi untuk pendataan indeks literasi, tingkat kegemaran membaca, dan penyaluran aspirasi masyarakat demi Lombok Barat yang lebih cerdas.</p>
        
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="#layanan-utama" class="btn btn-primary">
                Akses Layanan <i class="bi bi-arrow-down-short"></i>
            </a>
            <a href="https://disarpus.lombokbaratkab.go.id/" target="_blank" class="btn btn-outline">
                Website Utama <i class="bi bi-box-arrow-up-right"></i>
            </a>
        </div>

        <div class="stats-strip">
            <div class="stat-item">
                <span class="stat-val count-up" data-target="<?= date('Y') ?>">0</span>
                <span class="stat-lbl">Tahun Periode</span>
            </div>
            <div class="stat-item">
                <span class="stat-val count-up" data-target="100">0</span><span style="position:absolute; top:4px; font-weight:800; color:var(--primary)">%</span>
                <span class="stat-lbl">Digitalisasi</span>
            </div>
            <div class="stat-item">
                <span class="stat-val">24/7</span>
                <span class="stat-lbl">Akses Layanan</span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="wrapper services-section" id="layanan-utama">
        <div class="section-header">
            <h2>Pusat Layanan Data</h2>
            <p>Pilih instrumen survei atau layanan yang Anda butuhkan</p>
        </div>

        <div class="cards-grid">
            <!-- IPLM -->
            <a href="pustakawan/pilih_perpustakaan.php?target=iplm" class="feature-card card-iplm">
                <div class="icon-box">
                    <i class="bi bi-bar-chart-line"></i>
                </div>
                <h3>Survei IPLM</h3>
                <p>Indeks Pembangunan Literasi Masyarakat. Instrumen strategis untuk mengukur kemajuan infrastruktur dan budaya literasi.</p>
                <div class="card-footer">
                    <span>Mulai Survei</span>
                    <i class="bi bi-arrow-right"></i>
                </div>
            </a>

            <!-- TKM -->
            <a href="pustakawan/kuisioner_tkm.php?target=tkm" class="feature-card card-tkm">
                <div class="icon-box">
                    <i class="bi bi-book-half"></i>
                </div>
                <h3>Survei TKM</h3>
                <p>Tingkat Kegemaran Membaca. Bantu kami memetakan preferensi dan kebiasaan membaca masyarakat Lombok Barat.</p>
                <div class="card-footer">
                    <span>Mulai Survei</span>
                    <i class="bi bi-arrow-right"></i>
                </div>
            </a>

            <!-- Pengaduan -->
            <a href="pustakawan/form_pengaduan.php" class="feature-card card-aduan">
                <div class="icon-box">
                    <i class="bi bi-envelope-open-heart"></i>
                </div>
                <h3>Kotak Aspirasi</h3>
                <p>Punya saran, kritik, atau keluhan? Suara Anda adalah fondasi utama kami dalam meningkatkan kualitas pelayanan publik.</p>
                <div class="card-footer">
                    <span>Kirim Pesan</span>
                    <i class="bi bi-arrow-right"></i>
                </div>
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer" id="footer-map">
        <!-- SVG Wave Divider for smooth transition -->
        <svg class="footer-wave" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" preserveAspectRatio="none" style="height: 80px; width: 100%; margin-bottom: -1px;">
            <path fill="#0f172a" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
        </svg>
        
        <div class="footer-bg"></div>

        <div class="wrapper">
            <div class="footer-content">
                <!-- Brand & Social -->
                <div class="footer-brand">
                    <h2>Disarpus Lobar</h2>
                    <p>Berkomitmen menghadirkan layanan kearsipan dan perpustakaan yang modern, inklusif, dan akuntabel.</p>
                    <div class="social-links">
                        <!-- Only FB & IG as requested -->
                        <a href="https://www.facebook.com/disarpuslobar#" target="_blank" rel="noopener noreferrer" class="social-btn"><i class="bi bi-facebook"></i></a>
                        <a href="https://www.instagram.com/disarpuslobar" target="_blank" rel="noopener noreferrer" class="social-btn"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="footer-links">
                    <h4>Akses</h4>
                    <ul>
                        <li><a href="index.php">Beranda</a></li>
                        <li><a href="pustakawan/pilih_perpustakaan.php?target=iplm">Survei IPLM</a></li>
                        <li><a href="pustakawan/kuisioner_tkm.php?target=tkm">Survei TKM</a></li>
                        <li><a href="pustakawan/form_pengaduan.php">Pengaduan</a></li>
                    </ul>
                </div>

                <!-- Contacts -->
                <div class="footer-links">
                    <h4>Kontak</h4>
                    <ul>
                        <li>
                            <i class="bi bi-telephone-fill me-2"></i> 
                            <span style="display:inline-block; width: 40px;">Telp.</span> (0370) 681239
                        </li>
                        <li>
                            <i class="bi bi-printer-fill me-2" style="visibility: hidden;"></i> <!-- Hidden icon for alignment -->
                            <span style="display:inline-block; width: 40px;">Fax.</span> (0370) 681250
                        </li>
                        <li><a href="mailto:disarpus@lombokbaratkab.go.id"><i class="bi bi-envelope-fill me-2"></i> disarpus@<br>lombokbaratkab.go.id</a></li>
                        <li><i class="bi bi-mailbox2 me-2"></i> Kode Pos: 83363</li>
                    </ul>
                </div>

                <!-- Google Map (Right Column) -->
                <div class="footer-links">
                    <h4>Lokasi Kami</h4>
                    <div class="footer-map-container">
                        <iframe src="https://maps.google.com/maps?q=Perpustakaan+Daerah+Lombok+Barat&t=&z=15&ie=UTF8&iwloc=&output=embed" loading="lazy"></iframe>
                    </div>
                    <div style="margin-top: 10px; font-size: 0.85rem; color: #94a3b8; display: flex; align-items: start; gap: 6px;">
                        <i class="bi bi-geo-alt" style="margin-top: 3px;"></i> Jln. Raya BIL KM 21 Gerung
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                &copy; <?= date('Y') ?> Dinas Kearsipan dan Perpustakaan Kabupaten Lombok Barat. All Rights Reserved.
            </div>
        </div>
    </footer>

    <script src="assets/loader.js"></script>
    
    <!-- Interactions Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Navbar Scroll Effect
            const navbar = document.getElementById('navbar');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            // Card Animations
            const cards = document.querySelectorAll('.feature-card');
            cards.forEach((card, i) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s cubic-bezier(0.25, 0.8, 0.25, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 200 + (i * 100));
            });

            // Counters Animation
            const counters = document.querySelectorAll('.count-up');
            const speed = 50; // Faster animation (lower = faster)

            const animateCount = (counter) => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText;
                const inc = Math.max(1, target / speed);

                if (count < target) {
                    counter.innerText = Math.ceil(count + inc);
                    setTimeout(() => animateCount(counter), 20);
                } else {
                    counter.innerText = target;
                }
            };

            // Trigger counters when in view
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const counter = entry.target;
                        animateCount(counter);
                        observer.unobserve(counter);
                    }
                });
            }, { threshold: 0.5 });

            counters.forEach(counter => observer.observe(counter));
        });
    </script>
</body>
</html>
