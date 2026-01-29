<?php
// web-perpus-v1/pustakawan/kuisioner_tkm.php
$library_id = isset($_GET['library_id']) ? $_GET['library_id'] : '';

// --- DATA REFRENSI SESUAI PDF (Halaman 1 & 2) ---

// III. PRA MEMBACA
$soal_pra_membaca = [
    "Saya membaca buku karena saya merasa senang saat membaca",
    "Saya membaca buku yang menarik minat saya tanpa paksaan orang lain",
    "Saya membaca untuk mencapai tujuan tertentu (misal: menambah pengetahuan, mengerjakan tugas)",
    "Saya membaca untuk memahami informasi penting sebelum mengambil keputusan",
    "Saya percaya bahwa saya dapat memahami teks walaupun topiknya baru bagi saya",
    "Saya merasa percaya diri saat menceritakan kembali isi bacaan",
    "Saya membaca karena didorong oleh orang lain",
    "Saya membaca untuk memenuhi tugas atau kewajiban",
    "Saya memiliki koleksi buku di rumah yang selalu tersedia untuk dibaca.",
    "Saya dapat mengunduh e-book dengan mudah dari internet kalau mau membaca.",
    "Saya memiliki pencahayaan yang cukup saat membaca",
    "Saya mudah menemukan tempat yang nyaman untuk membaca",
    "Saya sering melihat anggota keluarga membaca sehingga saya terinspirasi membaca",
    "Saya dan keluarga sering membaca bersama",
    "Tokoh publik (Influencer, politisi, duta baca, tokoh masyarakat, dll.) merekomendasikan bacaan yang sesuai dengan minat saya",
    "Tokoh publik (Influencer, politisi, duta baca, tokoh masyarakat, dll.) memberikan dorongan dan arahan kepada saya agar saya membaca",
    "Saya sering berdiskusi dengan teman tentang buku yang kami baca",
    "Teman saya memberi saya rekomendasi buku yang menarik",
    "Saya mengikuti kegiatan literasi (misal: klub buku, workshop membaca)",
    "Saya sering berpartisipasi dalam tantangan membaca (reading challenge)",
    "Orang di lingkungan saya menghargai kebiasaan membaca",
    "Media lokal mempromosikan kegiatan membaca",
    "Orang-orang di lingkungan saya sering membaca",
    "Acara budaya di tempat saya sering melibatkan kegiatan membaca",
    "Saya menjadi anggota/pengurus organisasi pembaca/literasi",
    "Saya membantu penyelenggaraan acara literasi di lingkungan saya",
    "Saya menggunakan perpustakaan digital untuk membaca materi budaya",
    "Saya mengikuti tur atau acara budaya yang melibatkan kegiatan literasi"
];

// IV. SAAT MEMBACA
$soal_saat_membaca = [
    "Saya dapat mempertahankan fokus saat membaca hingga selesai",
    "Saya memilih tempat tenang untuk membaca",
    "Saya membuat catatan atau menandai bagian penting dalam teks",
    "Saya membuat ringkasan setelah selesai membaca",
    "Saya sering bertanya pada diri sendiri untuk memeriksa pemahaman",
    "Saya berdiskusi tentang teks yang saya baca dengan orang lain setelah membaca",
    "Saya dapat menilai diri sendiri tingkat pemahaman saya tentang isi bacaan yang saya baca",
    "Saya meminta bantuan orang lain jika menemukan bagian sulit",
    "Saya tertarik membaca opini/pendapat orang lain tentang isi sebuah buku",
    "Saya tertarik untuk bertukar pikiran terkait buku yang saya baca dengan orang lain",
    "Saya suka membaca bersama teman atau kelompok",
    "Saya merencanakan waktu membaca bersama",
    "Saya membahas arti kata sulit dalam teks bersama teman",
    "Saya mencatat dan mencari arti kata sulit dalam kamus/ensiklopedia",
    "Saya menggunakan bacaan untuk memecahkan masalah sehari-hari",
    "Saya menulis laporan berdasarkan hasil baca"
];

