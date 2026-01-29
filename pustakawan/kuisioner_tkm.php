<?php
// web-perpus-v1/pustakawan/kuisioner_tkm.php
$library_id = isset($_GET['library_id']) ? $_GET['library_id'] : '';

// --- LOGIKA TANGGAL ---
$bulan_indo = ['01' => 'JANUARI', '02' => 'FEBRUARI', '03' => 'MARET', '04' => 'APRIL', '05' => 'MEI', '06' => 'JUNI', '07' => 'JULI', '08' => 'AGUSTUS', '09' => 'SEPTEMBER', '10' => 'OKTOBER', '11' => 'NOVEMBER', '12' => 'DESEMBER'];
$tahun_sekarang = date('Y');
$bulan_sekarang = $bulan_indo[date('m')];

// --- ARRAY PERTANYAAN ---
$soal_pra_membaca = ["Saya membaca buku karena saya merasa senang saat membaca", "Saya membaca buku yang menarik minat saya tanpa paksaan orang lain", "Saya membaca untuk mencapai tujuan tertentu (misal: menambah pengetahuan, mengerjakan tugas)", "Saya membaca untuk memahami informasi penting sebelum mengambil keputusan", "Saya percaya bahwa saya dapat memahami teks walaupun topiknya baru bagi saya", "Saya merasa percaya diri saat menceritakan kembali isi bacaan", "Saya membaca karena didorong oleh orang lain", "Saya membaca untuk memenuhi tugas atau kewajiban", "Saya memiliki koleksi buku di rumah yang selalu tersedia untuk dibaca.", "Saya dapat mengunduh e-book dengan mudah dari internet kalau mau membaca.", "Saya memiliki pencahayaan yang cukup saat membaca", "Saya mudah menemukan tempat yang nyaman untuk membaca", "Saya sering melihat anggota keluarga membaca sehingga saya terinspirasi membaca", "Saya dan keluarga sering membaca bersama", "Tokoh publik (Influencer, politisi, duta baca, tokoh masyarakat, dll.) merekomendasikan bacaan yang sesuai dengan minat saya", "Tokoh publik (Influencer, politisi, duta baca, tokoh masyarakat, dll.) memberikan dorongan dan arahan kepada saya agar saya membaca", "Saya sering berdiskusi dengan teman tentang buku yang kami baca", "Teman saya memberi saya rekomendasi buku yang menarik", "Saya mengikuti kegiatan literasi (misal: klub buku, workshop membaca)", "Saya sering berpartisipasi dalam tantangan membaca (reading challenge)", "Orang di lingkungan saya menghargai kebiasaan membaca", "Media lokal mempromosikan kegiatan membaca", "Orang-orang di lingkungan saya sering membaca", "Acara budaya di tempat saya sering melibatkan kegiatan membaca", "Saya menjadi anggota/pengurus organisasi pembaca/literasi", "Saya membantu penyelenggaraan acara literasi di lingkungan saya", "Saya menggunakan perpustakaan digital untuk membaca materi budaya", "Saya mengikuti tur atau acara budaya yang melibatkan kegiatan literasi"];
$soal_saat_membaca = ["Saya dapat mempertahankan fokus saat membaca hingga selesai", "Saya memilih tempat tenang untuk membaca", "Saya membuat catatan atau menandai bagian penting dalam teks", "Saya membuat ringkasan setelah selesai membaca", "Saya sering bertanya pada diri sendiri untuk memeriksa pemahaman", "Saya berdiskusi tentang teks yang saya baca dengan orang lain setelah membaca", "Saya dapat menilai diri sendiri tingkat pemahaman saya tentang isi bacaan yang saya baca", "Saya meminta bantuan orang lain jika menemukan bagian sulit", "Saya tertarik membaca opini/pendapat orang lain tentang isi sebuah buku", "Saya tertarik untuk bertukar pikiran terkait buku yang saya baca dengan orang lain", "Saya suka membaca bersama teman atau kelompok", "Saya merencanakan waktu membaca bersama", "Saya membahas arti kata sulit dalam teks bersama teman", "Saya mencatat dan mencari arti kata sulit dalam kamus/ensiklopedia", "Saya menggunakan bacaan untuk memecahkan masalah sehari-hari", "Saya menulis laporan berdasarkan hasil baca"];
$soal_pasca_membaca = ["Setelah membaca, saya merasa pengetahuan saya bertambah", "Saya merasa menjadi lebih senang setelah saya membaca", "Membaca membantu saya berkomunikasi lebih baik dengan orang lain", "Saya membaca untuk memahami perspektif orang lain", "Membaca membuat saya lebih memahami perasaan diri sendiri atau tokoh", "Saya termotivasi setelah membaca cerita inspiratif", "Saya percaya mampu meningkatkan kemampuan membaca", "Saya percaya diri saat membaca topik baru", "Saya menganggap membaca sebagai kegiatan bernilai dan bermanfaat", "Saya bersedia meluangkan waktu membaca setiap hari"];
$soal_interaksi = ["Saya datang ke perpustakaan untuk mencari informasi terpercaya", "Saya mencari bahan bacaan di perpustakaan untuk minat pribadi", "Saya datang ke perpustakaan setiap bulan selama 3 bulan terakhir", "Saya menggunakan fasilitas perpustakaan, baik berupa bahan bacaan atau fasilitas lainnya secara langsung lebih dari satu kali dalam 3 bulan terakhir", "Pustakawan/pengelola perpustakaan membantu saya menemukan bahan bacaan yang sesuai", "Saya merasa nyaman bertanya kepada pustakawan/pengelola perpustakaan", "Saya melihat katalog online untuk menemukan materi", "Saya meminjam atau mengunduh buku dari koleksi perpustakaan", "Saya membagikan informasi perpustakaan kepada teman/rekan kerja", "Saya mengintegrasikan bacaan perpustakaan dalam presentasi", "Saya sering merekomendasikan buku atau sumber kepada keluarga, teman, atau rekan kerja", "Saya menulis ulasan singkat tentang buku yang saya baca", "Saya puas dengan kualitas layanan perpustakaan yang saya gunakan", "Saya akan merekomendasikan perpustakaan ini kepada orang lain"];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuesioner TKM - <?= $bulan_sekarang ?> <?= $tahun_sekarang ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            background-color: #f8f9fa; 
            font-family: 'Poppins', sans-serif; 
            color: #333;
            padding-top: 20px;
        }

        /* 1. GARIS PALING ATAS */
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 10px;
            background-color: #000;
            z-index: 9999;
        }
        
        .container-form { 
            max-width: 1000px; margin: 40px auto; padding: 40px; 
            background-color: #ffffff; border-radius: 15px; border: 1px solid #e0e0e0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        /* 2. HEADER FLEXBOX (Logo Kiri - Judul - Logo Kanan) */
        .header-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 40px;
            padding-bottom: 20px;
            gap: 20px;
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
        
        .header-logo {
            width: 150px;
            height:150px;
            object-fit: contain;
        }

        .header-title-box {
            flex: 1;
            text-align: center;
        }
        
        .header-title-box h3 {
            font-weight: 700;
            margin: 0;
            color: #000;
            font-size: 22px;
            line-height: 1.4;
        }
        
        .header-subtitle {
            font-size: 16px;
            font-weight: 500;
            display: block;
            margin-top: 5px;
        }

        .form-section { 
            background-color: #ffffff; border: 1px solid #333; border-radius: 10px; 
            padding: 30px; margin-bottom: 30px; 
        }
        
        .section-title { 
            font-weight: 600; font-size: 18px; margin-bottom: 25px; color: #000;
            text-transform: uppercase; border-bottom: 1px dashed #ccc; padding-bottom: 10px;
        }
        
        .form-label { font-weight: 500; font-size: 14px; margin-bottom: 8px; color: #444; }
        
        .form-control, .form-select { 
            border: 1px solid #aaa; border-radius: 6px; padding: 10px; text-transform: uppercase; 
        }
        .form-control:focus, .form-select:focus { border-color: #000; box-shadow: 0 0 0 3px rgba(0,0,0,0.1); }
        
        .likert-row { border-bottom: 1px solid #eee; padding: 15px 0; }
        .likert-row:last-child { border-bottom: none; }
        .likert-row p { font-weight: 500; margin-bottom: 8px; }
        .likert-options label { 
            margin-right: 25px; cursor: pointer; font-size: 14px;
        }
        .likert-options input[type="radio"] { margin-right: 5px; }

        .legend-box {
            background-color: #ffffff; border: 1px solid #333; color: #333;
            padding: 15px; border-radius: 6px; margin-bottom: 25px;
            font-size: 13px; text-align: center;
        }
        .legend-item { display: inline-block; margin: 0 10px; }
        .legend-code {
            background: #000; color: #fff; padding: 2px 8px; 
            border-radius: 4px; margin-right: 6px; font-weight: bold;
        }

        .btn-submit { 
            background-color: #000; color: #fff; border: none; padding: 15px 50px; 
            border-radius: 8px; font-weight: 600; font-size: 16px; width: 100%; transition: all 0.3s;
        }
        .btn-submit:hover { background-color: #333; transform: translateY(-2px); }

        .back-link { display: inline-block; margin-bottom: 20px; color: #666; text-decoration: none; font-weight: 500; }
        .back-link:hover { color: #000; }
        
        #input_pekerjaan_lainnya { transition: all 0.3s ease-in-out; }
    </style>
</head>
<body>

    <div class="container-form">
        <a href="../index.php?library_id=<?= $library_id ?>" class="back-link">
            &larr; Kembali ke Beranda
        </a>

        <div class="header-wrapper">
            <img src="../assets/logo_lobar.png" alt="Logo Kiri" class="header-logo">
            
            <div class="header-title-box">
                <h3>TINGKAT KEGEMARAN MEMBACA (TKM)</h3>
                <span class="header-subtitle">TAHUN <?= $tahun_sekarang ?> PERIODE <?= $bulan_sekarang ?></span>
            </div>

            <img src="../assets/logo_disarpus.png" alt="Logo Kanan" class="header-logo">
        </div>

        <form method="POST" action="proses_tkm.php">

            <div class="form-section">
                <div class="section-title">I. DATA DEMOGRAFI</div>
                <div class="row g-4">
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
                        <select name="pekerjaan" id="select_pekerjaan" class="form-select">
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
                            <option value="Lainnya">Lainnya</option>
                        </select>
                        <input type="text" name="pekerjaan_lainnya" id="input_pekerjaan_lainnya" class="form-control mt-2" placeholder="SEBUTKAN PEKERJAAN ANDA..." style="display: none;">
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
                <div class="section-title">II. PERTANYAAN TAMBAHAN</div>
                
                <div class="mb-3">
                    <label class="form-label d-block mb-2">Saya memiliki/menyediakan waktu khusus untuk membaca</label>
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
                    <label class="form-label d-block mb-2">Tujuan membaca bagi saya adalah (Boleh pilih lebih dari satu)</label>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="tujuan[]" value="Menambah pengetahuan"><label class="form-check-label">Menambah pengetahuan</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="tujuan[]" value="Mencari rujukan"><label class="form-check-label">Mencari rujukan tugas</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="tujuan[]" value="Kesenangan"><label class="form-check-label">Kesenangan</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="tujuan[]" value="Waktu luang"><label class="form-check-label">Mengisi waktu luang</label></div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Berapa jarak rumah anda ke perpustakaan terdekat</label>
                    <select name="jarak_perpus" class="form-select mt-2">
                        <option>Kurang dari 1 KM</option>
                        <option>2 - 3 KM</option>
                        <option>4 - 5 KM</option>
                        <option>Lebih dari 5 KM</option>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <div class="section-title">III. PRA MEMBACA</div>
                
                <div class="legend-box">
                    <span class="legend-item"><span class="legend-code">STS</span> Sangat Tidak Setuju</span>
                    <span class="legend-item"><span class="legend-code">TS</span> Tidak Setuju</span>
                    <span class="legend-item"><span class="legend-code">S</span> Setuju</span>
                    <span class="legend-item"><span class="legend-code">SS</span> Sangat Setuju</span>
                </div>

                <?php foreach($soal_pra_membaca as $i => $soal): ?>
                <div class="likert-row">
                    <p><?= ($i+1) . ". " . $soal ?></p>
                    <div class="likert-options">
                        <label><input type="radio" name="pra_<?= $i ?>" value="1"> STS</label>
                        <label><input type="radio" name="pra_<?= $i ?>" value="2"> TS</label>
                        <label><input type="radio" name="pra_<?= $i ?>" value="3"> S</label>
                        <label><input type="radio" name="pra_<?= $i ?>" value="4"> SS</label>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="mt-4 p-3 border rounded bg-light">
                    <div class="mb-3"><label class="form-label">Frekuensi membaca buku TERCETAK (per bulan)</label><select name="freq_buku_cetak" class="form-select"><option>Kurang dari 3 buku</option><option>4-6 buku</option><option>7-9 buku</option><option>Lebih dari 9 buku</option></select></div>
                    <div class="mb-3"><label class="form-label">Frekuensi membaca buku DIGITAL (per bulan)</label><select name="freq_buku_digital" class="form-select"><option>Kurang dari 3 buku</option><option>4-6 buku</option><option>7-9 buku</option><option>Lebih dari 9 buku</option></select></div>
                    <div class="mb-3"><label class="form-label">Durasi membaca buku TERCETAK</label><select name="durasi_cetak" class="form-select"><option>Kurang dari 10 menit</option><option>10-20 menit</option><option>20-40 menit</option><option>Lebih dari 40 menit</option></select></div>
                    <div class="mb-3"><label class="form-label">Durasi membaca buku DIGITAL</label><select name="durasi_digital" class="form-select"><option>Kurang dari 10 menit</option><option>10-20 menit</option><option>20-40 menit</option><option>Lebih dari 40 menit</option></select></div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-title">IV. SAAT MEMBACA</div>
                <div class="legend-box">
                    <span class="legend-item"><span class="legend-code">STS</span> Sangat Tidak Setuju</span>
                    <span class="legend-item"><span class="legend-code">TS</span> Tidak Setuju</span>
                    <span class="legend-item"><span class="legend-code">S</span> Setuju</span>
                    <span class="legend-item"><span class="legend-code">SS</span> Sangat Setuju</span>
                </div>
                <?php foreach($soal_saat_membaca as $i => $soal): ?>
                <div class="likert-row">
                    <p><?= ($i+1) . ". " . $soal ?></p>
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
                <div class="section-title">V. PASCA MEMBACA</div>
                <div class="legend-box">
                    <span class="legend-item"><span class="legend-code">STS</span> Sangat Tidak Setuju</span>
                    <span class="legend-item"><span class="legend-code">TS</span> Tidak Setuju</span>
                    <span class="legend-item"><span class="legend-code">S</span> Setuju</span>
                    <span class="legend-item"><span class="legend-code">SS</span> Sangat Setuju</span>
                </div>
                <?php foreach($soal_pasca_membaca as $i => $soal): ?>
                <div class="likert-row">
                    <p><?= ($i+1) . ". " . $soal ?></p>
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
                <div class="section-title">VI. INTERAKSI DENGAN PERPUSTAKAAN</div>
                <div class="legend-box">
                    <span class="legend-item"><span class="legend-code">STS</span> Sangat Tidak Setuju</span>
                    <span class="legend-item"><span class="legend-code">TS</span> Tidak Setuju</span>
                    <span class="legend-item"><span class="legend-code">S</span> Setuju</span>
                    <span class="legend-item"><span class="legend-code">SS</span> Sangat Setuju</span>
                </div>
                <?php foreach($soal_interaksi as $i => $soal): ?>
                <div class="likert-row">
                    <p><?= ($i+1) . ". " . $soal ?></p>
                    <div class="likert-options">
                        <label><input type="radio" name="interaksi_<?= $i ?>" value="1"> STS</label>
                        <label><input type="radio" name="interaksi_<?= $i ?>" value="2"> TS</label>
                        <label><input type="radio" name="interaksi_<?= $i ?>" value="3"> S</label>
                        <label><input type="radio" name="interaksi_<?= $i ?>" value="4"> SS</label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="mb-5">
                <button type="submit" class="btn-submit">KIRIM KUESIONER TKM &rarr;</button>
            </div>

        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Toggle Pekerjaan
            const selectPekerjaan = document.getElementById('select_pekerjaan');
            const inputLainnya = document.getElementById('input_pekerjaan_lainnya');

            if(selectPekerjaan){
                selectPekerjaan.addEventListener('change', function() {
                    if (this.value === 'Lainnya') {
                        inputLainnya.style.display = 'block';
                        inputLainnya.required = true;
                        inputLainnya.focus();
                    } else {
                        inputLainnya.style.display = 'none';
                        inputLainnya.required = false;
                        inputLainnya.value = '';
                    }
                });
            }

            // 2. Auto Uppercase
            const textInputs = document.querySelectorAll('input[type="text"], textarea');
            textInputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    const start = this.selectionStart;
                    const end = this.selectionEnd;
                    this.value = this.value.toUpperCase();
                    this.setSelectionRange(start, end);
                });
            });

            // 3. Anti Minus
            const numberInputs = document.querySelectorAll('input[type="number"]');
            numberInputs.forEach(function(input) {
                input.setAttribute('min', '0');
                input.addEventListener('keydown', function(e) {
                    if (e.key === '-' || e.key === 'e' || e.key === 'E') {
                        e.preventDefault();
                    }
                });
                input.addEventListener('input', function() {
                    if (this.value < 0) this.value = Math.abs(this.value);
                });
            });
        });
    </script>
</body>
</html>