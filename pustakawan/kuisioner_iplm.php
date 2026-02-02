<?php
// web-perpus-v1/pustakawan/kuisioner_iplm.php

// 1. TANGKAP DATA DARI HALAMAN SEBELUMNYA
$library_id = isset($_GET['library_id']) ? $_GET['library_id'] : '';
$kategori_utama = isset($_GET['kategori_utama']) ? $_GET['kategori_utama'] : ''; 
$kategori_sub   = isset($_GET['kategori_sub']) ? $_GET['kategori_sub'] : '';     
// Tangkap Nama Perpustakaan Text
$nama_perpus_text = isset($_GET['nama_perpus_text']) ? $_GET['nama_perpus_text'] : '';

// Format tampilan text Jenis
$display_jenis = $kategori_utama ? "Perpustakaan " . ucfirst($kategori_utama) : '';

// --- LOGIKA TANGGAL OTOMATIS ---
$bulan_indo = ['01' => 'JANUARI', '02' => 'FEBRUARI', '03' => 'MARET', '04' => 'APRIL', '05' => 'MEI', '06' => 'JUNI', '07' => 'JULI', '08' => 'AGUSTUS', '09' => 'SEPTEMBER', '10' => 'OKTOBER', '11' => 'NOVEMBER', '12' => 'DESEMBER'];
$tahun_sekarang = date('Y');
$bulan_sekarang = $bulan_indo[date('m')];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuesioner IPLM - <?= $bulan_sekarang ?> <?= $tahun_sekarang ?></title>
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

        /* 2. HEADER */
        .header-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 40px;
            padding-bottom: 20px;
            gap: 20px;
            background-image: linear-gradient(to bottom, #000 0px, #000 1px, transparent 1px, transparent 4px, #000 4px, #000 5px);
            background-repeat: no-repeat;
            background-size: 100% 5px;
            background-position: bottom;
        }
        
        .header-logo { width: 150px; height: 150px; object-fit: contain; }
        .header-title-box { flex: 1; text-align: center; }
        .header-title-box h3 { font-weight: 700; margin: 0; color: #000; font-size: 22px; line-height: 1.4; }
        .header-subtitle { font-size: 16px; font-weight: 500; display: block; margin-top: 5px; }

        /* Styles Form */
        .form-section { background-color: #ffffff; border: 1px solid #333; border-radius: 10px; padding: 30px; margin-bottom: 30px; }
        .section-header { font-weight: 600; font-size: 18px; margin-bottom: 25px; color: #000; text-transform: uppercase; border-bottom: 1px dashed #ccc; padding-bottom: 10px; }
        .form-label { font-weight: 500; font-size: 14px; margin-bottom: 8px; color: #444; }
        .form-control, .form-select { border: 1px solid #aaa; border-radius: 6px; padding: 10px; text-transform: uppercase; }
        .form-control:focus, .form-select:focus { border-color: #000; box-shadow: 0 0 0 3px rgba(0,0,0,0.1); }
        
        /* STYLE KHUSUS READONLY */
        input[readonly] {
            background-color: #e9ecef !important; /* Abu-abu */
            color: #495057;
            cursor: not-allowed;
            border-color: #ced4da;
            font-weight: bold;
        }

        .btn-submit { background-color: #000; color: #fff; border: none; padding: 15px 50px; border-radius: 8px; font-weight: 600; font-size: 16px; width: 100%; transition: all 0.3s; }
        .btn-submit:hover { background-color: #333; transform: translateY(-2px); }
        .back-link { display: inline-block; margin-bottom: 20px; color: #666; text-decoration: none; font-weight: 500; }
        .back-link:hover { color: #000; }
    </style>
</head>
<body>

    <div class="container-form">
        <a href="pilih_perpustakaan.php?target=iplm" class="back-link">
            &larr; Kembali ke Pilihan Perpustakaan
        </a>

        <div class="header-wrapper">
            <img src="../assets/logo_lobar.png" alt="Logo Kiri" class="header-logo">
            
            <div class="header-title-box">
                <h3>KUESIONER INDEKS PEMBANGUNAN LITERASI MASYARAKAT (IPLM)</h3>
                <span class="header-subtitle">TAHUN <?= $tahun_sekarang ?> PERIODE <?= $bulan_sekarang ?></span>
            </div>

            <img src="../assets/logo_disarpus.png" alt="Logo Kanan" class="header-logo">
        </div>

        <form method="POST" action="proses_iplm.php">
            
            <input type="hidden" name="library_id" value="<?= htmlspecialchars($library_id) ?>">
            <input type="hidden" name="nama_perpustakaan_hidden" value="<?= htmlspecialchars($nama_perpus_text) ?>">
            <input type="hidden" name="jenis_perpus_hidden" value="<?= htmlspecialchars($display_jenis) ?>">
            <input type="hidden" name="subjenis_hidden" value="<?= htmlspecialchars($kategori_sub) ?>">

            <div class="form-section">
                <div class="section-header">I. DATA JENIS PERPUSTAKAAN</div>
                <div class="row g-4">
                    
                    <div class="col-md-6">
                        <label class="form-label">Jenis Perpustakaan</label>
                        <input type="text" name="jenis_perpus_display" class="form-control" 
                               value="<?= htmlspecialchars($display_jenis) ?>" readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Subjenis Perpustakaan</label>
                        <input type="text" name="subjenis_display" class="form-control" 
                               value="<?= htmlspecialchars($kategori_sub) ?>" readonly>
                    </div>

                    <?php if($kategori_utama == 'Sekolah'): ?>
                        <div class="col-md-6">
                            <label class="form-label">Jumlah Guru & Tenaga Kependidikan</label>
                            <input type="number" name="jml_guru" class="form-control" required value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jumlah Siswa</label>
                            <input type="number" name="jml_siswa" class="form-control" required value="0">
                        </div>
                    <?php endif; ?>

                    <?php if($kategori_utama == 'Khusus' || $kategori_utama == 'Umum'): ?>
                        <div class="col-md-6">
                            <label class="form-label">Jumlah Karyawan/Pengelola</label>
                            <input type="number" name="jml_karyawan" class="form-control" required value="0">
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>

            <div class="form-section">
                <div class="section-header">II. DATA DEMOGRAFI</div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">NPP <small class="text-muted">(Isi 0 jika belum ada)</small></label>
                        <input type="text" name="npp" class="form-control">
                    </div>
                    
                    <?php if($kategori_utama == 'Sekolah'): ?>
                    <div class="col-md-6">
                        <label class="form-label">NPSN</label>
                        <input type="text" name="npsn" class="form-control">
                    </div>
                    <?php endif; ?>

                    <div class="col-md-12">
                        <label class="form-label">Nama Institusi/Sekolah/OPD/TBM</label>
                        <input type="text" name="nama_institusi" class="form-control">
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label">Nama Perpustakaan</label>
                        <input type="text" name="nama_perpustakaan" class="form-control"
                               value="<?= htmlspecialchars($nama_perpus_text) ?>" readonly>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Provinsi Asal</label>
                        <input type="text" name="provinsi" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kabupaten/Kota Asal</label>
                        <input type="text" name="kota" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap Pengisi</label>
                        <input type="text" name="pengisi" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kontak Pengisi (WA)</label>
                        <input type="text" name="kontak" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header">III. DIMENSI KOLEKSI</div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Judul Koleksi Tercetak</label>
                        <input type="number" name="judul_tercetak" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Eksemplar Koleksi Tercetak</label>
                        <input type="number" name="eksemplar_tercetak" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Judul Koleksi Digital</label>
                        <input type="number" name="judul_digital" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Eksemplar Koleksi Digital</label>
                        <input type="number" name="eksemplar_digital" class="form-control">
                    </div>
                    
                    <div class="col-12"><hr class="my-3 text-secondary"></div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Penambahan Judul Tercetak (1 Thn)</label>
                        <input type="number" name="tambah_judul_tercetak" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Penambahan Eksemplar Tercetak (1 Thn)</label>
                        <input type="number" name="tambah_eksemplar_tercetak" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Penambahan Judul Digital (1 Thn)</label>
                        <input type="number" name="tambah_judul_digital" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Penambahan Eksemplar Digital (1 Thn)</label>
                        <input type="number" name="tambah_eksemplar_digital" class="form-control">
                    </div>

                    <div class="col-12"><hr class="my-3 text-secondary"></div>

                    <?php if($kategori_utama == 'Sekolah'): ?>
                    <div class="col-md-6">
                        <label class="form-label">Anggaran Dana BOS (Sekolah)</label>
                        <input type="number" name="anggaran_bos" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Anggaran Dana Non BOS (Sekolah)</label>
                        <input type="number" name="anggaran_non_bos" class="form-control">
                    </div>
                    <?php endif; ?>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">Total Anggaran Pengembangan Koleksi (1 Thn)</label>
                        <input type="number" name="total_anggaran_koleksi" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header">IV. DIMENSI TENAGA PERPUSTAKAAN</div>
                <div class="row g-4">
                    <div class="col-md-12">
                        <label class="form-label">Tenaga Berkualifikasi Ilmu Perpustakaan (Orang)</label>
                        <input type="number" name="tenaga_berkualifikasi" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Tenaga TIDAK Berkualifikasi Ilmu Perpustakaan (Orang)</label>
                        <input type="number" name="tenaga_non_kualifikasi" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Tenaga Mengikuti PKB/Diklat (1 Thn Terakhir)</label>
                        <input type="number" name="tenaga_ikut_pkb" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Anggaran Diklat Tenaga (1 Thn Terakhir)</label>
                        <input type="number" name="anggaran_diklat" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header">V. DIMENSI PELAYANAN</div>
                <div class="mb-3">
                    <label class="form-label">Peserta Kegiatan Literasi (1 Thn Terakhir)</label>
                    <input type="number" name="peserta_literasi" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Total Pemustaka Luring/Daring (1 Thn Terakhir)</label>
                    <input type="number" name="pemustaka_total" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Pemustaka Menggunakan TIK (1 Thn Terakhir)</label>
                    <input type="number" name="pemustaka_tik" class="form-control">
                </div>
                <div class="row g-4 mt-1">
                    <div class="col-md-6">
                        <label class="form-label">Judul Tercetak Dimanfaatkan</label>
                        <input type="number" name="judul_tercetak_pakai" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Eksemplar Tercetak Dimanfaatkan</label>
                        <input type="number" name="eksemplar_tercetak_pakai" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Judul Digital Dimanfaatkan</label>
                        <input type="number" name="judul_digital_pakai" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Eksemplar Digital Dimanfaatkan</label>
                        <input type="number" name="eksemplar_digital_pakai" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header">VI. DIMENSI PENYELENGGARAAN & PENGELOLAAN</div>
                <div class="mb-4">
                    <label class="form-label">Kegiatan Penguatan Budaya Baca (1 Thn)</label>
                    <input type="number" name="jml_kegiatan_baca" class="form-control">
                </div>
                <div class="mb-4">
                    <label class="form-label">Kerjasama Eksternal (1 Thn)</label>
                    <input type="number" name="jml_kerjasama" class="form-control">
                </div>
                <div class="mb-4">
                    <label class="form-label">Variasi Layanan Tersedia</label>
                    <input type="number" name="jml_variasi_layanan" class="form-control">
                </div>
                <div class="mb-4">
                    <label class="form-label">Dokumen Kebijakan & Prosedur</label>
                    <input type="number" name="jml_kebijakan" class="form-control">
                </div>
                
                <?php if($kategori_sub == 'Daerah' || $kategori_sub == 'Umum'): ?>
                <div class="mb-4">
                    <label class="form-label">Perda Tentang Perpustakaan</label>
                    <input type="number" name="jml_perda" class="form-control">
                </div>
                <?php endif; ?>

                <div class="mb-4">
                    <label class="form-label">Anggaran Peningkatan Pelayanan (1 Thn)</label>
                    <input type="number" name="anggaran_layanan" class="form-control">
                </div>
            </div>

            <div class="mb-5">
                <button type="submit" class="btn-submit">KIRIM KUESIONER IPLM &rarr;</button>
            </div>

        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Auto Uppercase
            const textInputs = document.querySelectorAll('input[type="text"], textarea');
            textInputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    const start = this.selectionStart;
                    const end = this.selectionEnd;
                    this.value = this.value.toUpperCase();
                    this.setSelectionRange(start, end);
                });
            });

            // Anti Minus
            const numberInputs = document.querySelectorAll('input[type="number"]');
            numberInputs.forEach(function(input) {
                input.setAttribute('min', '0');
                input.addEventListener('keydown', function(e) {
                    if (e.key === '-' || e.key === 'e' || e.key === 'E') { e.preventDefault(); }
                });
                input.addEventListener('input', function() {
                    if (this.value < 0) this.value = Math.abs(this.value);
                });
            });
        });
    </script>
</body>
</html>