<?php
// web-perpus-v1/admin/export_data.php
session_start();
require '../config/database.php';
require '../config/admin_auth.php';

// 1. TANGKAP PARAMETER JENIS (iplm atau tkm)
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';

// Jika tidak ada jenis yang dipilih, tolak akses
if (!in_array($jenis, ['iplm', 'tkm'])) {
    die("Error: Harap pilih jenis laporan (IPLM atau TKM).");
}

// 2. CONFIGURASI HEADER EXCEL & QUERY DATABASE
if ($jenis == 'iplm') {
    // --- SETUP IPLM ---
    $filename = "Rekap_IPLM_" . date('Y-m-d_H-i-s') . ".xls";
    $sql = "SELECT * FROM trans_iplm ORDER BY id DESC"; // Pastikan nama tabel benar
    $title = "REKAPITULASI INDEKS PEMBANGUNAN LITERASI MASYARAKAT (IPLM)";
} else {
    // --- SETUP TKM ---
    $filename = "Rekap_TKM_" . date('Y-m-d_H-i-s') . ".xls";
    $sql = "SELECT * FROM trans_tkm ORDER BY id DESC"; // Pastikan nama tabel benar
    $title = "REKAPITULASI TINGKAT KEGEMARAN MEMBACA (TKM)";
}

// 3. EKSEKUSI QUERY
try {
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Gagal mengambil data database. Pastikan tabel sudah ada.<br>Error: " . $e->getMessage());
}