// V. PASCA MEMBACA
$soal_pasca_membaca = [
    "Setelah membaca, saya merasa pengetahuan saya bertambah",
    "Saya merasa menjadi lebih senang setelah saya membaca",
    "Membaca membantu saya berkomunikasi lebih baik dengan orang lain",
    "Saya membaca untuk memahami perspektif orang lain",
    "Membaca membuat saya lebih memahami perasaan diri sendiri atau tokoh",
    "Saya termotivasi setelah membaca cerita inspiratif",
    "Saya percaya mampu meningkatkan kemampuan membaca",
    "Saya percaya diri saat membaca topik baru",
    "Saya menganggap membaca sebagai kegiatan bernilai dan bermanfaat",
    "Saya bersedia meluangkan waktu membaca setiap hari"
];

// VI. INTERAKSI PERPUSTAKAAN
$soal_interaksi = [
    "Saya datang ke perpustakaan untuk mencari informasi terpercaya",
    "Saya mencari bahan bacaan di perpustakaan untuk minat pribadi",
    "Saya datang ke perpustakaan setiap bulan selama 3 bulan terakhir",
    "Saya menggunakan fasilitas perpustakaan, baik berupa bahan bacaan atau fasilitas lainnya secara langsung lebih dari satu kali dalam 3 bulan terakhir",
    "Pustakawan/pengelola perpustakaan membantu saya menemukan bahan bacaan yang sesuai",
    "Saya merasa nyaman bertanya kepada pustakawan/pengelola perpustakaan",
    "Saya melihat katalog online untuk menemukan materi",
    "Saya meminjam atau mengunduh buku dari koleksi perpustakaan",
    "Saya membagikan informasi perpustakaan kepada teman/rekan kerja",
    "Saya mengintegrasikan bacaan perpustakaan dalam presentasi",
    "Saya sering merekomendasikan buku atau sumber kepada keluarga, teman, atau rekan kerja",
    "Saya menulis ulasan singkat tentang buku yang saya baca",
    "Saya puas dengan kualitas layanan perpustakaan yang saya gunakan",
    "Saya akan merekomendasikan perpustakaan ini kepada orang lain"
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kuesioner TKM - Tahun 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #ffffff; font-family: sans-serif; }
        .sidebar { width: 250px; background-color: #d9d9d9; min-height: 100vh; border-right: 1px solid #000; padding: 30px; position: fixed; top: 0; left: 0; }
        .sidebar h2 { font-weight: bold; margin-bottom: 50px; text-align: center; }
        .nav-link { color: #000; font-size: 18px; margin-bottom: 15px; text-decoration: none; display: block; padding: 5px 10px; }
        .nav-link:hover { font-weight: bold; }
        .main-content { margin-left: 250px; padding: 40px; }
        .form-section { background-color: #d9d9d9; border: 1px solid #000; border-radius: 8px; padding: 25px; margin-bottom: 30px; }
        .section-title { font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .likert-row { border-bottom: 1px solid #999; padding: 15px 0; }
        .likert-options label { margin-right: 20px; font-weight: normal; cursor: pointer; }
        .btn-submit { background-color: #000; color: #fff; border: none; padding: 10px 40px; border-radius: 8px; font-weight: bold; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <h2>Logo</h2>
        <a href="dashboard.php?library_id=<?= $library_id ?>" class="nav-link">Beranda</a>
        <a href="kuisioner_iplm.php?library_id=<?= $library_id ?>" class="nav-link">Kuisioner IPLM</a>
        <a href="#" class="nav-link fw-bold">Kuisioner TKM</a>
        <a href="riwayat.php?library_id=<?= $library_id ?>" class="nav-link">Riwayat</a>
        <a href="profil.php?library_id=<?= $library_id ?>" class="nav-link">Profil</a>
    </nav>

    <main class="main-content">
        <h3 class="mb-4">TINGKAT KEGEMARAN MEMBACA (TKM) TAHUN 2025</h3>

        <form method="POST" action="proses_tkm.php">

            <div class="form-section">
                <h5 class="section-title">I. DATA DEMOGRAFI</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Usia</label>
                        <select name="usia" class="form-select">
                            <option>15 - 28 Tahun</option>
                            <option>29 - 42 Tahun</option>
                            <option>43 - 56 Tahun</option>
                            <option>Lebih dari 56 Tahun</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="gender" class="form-select">
                            <option>Laki-laki</option>
                            <option>Perempuan</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Pendidikan Terakhir</label>
                        <select name="pendidikan" class="form-select">
                            <option>SD Tidak Tamat</option>
                            <option>SD/MI</option>
                            <option>SMP/MTs</option>
                            <option>SMA/SMK/MA</option>
                            <option>Diploma-D1/D2/D3</option>
                            <option>Sarjana-D4/S1</option>
                            <option>Magister-S2</option>
                            <option>Doktor-S3</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Pekerjaan</label>
                        <select name="pekerjaan" class="form-select">
                            <option>Pelajar SMP/MTs</option>
                            <option>Pelajar SMA/SMK/MA</option>
                            <option>Mahasiswa</option>
                            <option>Pegawai Negeri (ASN) (Selain Guru atau Dosen)</option>
                            <option>Pegawai Swasta</option>
                            <option>Pengusaha/Pedagang</option>
                            <option>Pegawai Honorer</option>
                            <option>Pegawai BUMN</option>
                            <option>Anggota TNI/Polri</option>
                            <option>Dosen/Guru (Negeri/Swasta)</option>
                            <option>Buruh (Pabrik, Penjaga Toko, Konstruksi, dll)</option>
                            <option>Petani/Nelayan</option>
                            <option>Lainnya</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Provinsi Asal</label>
                        <input type="text" name="provinsi" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kabupaten/Kota Asal</label>
                        <input type="text" name="kota" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h5 class="section-title">II. PERTANYAAN TAMBAHAN</h5>
                
                <div class="mb-3">
                    <label class="fw-bold d-block mb-2">Saya memiliki/menyediakan waktu khusus untuk membaca</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="waktu_khusus" value="Ya">
                        <label class="form-check-label">Ya</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="waktu_khusus" value="Tidak">
                        <label class="form-check-label">Tidak</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="fw-bold d-block mb-2">Tujuan membaca bagi saya adalah (Boleh pilih lebih dari satu)</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="tujuan[]" value="Menambah pengetahuan">
                        <label class="form-check-label">Untuk menambah pengetahuan</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="tujuan[]" value="Mencari rujukan">
                        <label class="form-check-label">Mencari rujukan untuk mengerjakan tugas sekolah/kantor</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="tujuan[]" value="Kesenangan">
                        <label class="form-check-label">Untuk kesenangan</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="tujuan[]" value="Waktu luang">
                        <label class="form-check-label">Untuk menghabiskan waktu luang</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Berapa jarak rumah anda ke perpustakaan terdekat</label>
                    <select name="jarak_perpus" class="form-select mt-2">
                        <option>Kurang dari 1 KM</option>
                        <option>2 - 3 KM</option>
                        <option>4 - 5 KM</option>
                        <option>Lebih dari 5 KM</option>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <h5 class="section-title">III. PRA MEMBACA</h5>
                
                <?php foreach($soal_pra_membaca as $i => $soal): ?>
                <div class="likert-row">
                    <p class="mb-2"><?= ($i+1) . ". " . $soal ?></p>
                    <div class="likert-options">
                        <label><input type="radio" name="pra_<?= $i ?>" value="1"> STS (Sangat Tidak Setuju)</label>
                        <label><input type="radio" name="pra_<?= $i ?>" value="2"> TS (Tidak Setuju)</label>
                        <label><input type="radio" name="pra_<?= $i ?>" value="3"> S (Setuju)</label>
                        <label><input type="radio" name="pra_<?= $i ?>" value="4"> SS (Sangat Setuju)</label>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="mt-4 p-3 border rounded bg-light">
                    <div class="mb-3">
                        <label class="fw-bold">Dalam sebulan terakhir, saya membaca buku tercetak sebanyak... diluar kebutuhan tugas</label>
                        <select name="freq_buku_cetak" class="form-select">
                            <option>Kurang dari 3 buku</option>
                            <option>4-6 buku</option>
                            <option>7-9 buku</option>
                            <option>Lebih dari 9 buku</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold">Dalam sebulan terakhir, saya membaca buku digital sebanyak... diluar kebutuhan tugas</label>
                        <select name="freq_buku_digital" class="form-select">
                            <option>Kurang dari 3 buku</option>
                            <option>4-6 buku</option>
                            <option>7-9 buku</option>
                            <option>Lebih dari 9 buku</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold">Saya biasanya membaca buku tercetak selama</label>
                        <select name="durasi_cetak" class="form-select">
                            <option>Kurang dari 10 menit</option>
                            <option>10-20 menit</option>
                            <option>20-40 menit</option>
                            <option>Lebih dari 40 menit</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="fw-bold">Saya biasanya membaca buku digital selama</label>
                        <select name="durasi_digital" class="form-select">
                            <option>Kurang dari 10 menit</option>
                            <option>10-20 menit</option>
                            <option>20-40 menit</option>
                            <option>Lebih dari 40 menit</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h5 class="section-title">IV. SAAT MEMBACA</h5>
                <?php foreach($soal_saat_membaca as $i => $soal): ?>
                <div class="likert-row">
                    <p class="mb-2"><?= ($i+1) . ". " . $soal ?></p>
                    <div class="likert-options">
                        <label><input type="radio" name="saat_<?= $i ?>" value="1"> STS</label>
                        <label><input type="radio" name="saat_<?= $i ?>" value="2"> TS</label>
                        <label><input type="radio" name="saat_<?= $i ?>" value="3"> S</label>
                        <label><input type="radio" name="saat_<?= $i ?>" value="4"> SS</label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="form-section">
                <h5 class="section-title">V. PASCA MEMBACA</h5>
                <?php foreach($soal_pasca_membaca as $i => $soal): ?>
                <div class="likert-row">
                    <p class="mb-2"><?= ($i+1) . ". " . $soal ?></p>
                    <div class="likert-options">
                        <label><input type="radio" name="pasca_<?= $i ?>" value="1"> STS</label>
                        <label><input type="radio" name="pasca_<?= $i ?>" value="2"> TS</label>
                        <label><input type="radio" name="pasca_<?= $i ?>" value="3"> S</label>
                        <label><input type="radio" name="pasca_<?= $i ?>" value="4"> SS</label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="form-section">
                <h5 class="section-title">VI. INTERAKSI DENGAN PERPUSTAKAAN</h5>
                <?php foreach($soal_interaksi as $i => $soal): ?>
                <div class="likert-row">
                    <p class="mb-2"><?= ($i+1) . ". " . $soal ?></p>
                    <div class="likert-options">
                        <label><input type="radio" name="interaksi_<?= $i ?>" value="1"> STS</label>
                        <label><input type="radio" name="interaksi_<?= $i ?>" value="2"> TS</label>
                        <label><input type="radio" name="interaksi_<?= $i ?>" value="3"> S</label>
                        <label><input type="radio" name="interaksi_<?= $i ?>" value="4"> SS</label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="text-end mb-5">
                <button type="submit" class="btn-submit">Kirim Kuesioner TKM</button>
            </div>

        </form>
    </main>
</body>
</html>