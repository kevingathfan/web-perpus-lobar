<?php
// web-perpus-v1/pustakawan/import_final.php
require '../config/database.php';

$csvFile = 'perpustakaan_desa_status.csv';

if (!file_exists($csvFile)) {
    die("Error: File $csvFile tidak ditemukan di folder pustakawan.");
}

echo "<h3>Sedang Memproses Import Data...</h3>";
echo "<p>Mode: <strong>Cek Duplikat & Update</strong></p>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>No</th><th>Nama Perpustakaan</th><th>Aksi</th><th>Status Hasil</th></tr>";

if (($handle = fopen($csvFile, "r")) !== FALSE) {
    // 1. Lewati baris header
    fgetcsv($handle); 

    // 2. Siapkan Query Cek (Untuk melihat apakah data sudah ada)
    $sqlCheck = "SELECT id FROM libraries WHERE nama = :nama";
    $stmtCheck = $pdo->prepare($sqlCheck);

    // 3. Siapkan Query Insert (Untuk data baru)
    $sqlInsert = "INSERT INTO libraries (nama, jenis, lokasi) VALUES (:nama, :jenis, :lokasi)";
    $stmtInsert = $pdo->prepare($sqlInsert);

    // 4. Siapkan Query Update (Jika data sudah ada, kita update isinya biar fresh)
    $sqlUpdate = "UPDATE libraries SET jenis = :jenis, lokasi = :lokasi WHERE id = :id";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    
    $berhasil_insert = 0;
    $berhasil_update = 0;
    $gagal = 0;
    $baris = 1;

    // Loop baris per baris
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
        
        // Amankan input dari error 'Undefined array key'
        $nama   = isset($row[0]) ? trim($row[0]) : ''; 
        $jenis  = isset($row[1]) ? trim($row[1]) : '';
        $lokasi = isset($row[2]) ? trim($row[2]) : '';
        $status = isset($row[3]) ? trim($row[3]) : '';

        if(empty($nama)) continue; 

        try {
            // LANGKAH 1: Cek apakah Nama sudah ada di database?
            $stmtCheck->execute([':nama' => $nama]);
            $existingLib = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existingLib) {
                // --- KONDISI A: SUDAH ADA (Lakukan UPDATE) ---
                $stmtUpdate->execute([
                    ':jenis'  => $jenis,
                    ':lokasi' => $lokasi,
                    ':id'     => $existingLib['id']
                ]);
                $berhasil_update++;
                echo "<tr><td>$baris</td><td>$nama</td><td style='color:blue'>Update Data</td><td style='color:blue'>OK</td></tr>";

            } else {
                // --- KONDISI B: BELUM ADA (Lakukan INSERT) ---
                $stmtInsert->execute([
                    ':nama'   => $nama,
                    ':jenis'  => $jenis,
                    ':lokasi' => $lokasi,
                ]);
                $berhasil_insert++;
                echo "<tr><td>$baris</td><td>$nama</td><td style='color:green'>Insert Baru</td><td style='color:green'>OK</td></tr>";
            }

        } catch (Exception $e) {
            $gagal++;
            echo "<tr><td>$baris</td><td>$nama</td><td>Error</td><td style='color:red'>" . $e->getMessage() . "</td></tr>";
        }
        $baris++;
    }
    fclose($handle);
    
    echo "</table>";
    echo "<h3>Selesai!</h3>";
    echo "<ul>";
    echo "<li>Data Baru Ditambahkan: <b>$berhasil_insert</b></li>";
    echo "<li>Data Lama Diupdate: <b>$berhasil_update</b></li>";
    echo "<li>Gagal: <b>$gagal</b></li>";
    echo "</ul>";
}
?>