// 4. KIRIM HEADER EXCEL
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; font-size: 12px; }
        th, td { border: 1px solid #000; padding: 6px; vertical-align: middle; }
        th { text-align: center; font-weight: bold; height: 30px; }
        .num { mso-number-format:"0"; text-align: center; }
        
        /* Warna Header */
        .bg-head { background-color: #d9d9d9; }
        .bg-kuning { background-color: #ffe699; }
        .bg-biru { background-color: #bdd7ee; }
        .bg-hijau { background-color: #c6e0b4; }
        .bg-orange { background-color: #f8cbad; }
    </style>
</head>
<body>

    <h3><?= $title ?></h3>
    <p>Diunduh pada: <?= date('d-m-Y H:i') ?></p>

    <?php if ($jenis == 'iplm'): ?>
    <table>
        <thead>
            <tr>
                <th colspan="7" class="bg-kuning">IDENTITAS</th>
                <th colspan="3" class="bg-biru">DATA UMUM</th>
                <th colspan="11" class="bg-hijau">KOLEKSI & ANGGARAN</th>
                <th colspan="4" class="bg-orange">TENAGA</th>
                <th colspan="7" class="bg-kuning">PELAYANAN</th>
                <th colspan="6" class="bg-head">MANAJEMEN</th>
            </tr>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama Perpustakaan</th>
                <th>Jenis</th>
                <th>Sub Jenis</th>
                <th>Provinsi</th>
                <th>Kab/Kota</th>
                
                <th>NPP</th>
                <th>Institusi</th>
                <th>Kontak</th>

                <th>Jml Judul Cetak</th>
                <th>Jml Eks Cetak</th>
                <th>Jml Judul Digital</th>
                <th>Jml Eks Digital</th>
                <th>+ Judul Cetak</th>
                <th>+ Eks Cetak</th>
                <th>+ Judul Digital</th>
                <th>+ Eks Digital</th>
                <th>BOS</th>
                <th>Non BOS</th>
                <th>Total Anggaran</th>

                <th>Tenaga Ahli</th>
                <th>Tenaga Non-Ahli</th>
                <th>Ikut Diklat</th>
                <th>Anggaran Diklat</th>

                <th>Peserta Literasi</th>
                <th>Total Pemustaka</th>
                <th>Pemustaka TIK</th>
                <th>Judul Cetak Dipakai</th>
                <th>Eks Cetak Dipakai</th>
                <th>Judul Digital Dipakai</th>
                <th>Eks Digital Dipakai</th>

                <th>Kegiatan Baca</th>
                <th>Kerjasama</th>
                <th>Variasi Layanan</th>
                <th>Kebijakan</th>
                <th>Perda</th>
                <th>Anggaran Layanan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach($data as $row): ?>
            <tr>
                <td class="num"><?= $no++ ?></td>
                <td><?= $row['created_at'] ?? '-' ?></td>
                <td><?= htmlspecialchars($row['nama_perpustakaan']) ?></td>
                <td><?= htmlspecialchars($row['jenis_perpus']) ?></td>
                <td><?= htmlspecialchars($row['subjenis']) ?></td>
                <td><?= htmlspecialchars($row['provinsi']) ?></td>
                <td><?= htmlspecialchars($row['kota']) ?></td>
                
                <td class="num"><?= htmlspecialchars($row['npp']) ?></td>
                <td><?= htmlspecialchars($row['nama_institusi']) ?></td>
                <td><?= htmlspecialchars($row['pengisi']) ?> (<?= htmlspecialchars($row['kontak']) ?>)</td>

                <td class="num"><?= $row['judul_tercetak'] ?></td>
                <td class="num"><?= $row['eksemplar_tercetak'] ?></td>
                <td class="num"><?= $row['judul_digital'] ?></td>
                <td class="num"><?= $row['eksemplar_digital'] ?></td>
                <td class="num"><?= $row['tambah_judul_tercetak'] ?></td>
                <td class="num"><?= $row['tambah_eksemplar_tercetak'] ?></td>
                <td class="num"><?= $row['tambah_judul_digital'] ?></td>
                <td class="num"><?= $row['tambah_eksemplar_digital'] ?></td>
                <td><?= $row['anggaran_bos'] ?></td>
                <td><?= $row['anggaran_non_bos'] ?></td>
                <td><?= $row['total_anggaran_koleksi'] ?></td>

                <td class="num"><?= $row['tenaga_berkualifikasi'] ?></td>
                <td class="num"><?= $row['tenaga_non_kualifikasi'] ?></td>
                <td class="num"><?= $row['tenaga_ikut_pkb'] ?></td>
                <td><?= $row['anggaran_diklat'] ?></td>

                <td class="num"><?= $row['peserta_literasi'] ?></td>
                <td class="num"><?= $row['pemustaka_total'] ?></td>
                <td class="num"><?= $row['pemustaka_tik'] ?></td>
                <td class="num"><?= $row['judul_tercetak_pakai'] ?></td>
                <td class="num"><?= $row['eksemplar_tercetak_pakai'] ?></td>
                <td class="num"><?= $row['judul_digital_pakai'] ?></td>
                <td class="num"><?= $row['eksemplar_digital_pakai'] ?></td>

                <td class="num"><?= $row['jml_kegiatan_baca'] ?></td>
                <td class="num"><?= $row['jml_kerjasama'] ?></td>
                <td class="num"><?= $row['jml_variasi_layanan'] ?></td>
                <td class="num"><?= $row['jml_kebijakan'] ?></td>
                <td class="num"><?= $row['jml_perda'] ?></td>
                <td><?= $row['anggaran_layanan'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php elseif ($jenis == 'tkm'): ?>
    <table>
        <thead>
            <tr>
                <th colspan="7" class="bg-kuning">IDENTITAS & DEMOGRAFI</th>
                <th colspan="7" class="bg-biru">KEBIASAAN MEMBACA</th>
                <th colspan="28" class="bg-hijau">DIMENSI PRA MEMBACA (Skor 1-4)</th>
                <th colspan="16" class="bg-orange">DIMENSI SAAT MEMBACA (Skor 1-4)</th>
                <th colspan="10" class="bg-head">DIMENSI PASCA MEMBACA (Skor 1-4)</th>
                <th colspan="14" class="bg-kuning">INTERAKSI PERPUSTAKAAN (Skor 1-4)</th>
            </tr>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Usia</th>
                <th>Gender</th>
                <th>Pendidikan</th>
                <th>Pekerjaan</th>
                <th>Domisili</th>

                <th>Waktu Khusus</th>
                <th>Tujuan</th>
                <th>Jarak</th>
                <th>Freq Cetak</th>
                <th>Freq Digital</th>
                <th>Durasi Cetak</th>
                <th>Durasi Digital</th>

                <?php for($i=1; $i<=28; $i++): ?><th>P<?= $i ?></th><?php endfor; ?>
                <?php for($i=1; $i<=16; $i++): ?><th>S<?= $i ?></th><?php endfor; ?>
                <?php for($i=1; $i<=10; $i++): ?><th>Pc<?= $i ?></th><?php endfor; ?>
                <?php for($i=1; $i<=14; $i++): ?><th>I<?= $i ?></th><?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach($data as $row): ?>
            <tr>
                <td class="num"><?= $no++ ?></td>
                <td><?= $row['created_at'] ?? '-' ?></td>
                <td><?= htmlspecialchars($row['usia']) ?></td>
                <td><?= htmlspecialchars($row['gender']) ?></td>
                <td><?= htmlspecialchars($row['pendidikan']) ?></td>
                <td><?= htmlspecialchars($row['pekerjaan']) ?></td>
                <td><?= htmlspecialchars($row['kota']) ?></td>

                <td><?= htmlspecialchars($row['waktu_khusus']) ?></td>
                <td><?= htmlspecialchars($row['tujuan']) ?></td>
                <td><?= htmlspecialchars($row['jarak_perpus']) ?></td>
                <td><?= htmlspecialchars($row['freq_buku_cetak']) ?></td>
                <td><?= htmlspecialchars($row['freq_buku_digital']) ?></td>
                <td><?= htmlspecialchars($row['durasi_cetak']) ?></td>
                <td><?= htmlspecialchars($row['durasi_digital']) ?></td>

                <?php for($i=0; $i<28; $i++): ?><td class="num"><?= $row['pra_'.$i] ?? 0 ?></td><?php endfor; ?>
                <?php for($i=0; $i<16; $i++): ?><td class="num"><?= $row['saat_'.$i] ?? 0 ?></td><?php endfor; ?>
                <?php for($i=0; $i<10; $i++): ?><td class="num"><?= $row['pasca_'.$i] ?? 0 ?></td><?php endfor; ?>
                <?php for($i=0; $i<14; $i++): ?><td class="num"><?= $row['interaksi_'.$i] ?? 0 ?></td><?php endfor; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

</body>
</html>
