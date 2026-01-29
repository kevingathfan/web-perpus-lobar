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
            background-image: linear-gradient(
                    to bottom,
                    #000 0px,
                    #000 1px,
                    transparent 1px,
                    transparent 4px,
                    #000 4px,
                    #000 5px
                );
    background-repeat: no-repeat;
    background-size: 100% 5px;
    background-position: bottom;
        }

        .header-text {
            text-align: center;
            flex-grow: 1;
        }

        .header-text h2 {
            font-size: 26px;
            font-weight: 600;
            margin: 0;
        }

        .logo-img {
            height: 150px;
            width: 150px;
            object-fit: contain
        }

        /* ================= INFO BOX ================= */
        .info-box {
            max-width: 1000px;
            background: #fff;
            border: 1px solid #000;
            border-radius: 15px;
            padding: 40px;
            margin: 40px;
            box-shadow: 0px 4px 6px rgba(0,0,0,0.05);
        }

        .title-section {
            margin-bottom: 40px;
            text-align: center;
        }

        .title-section h4 {
            font-size: 18px;
            line-height: 1.6;
            margin: 0;
            font-weight: 500;
        }

        .footer-section {
            margin-top: 50px;
        }

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

        .card-menu h3 {
            font-size: 20px;
            font-weight: 500;
            line-height: 1.4;
            margin: 0;
        }

        /* Aksen biru kiri */
        .card-menu::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background-color: #0d6efd;
            transform: scaleY(0);
            transform-origin: top;
            transition: transform 0.3s ease;
        }

        /* Hover Effect */
        .card-menu:hover {
            transform: translateY(-7px);
            box-shadow: 0 18px 32px rgba(0, 0, 0, 0.18);
        }

        .card-menu:hover::before {
            transform: scaleY(1);
        }

    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="header-box">
        <img src="assets/logo_lobar.png" alt="Logo" class="logo-img">

        <div class="header-text">
            <h2>Dinas Kearsipan dan Perpustakaan</h2>
            <p class="mb-0">Kabupaten Lombok Barat</p>
        </div>

        <img src="assets/logo_disarpus.png" alt="Logo" class="logo-img">
    </div>

    <!-- TITLE -->
    <div class="info-box title-section">
        <h4>
            Kuisioner Pengukuran Indeks Pembangunan Literasi Masyarakat
            dan Kegemaran Membaca Kabupaten Lombok Barat
        </h4>
    </div>

    <div class="mb-4 fs-5">Pilih Kategori Kuisioner</div>

    <!-- MENU -->
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
        </div>
    </div>

    <!-- FOOTER -->
    <div class="info-box footer-section">
        <h6 class="fw-bold">Mengapa kuisioner ini penting?</h6>
        <p class="text-muted small mb-0">
            Data yang Anda isikan sangat berharga untuk mengukur kemajuan literasi di daerah kita.
            Hasil survei ini akan digunakan sebagai dasar pengambilan kebijakan pengembangan
            perpustakaan di masa depan.
        </p>
    </div>

    <div class="mt-5 text-center">
        <a href="admin/login.php" class="text-muted text-decoration-none small">
            Masuk sebagai Admin Dinas →
        </a>
    </div>

</body>
</html>
