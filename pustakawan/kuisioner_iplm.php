<?php
// web-perpus-v1/pustakawan/kuisioner_iplm.php
$library_id = isset($_GET['library_id']) ? $_GET['library_id'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuesioner IPLM - Tahun 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #ffffff; font-family: sans-serif; overflow-x: hidden; }
        .sidebar { width: 250px; background-color: #d9d9d9; min-height: 100vh; border-right: 1px solid #000; padding: 30px; position: fixed; top: 0; left: 0; }
        .sidebar h2 { font-weight: bold; margin-bottom: 50px; text-align: center; }
        .nav-link { color: #000; font-size: 18px; margin-bottom: 15px; text-decoration: none; display: block; padding: 5px 10px; }
        .nav-link:hover { font-weight: bold; }
        .main-content { margin-left: 250px; padding: 40px; }
        
        .form-section { background-color: #d9d9d9; border: 1px solid #000; border-radius: 8px; padding: 25px; margin-bottom: 30px; }
        .form-section h5 { font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        
        /* Style label agar bisa menampung teks panjang */
        .form-label { font-weight: 500; font-size: 14px; margin-bottom: 8px; display: block; }
        .form-control, .form-select { background-color: #fff; border: 1px solid #000; border-radius: 4px; }
        
        .btn-submit { background-color: #000; color: #fff; border: none; padding: 12px 50px; border-radius: 8px; font-weight: bold; font-size: 16px; }
        .btn-submit:hover { background-color: #333; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <h2>Logo</h2>
        <a href="dashboard.php?library_id=<?= $library_id ?>" class="nav-link">Beranda</a>
        <a href="#" class="nav-link fw-bold">Kuisioner IPLM</a>
        <a href="kuisioner_tkm.php?library_id=<?= $library_id ?>" class="nav-link">Kuisioner TKM</a>
        <a href="riwayat.php?library_id=<?= $library_id ?>" class="nav-link">Riwayat</a>
        <a href="profil.php?library_id=<?= $library_id ?>" class="nav-link">Profil</a>
    </nav>

    <main class="main-content">
        <h3 class="mb-4 text-center fw-bold">KUESIONER INDEKS PEMBANGUNAN LITERASI MASYARAKAT (IPLM)<br>TAHUN 2025</h3>

        <form method="POST" action="proses_iplm.php">
            
            <div class="form-section">
                <h5>I. DATA JENIS PERPUSTAKAAN (Isilah dengan huruf KAPITAL)</h5>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Jenis Perpustakaan</label>
                        <select name="jenis_perpus" class="form-select">
                            <option>Perpustakaan Umum</option>
                            <option>Perpustakaan Sekolah</option>
                            <option>Perpustakaan Perguruan Tinggi</option>
                            <option>Perpustakaan Khusus</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Subjenis Perpustakaan</label>
                        <input type="text" name="subjenis" class="form-control text-uppercase">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Guru dan Tenaga Kependidikan<br>(Diisi jika Perpustakaan Sekolah, perpustakaan lainnya isi dengan 0)</label>
                        <input type="number" name="jml_guru" class="form-control" value="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Siswa<br>(Diisi jika Perpustakaan Sekolah, perpustakaan lainnya isi dengan 0)</label>
                        <input type="number" name="jml_siswa" class="form-control" value="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Karyawan<br>(Diisi jika Perpustakaan Khusus, perpustakaan lainnya isi dengan 0)</label>
                        <input type="number" name="jml_karyawan" class="form-control" value="0">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h5>II. DATA DEMOGRAFI (Isilah dengan huruf KAPITAL)</h5>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Nomor Pokok Perpustakaan (NPP)<br>(Jika belum memiliki NPP, isi dengan 0)</label>
                        <input type="text" name="npp" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nomor Pokok Sekolah Nasional (NPSN)<br>(Diisi jika Perpustakaan Sekolah, perpustakaan lainnya isi dengan 0)</label>
                        <input type="text" name="npsn" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Nama Institusi/Sekolah/OPD/TBM/Lainnya</label>
                        <input type="text" name="nama_institusi" class="form-control text-uppercase">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Nama Perpustakaan</label>
                        <input type="text" name="nama_perpustakaan" class="form-control text-uppercase">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Alamat Institusi/Sekolah/OPD/TBM/Lainnya</label>
                        <textarea name="alamat" class="form-control text-uppercase" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Provinsi Asal</label>
                        <input type="text" name="provinsi" class="form-control text-uppercase">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kabupaten/Kota Asal</label>
                        <input type="text" name="kota" class="form-control text-uppercase">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap Pengisi Kuesioner</label>
                        <input type="text" name="pengisi" class="form-control text-uppercase">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kontak Pengisi Kuesioner (Whatsapp Aktif)</label>
                        <input type="text" name="kontak" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h5>III. DIMENSI KOLEKSI</h5>
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
                    
                    <div class="col-12"><hr class="border-black"></div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Penambahan Jumlah Judul Koleksi Tercetak dalam 1 Tahun Terakhir</label>
                        <input type="number" name="tambah_judul_tercetak" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Penambahan Jumlah Eksemplar Koleksi Tercetak dalam 1 Tahun Terakhir</label>
                        <input type="number" name="tambah_eksemplar_tercetak" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Penambahan Jumlah Judul Koleksi Digital dalam 1 Tahun Terakhir</label>
                        <input type="number" name="tambah_judul_digital" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Penambahan Jumlah Eksemplar Koleksi Digital dalam 1 Tahun Terakhir</label>
                        <input type="number" name="tambah_eksemplar_digital" class="form-control">
                    </div>

                    <div class="col-12"><hr class="border-black"></div>

                    <div class="col-md-12">
                        <label class="form-label">Jumlah Anggaran Pengembangan Koleksi Tercetak dan Digital Dalam 1 Tahun Terakhir yang Berasal dari Dana BOS<br>(Diisi jika Perpustakaan Sekolah, perpustakaan lainnya isi dengan 0)</label>
                        <input type="number" name="anggaran_bos" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Jumlah Anggaran Pengembangan Koleksi Tercetak dan Digital dalam 1 Tahun Terakhir yang Berasal dari Dana Non BOS<br>(Diisi jika Perpustakaan Sekolah, perpustakaan lainnya isi dengan 0)</label>
                        <input type="number" name="anggaran_non_bos" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Jumlah Anggaran Pengembangan Koleksi Tercetak dan Digital dalam 1 Tahun Terakhir</label>
                        <input type="number" name="total_anggaran_koleksi" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h5>IV. DIMENSI TENAGA PERPUSTAKAAN</h5>
                <div class="row g-4">
                    <div class="col-md-12">
                        <label class="form-label">Jumlah Tenaga Perpustakaan Memiliki Kualifikasi Pendidikan Ilmu Perpustakaan (Orang)</label>
                        <input type="number" name="tenaga_berkualifikasi" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Jumlah Tenaga Perpustakaan Tidak Memiliki Kualifikasi Pendidikan Ilmu Perpustakaan (Orang)</label>
                        <input type="number" name="tenaga_non_kualifikasi" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Jumlah Tenaga Perpustakaan yang Mengikuti Kegiatan Pengembangan Keprofesian Berkelanjutan (PKB) di Bidang Perpustakaan dalam 1 Tahun Terakhir (Orang)</label>
                        <input type="number" name="tenaga_ikut_pkb" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Jumlah Anggaran Pengembangan Keprofesian (Diklat) Tenaga Perpustakaan dalam 1 Tahun Terakhir</label>
                        <input type="number" name="anggaran_diklat" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h5>V. DIMENSI PELAYANAN</h5>
                <div class="mb-3">
                    <label class="form-label">Jumlah Peserta Kegiatan Penguatan Budaya Baca dan Peningkatan Kecakapan Literasi dalam 1 Tahun Terakhir (Orang)</label>
                    <input type="number" name="peserta_literasi" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah Pemustaka dari Satuan Pendidikan atau Masyarakat yang Memanfaatkan Perpustakaan Secara Luring dan/atau Daring dalam 1 Tahun Terakhir (Orang)</label>
                    <input type="number" name="pemustaka_total" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah Pemustaka yang Menggunakan Fasilitas Sarana TIK di Perpustakaan dalam 1 Tahun Terakhir (Orang)</label>
                    <input type="number" name="pemustaka_tik" class="form-control">
                </div>
                <div class="row g-4 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Judul Koleksi Tercetak Yang Dimanfaatkan Dalam 1 Tahun Terakhir</label>
                        <input type="number" name="judul_tercetak_pakai" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Eksemplar Koleksi Tercetak yang Dimanfaatkan dalam 1 Tahun Terakhir</label>
                        <input type="number" name="eksemplar_tercetak_pakai" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Judul Koleksi Digital yang Dimanfaatkan dalam 1 Tahun Terakhir</label>
                        <input type="number" name="judul_digital_pakai" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Eksemplar Koleksi Digital yang Dimanfaatkan dalam 1 Tahun Terakhir</label>
                        <input type="number" name="eksemplar_digital_pakai" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h5>VI. DIMENSI PENYELENGGARAAN DAN PENGELOLAAN</h5>
                <div class="mb-4">
                    <label class="form-label">Jumlah Kegiatan Penguatan Budaya Baca dan Peningkatan Kecakapan Literasi dalam 1 Tahun Terakhir</label>
                    <input type="number" name="jml_kegiatan_baca" class="form-control">
                </div>
                <div class="mb-4">
                    <label class="form-label">Jumlah Kolaborasi/Kerja Sama Perpustakaan dengan Pihak Eksternal dalam Rangka Peningkatan Pelayanan dan Pengembangan Perpustakaan dalam 1 Tahun Terakhir (Kegiatan Kerja Sama)</label>
                    <input type="number" name="jml_kerjasama" class="form-control">
                </div>
                <div class="mb-4">
                    <label class="form-label">Jumlah Variasi Layanan yang Tersedia (Fisik Dan Digital)</label>
                    <input type="number" name="jml_variasi_layanan" class="form-control">
                </div>
                <div class="mb-4">
                    <label class="form-label">Jumlah Kebijakan dan Prosedur Pelayanan Perpustakaan (Dokumen)</label>
                    <input type="number" name="jml_kebijakan" class="form-control">
                </div>
                <div class="mb-4">
                    <label class="form-label">Jumlah Peraturan Daerah (Kebijakan) Tentang Perpustakaan<br>(Diisi jika Perpustakaan Umum Provinsi/Kabupaten/Kota, perpustakaan lainnya isi dengan 0)</label>
                    <input type="number" name="jml_perda" class="form-control">
                </div>
                <div class="mb-4">
                    <label class="form-label">Jumlah Anggaran untuk Peningkatan Pelayanan dan Pengelolaan Perpustakaan Selama 1 Tahun Terakhir</label>
                    <input type="number" name="anggaran_layanan" class="form-control">
                </div>
            </div>

            <div class="text-end mb-5">
                <button type="submit" class="btn-submit">Kirim Kuesioner IPLM</button>
            </div>

        </form>
    </main>
</body>
</html>