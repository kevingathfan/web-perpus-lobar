<?php
// web-perpus-v1/admin/atur_pertanyaan.php
session_start();
require '../config/database.php';
require '../config/admin_auth.php';

// Pastikan tabel kategori_bagian ada (untuk manajemen bagian/label)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS kategori_bagian (
        id INT AUTO_INCREMENT PRIMARY KEY,
        jenis_kuesioner VARCHAR(10) NOT NULL,
        name VARCHAR(255) NOT NULL,
        position INT NOT NULL DEFAULT 0,
        numbering_style ENUM('numeric','roman','none') NOT NULL DEFAULT 'numeric',
        manual_label VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Migrasi otomatis kategori yang sudah ada jika tabel masih kosong
    $stmtChk = $pdo->query("SELECT COUNT(*) FROM kategori_bagian");
    $exists = (int)$stmtChk->fetchColumn();
    if ($exists === 0) {
        $stmtDistinct = $pdo->query("SELECT jenis_kuesioner, kategori_bagian, MIN(id) AS first_id FROM master_pertanyaan GROUP BY jenis_kuesioner, kategori_bagian ORDER BY jenis_kuesioner, MIN(id)");
        $rows = $stmtDistinct->fetchAll(PDO::FETCH_ASSOC);
        $stmtIns = $pdo->prepare("INSERT INTO kategori_bagian (jenis_kuesioner, name, position, numbering_style) VALUES (?, ?, ?, 'numeric')");
        $lastJenis = null;
        $pos = 1;
        foreach ($rows as $r) {
            if ($r['jenis_kuesioner'] !== $lastJenis) {
                $pos = 1;
                $lastJenis = $r['jenis_kuesioner'];
            }
            $stmtIns->execute([$r['jenis_kuesioner'], $r['kategori_bagian'], $pos]);
            $pos++;
        }
    } else {
        // Sync missing categories (jika ada kategori baru di master_pertanyaan tapi belum ada di kategori_bagian)
        $sqlMiss = "SELECT DISTINCT m.jenis_kuesioner, m.kategori_bagian 
                    FROM master_pertanyaan m 
                    LEFT JOIN kategori_bagian k ON k.jenis_kuesioner = m.jenis_kuesioner AND k.name = m.kategori_bagian
                    WHERE k.id IS NULL AND m.kategori_bagian IS NOT NULL AND m.kategori_bagian <> ''";
        $stmtMiss = $pdo->query($sqlMiss);
        $missing = $stmtMiss->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($missing)) {
            $stmtMaxPosKB = $pdo->prepare("SELECT COALESCE(MAX(position), 0) FROM kategori_bagian WHERE jenis_kuesioner = ?");
            $stmtInsKB = $pdo->prepare("INSERT INTO kategori_bagian (jenis_kuesioner, name, position) VALUES (?, ?, ?)");
            
            foreach ($missing as $row) {
                $j = $row['jenis_kuesioner'];
                $n = $row['kategori_bagian'];
                
                $stmtMaxPosKB->execute([$j]);
                $pos = (int)$stmtMaxPosKB->fetchColumn() + 1;
                
                $stmtInsKB->execute([$j, $n, $pos]);
            }
        }
    }
} catch (Exception $e) {
    // jangan gagalkan halaman jika pembuatan tabel/migrasi gagal
}

if (!empty($_GET) && (isset($_GET['tab']) || isset($_GET['page_iplm']) || isset($_GET['page_tkm']))) {
    $_SESSION['atur_pertanyaan_state'] = [
        'tab' => $_GET['tab'] ?? 'iplm',
        'page_iplm' => (int)($_GET['page_iplm'] ?? 1),
        'page_tkm' => (int)($_GET['page_tkm'] ?? 1),
    ];
    header("Location: atur_pertanyaan.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nav_only'])) {
    $_SESSION['atur_pertanyaan_state'] = [
        'tab' => $_POST['tab'] ?? 'iplm',
        'page_iplm' => max(1, (int)($_POST['page_iplm'] ?? 1)),
        'page_tkm' => max(1, (int)($_POST['page_tkm'] ?? 1)),
    ];
    header("Location: atur_pertanyaan.php");
    exit;
}

// --- 1. PROSES CRUD ---
$pesan = "";
$pesan_type = "success"; // success atau danger
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['aksi']) && $_POST['aksi'] === 'import_csv') {
            if (!isset($_FILES['file_csv']) || $_FILES['file_csv']['error'] !== 0) {
                throw new Exception("Gagal upload CSV.");
            }

            $file = $_FILES['file_csv']['tmp_name'];
            $orig = $_FILES['file_csv']['name'] ?? '';
            $size = $_FILES['file_csv']['size'] ?? 0;
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                throw new Exception("File harus berformat .csv");
            }
            if ($size > 2 * 1024 * 1024) {
                throw new Exception("Ukuran file maksimal 2MB.");
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file);
            $allowed_mime = ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'];
            if ($mime && !in_array($mime, $allowed_mime, true)) {
                throw new Exception("Tipe file tidak valid.");
            }

            $handle = fopen($file, "r");
            if (!$handle) throw new Exception("File CSV tidak bisa dibuka.");

            $header = fgetcsv($handle);
            if (!$header) {
                fclose($handle);
                throw new Exception("CSV kosong.");
            }

            $header_map = [];
            $lower = array_map(function($h){ return strtolower(trim($h)); }, $header);
            $has_header = in_array('jenis_kuesioner', $lower, true) || in_array('jenis', $lower, true);
            if ($has_header) {
                foreach ($lower as $i => $h) {
                    $header_map[$h] = $i;
                }
            } else {
                rewind($handle);
            }

            $allowed_tipe = ['text','number','textarea','likert','select','radio'];
            $count_cache = [];
            $sukses = 0; $gagal = 0;

            $stmtInsert = $pdo->prepare("INSERT INTO master_pertanyaan (jenis_kuesioner, kategori_bagian, teks_pertanyaan, keterangan, tipe_input, pilihan_opsi, urutan) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtShift = $pdo->prepare("UPDATE master_pertanyaan SET urutan = urutan + 1 WHERE jenis_kuesioner = ? AND kategori_bagian = ? AND CAST(urutan AS UNSIGNED) >= ?");

            while (($row = fgetcsv($handle, 2000, ",")) !== false) {
                if ($has_header) {
                    $jenis = $row[$header_map['jenis_kuesioner'] ?? $header_map['jenis'] ?? -1] ?? '';
                    $bagian = $row[$header_map['kategori_bagian'] ?? $header_map['kategori'] ?? -1] ?? '';
                    $soal = $row[$header_map['teks_pertanyaan'] ?? $header_map['pertanyaan'] ?? -1] ?? '';
                    $keterangan = $row[$header_map['keterangan'] ?? -1] ?? '';
                    $tipe = $row[$header_map['tipe_input'] ?? $header_map['tipe'] ?? -1] ?? '';
                    $pilihan_opsi = $row[$header_map['pilihan_opsi'] ?? $header_map['opsi'] ?? -1] ?? '';
                    $urutan = $row[$header_map['urutan'] ?? -1] ?? '';
                } else {
                    $jenis = $row[0] ?? '';
                    $bagian = $row[1] ?? '';
                    $soal = $row[2] ?? '';
                    $keterangan = $row[3] ?? '';
                    $tipe = $row[4] ?? '';
                    $pilihan_opsi = $row[5] ?? '';
                    $urutan = $row[6] ?? '';
                }

                $jenis = strtoupper(trim((string)$jenis));
                $bagian = trim((string)$bagian);
                $soal = trim((string)$soal);
                $keterangan = trim((string)$keterangan);
                $tipe = strtolower(trim((string)$tipe));
                $pilihan_opsi = trim((string)$pilihan_opsi);
                $urutan = (int)$urutan;

                if (!in_array($jenis, ['IPLM','TKM'], true) || $bagian === '' || $soal === '') {
                    $gagal++; 
                    continue;
                }
                if (!in_array($tipe, $allowed_tipe, true)) $tipe = 'text';

                // AUTO-CREATE KATEGORI BAGIAN IF NOT EXISTS
                $stmtCheckBagian = $pdo->prepare("SELECT id FROM kategori_bagian WHERE jenis_kuesioner = ? AND name = ? LIMIT 1");
                $stmtCheckBagian->execute([$jenis, $bagian]);
                if (!$stmtCheckBagian->fetch()) {
                    $stmtMaxPosKB = $pdo->prepare("SELECT COALESCE(MAX(position), 0) FROM kategori_bagian WHERE jenis_kuesioner = ?");
                    $stmtMaxPosKB->execute([$jenis]);
                    $newPosKB = (int)$stmtMaxPosKB->fetchColumn() + 1;
                    
                    $stmtInsKB = $pdo->prepare("INSERT INTO kategori_bagian (jenis_kuesioner, name, position) VALUES (?, ?, ?)");
                    $stmtInsKB->execute([$jenis, $bagian, $newPosKB]);
                }

                $key = $jenis . '|' . $bagian;
                if (!isset($count_cache[$key])) {
                    $stmtMax = $pdo->prepare("SELECT MAX(CAST(urutan AS UNSIGNED)) FROM master_pertanyaan WHERE jenis_kuesioner = ? AND kategori_bagian = ?");
                    $stmtMax->execute([$jenis, $bagian]);
                    $count_cache[$key] = (int)$stmtMax->fetchColumn();
                }
                $currentMax = (int)$count_cache[$key];
                $maxPos = $currentMax + 1;
                
                if ($urutan < 1) $urutan = $maxPos;
                if ($urutan > $maxPos) $urutan = $maxPos;

                try {
                    // geser urutan agar tidak duplikasi
                    $stmtShift->execute([$jenis, $bagian, $urutan]);
                    $stmtInsert->execute([$jenis, $bagian, $soal, $keterangan, $tipe, $pilihan_opsi, $urutan]);
                    $count_cache[$key] = max($count_cache[$key], $urutan);
                    $sukses++;
                } catch (Exception $e) {
                    $gagal++;
                }
            }
            fclose($handle);

            $_SESSION['flash_message'] = "Import selesai. Berhasil: $sukses. Gagal: $gagal.";
            header("Location: atur_pertanyaan.php"); exit;
        }

        // CSV Import Logic Removed

        if (isset($_POST['aksi']) && $_POST['aksi'] === 'bulk_delete') {
            $ids = $_POST['ids'] ?? [];
            if (!empty($ids) && is_array($ids)) {
                $ids = array_map('intval', $ids);
                $ids = array_filter($ids);
                
                if (!empty($ids)) {
                    $inQuery = implode(',', array_fill(0, count($ids), '?'));
                    $pdo->beginTransaction();
                    try {
                        $stmt = $pdo->prepare("DELETE FROM master_pertanyaan WHERE id IN ($inQuery)");
                        $stmt->execute($ids);
                        $pdo->commit();
                        $_SESSION['flash_message'] = count($ids) . " pertanyaan terpilih berhasil dihapus.";
                    } catch (Exception $e) {
                         if ($pdo->inTransaction()) $pdo->rollBack();
                         throw $e;
                    }
                }
            }
            $redirectTab = $_POST['tab'] ?? 'iplm';
            $redirectPageIplm = (int)($_POST['page_iplm'] ?? 1);
            $redirectPageTkm = (int)($_POST['page_tkm'] ?? 1);
            $_SESSION['atur_pertanyaan_state'] = [
                'tab' => $redirectTab,
                'page_iplm' => max(1, $redirectPageIplm),
                'page_tkm' => max(1, $redirectPageTkm),
            ];
            header("Location: atur_pertanyaan.php"); exit;
        }

        if (isset($_POST['aksi']) && $_POST['aksi'] === 'set_kontak_iplm') {
            $kontak_id = (int)($_POST['kontak_pertanyaan_id'] ?? 0);
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('iplm_kontak_pertanyaan_id', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([$kontak_id]);
            $_SESSION['flash_message'] = "Kontak IPLM berhasil diperbarui.";
            $redirectTab = 'iplm';
            $redirectPageIplm = (int)($_POST['page_iplm'] ?? 1);
            $redirectPageTkm = (int)($_POST['page_tkm'] ?? 1);
            $_SESSION['atur_pertanyaan_state'] = [
                'tab' => $redirectTab,
                'page_iplm' => max(1, $redirectPageIplm),
                'page_tkm' => max(1, $redirectPageTkm),
            ];
            header("Location: atur_pertanyaan.php"); exit;
        }
        if (isset($_POST['aksi']) && $_POST['aksi'] === 'set_autofill_iplm') {
            $id_jenis = (int)($_POST['autofill_jenis_id'] ?? 0);
            $id_subjenis = (int)($_POST['autofill_subjenis_id'] ?? 0);
            $id_nama = (int)($_POST['autofill_nama_id'] ?? 0);

            $pairs = [
                'iplm_autofill_jenis_id' => $id_jenis,
                'iplm_autofill_subjenis_id' => $id_subjenis,
                'iplm_autofill_nama_id' => $id_nama,
            ];
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            foreach ($pairs as $key => $val) {
                $stmt->execute([$key, $val]);
            }

            $_SESSION['flash_message'] = "Auto-fill IPLM berhasil diperbarui.";
            $redirectTab = 'iplm';
            $redirectPageIplm = (int)($_POST['page_iplm'] ?? 1);
            $redirectPageTkm = (int)($_POST['page_tkm'] ?? 1);
            $_SESSION['atur_pertanyaan_state'] = [
                'tab' => $redirectTab,
                'page_iplm' => max(1, $redirectPageIplm),
                'page_tkm' => max(1, $redirectPageTkm),
            ];
            header("Location: atur_pertanyaan.php"); exit;
        }

        // --- Manajemen Bagian (kategori_bagian) ---
        if (isset($_POST['aksi']) && $_POST['aksi'] === 'add_bagian') {
            $jb = $_POST['jenis_bagian'] ?? 'IPLM';
            $name = trim($_POST['nama_bagian'] ?? '');
            if ($name === '') throw new Exception('Nama bagian tidak boleh kosong');
            // FIX: Filter position by jenis_kuesioner untuk domain isolation
            $stmtPos = $pdo->prepare("SELECT COALESCE(MAX(position),0) FROM kategori_bagian WHERE jenis_kuesioner = ?");
            $stmtPos->execute([$jb]);
            $pos = (int)$stmtPos->fetchColumn() + 1;
            // Hapus numbering_style dari INSERT - gunakan setting global
            $stmtIns = $pdo->prepare("INSERT INTO kategori_bagian (jenis_kuesioner, name, position) VALUES (?, ?, ?)");
            $stmtIns->execute([$jb, $name, $pos]);
            $_SESSION['flash_message'] = 'Bagian baru berhasil ditambahkan.';
            header('Location: atur_pertanyaan.php'); exit;
        }

        if (isset($_POST['aksi']) && $_POST['aksi'] === 'edit_bagian') {
            $id = (int)($_POST['bagian_id'] ?? 0);
            $newName = trim($_POST['nama_bagian'] ?? '');
            $targetPos = (int)($_POST['posisi_bagian'] ?? 0);
            
            if ($id <= 0) throw new Exception('ID bagian tidak valid');
            if ($newName === '') throw new Exception('Nama bagian tidak boleh kosong');
            if ($targetPos < 1) $targetPos = 1;

            $pdo->beginTransaction();
            try {
                // 1. Ambil data item yang diedit
                $stmtGet = $pdo->prepare("SELECT jenis_kuesioner, name FROM kategori_bagian WHERE id = ?");
                $stmtGet->execute([$id]);
                $current = $stmtGet->fetch(PDO::FETCH_ASSOC);
                if (!$current) throw new Exception('Bagian tidak ditemukan');
                
                $jenis = $current['jenis_kuesioner'];
                $stmtMaxPos = $pdo->prepare("SELECT COUNT(*) FROM kategori_bagian WHERE jenis_kuesioner = ?");
$stmtMaxPos->execute([$jenis]);
$maxPos = (int)$stmtMaxPos->fetchColumn();

if ($targetPos > $maxPos) {
    $targetPos = $maxPos;
}

                $oldName = $current['name'];

                // 2. Update Nama (jika berubah)
                if ($newName !== $oldName) {
                    $stmtUpName = $pdo->prepare("UPDATE kategori_bagian SET name = ? WHERE id = ?");
                    $stmtUpName->execute([$newName, $id]);
                    
                    // Update referensi di master_pertanyaan
                    $stmtUpRef = $pdo->prepare("UPDATE master_pertanyaan SET kategori_bagian = ? WHERE jenis_kuesioner = ? AND kategori_bagian = ?");
                    $stmtUpRef->execute([$newName, $jenis, $oldName]);
                }

                // 3. Reordering (Strategi Array Splice - Lebih Stabil)
                // Ambil semua ID dalam jenis ini, urutkan berdasarkan posisi saat ini
                $stmtAll = $pdo->prepare("SELECT id FROM kategori_bagian WHERE jenis_kuesioner = ? ORDER BY position ASC, id ASC");
                $stmtAll->execute([$jenis]);
                $ids = $stmtAll->fetchAll(PDO::FETCH_COLUMN);

                // Hapus ID target dari array (jika ada)
                $key = array_search($id, $ids);
                if ($key !== false) {
                    unset($ids[$key]);
                }
                $ids = array_values($ids); // Reindex array

                // Masukkan ID target ke posisi baru
                // targetPos 1 berarti index 0.
                $insertIndex = $targetPos - 1;
                if ($insertIndex < 0) $insertIndex = 0;
                if ($insertIndex > count($ids)) $insertIndex = count($ids);

                array_splice($ids, $insertIndex, 0, $id);

                // 4. Update semua posisi sesuai urutan array baru
                $stmtUpdatePos = $pdo->prepare("UPDATE kategori_bagian SET position = ? WHERE id = ?");
                foreach ($ids as $index => $currId) {
                    $newP = $index + 1;
                    $stmtUpdatePos->execute([$newP, $currId]);
                }

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            $_SESSION['flash_message'] = 'Bagian berhasil diperbarui.';
            header('Location: atur_pertanyaan.php'); exit;
        }

        if (isset($_POST['aksi']) && $_POST['aksi'] === 'delete_bagian') {
            $id = (int)($_POST['bagian_id'] ?? 0);
            if ($id <= 0) throw new Exception('ID bagian tidak valid');
            
            $stmtGet = $pdo->prepare("SELECT jenis_kuesioner, name FROM kategori_bagian WHERE id = ? LIMIT 1");
            $stmtGet->execute([$id]);
            $row = $stmtGet->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                // Delete questions first
                $stmtDelQ = $pdo->prepare("DELETE FROM master_pertanyaan WHERE jenis_kuesioner = ? AND kategori_bagian = ?");
                $stmtDelQ->execute([$row['jenis_kuesioner'], $row['name']]);
            }
            
            $stmtDel = $pdo->prepare("DELETE FROM kategori_bagian WHERE id = ?");
            $stmtDel->execute([$id]);
            $_SESSION['flash_message'] = 'Bagian dan seluruh pertanyaan di dalamnya berhasil dihapus.';
            header('Location: atur_pertanyaan.php'); exit;
        }

        // --- Ubah Global Numbering Style untuk Jenis Kuesioner ---
        if (isset($_POST['aksi']) && $_POST['aksi'] === 'set_numbering_style') {
            $jenis = $_POST['jenis'] ?? 'IPLM';
            $style = $_POST['numbering_style'] ?? 'numeric';
            if (!in_array($jenis, ['IPLM', 'TKM'], true)) throw new Exception('Jenis kuesioner tidak valid');
            if (!in_array($style, ['numeric', 'roman', 'none'], true)) throw new Exception('Gaya penomoran tidak valid');
            
            $setting_key = strtolower($jenis) . '_numbering_style';
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([$setting_key, $style]);
            $_SESSION['flash_message'] = "Gaya penomoran $jenis berhasil diubah menjadi $style.";
            $redirectTab = $jenis === 'TKM' ? 'tkm' : 'iplm';
            $redirectPageIplm = (int)($_POST['page_iplm'] ?? 1);
            $redirectPageTkm = (int)($_POST['page_tkm'] ?? 1);
            $_SESSION['atur_pertanyaan_state'] = [
                'tab' => $redirectTab,
                'page_iplm' => max(1, $redirectPageIplm),
                'page_tkm' => max(1, $redirectPageTkm),
            ];
            header('Location: atur_pertanyaan.php'); exit;
        }

        if (isset($_POST['aksi']) && ($_POST['aksi'] === 'tambah' || $_POST['aksi'] === 'edit')) {
            $jenisQ = $_POST['jenis'] ?? '';
            $bagianQ = $_POST['bagian'] ?? '';
            
            // Normalize inputs to arrays
            $soalInput = $_POST['soal'] ?? [];
            if (!is_array($soalInput)) $soalInput = [$soalInput];
            
            $keteranganInput = $_POST['keterangan'] ?? [];
            if (!is_array($keteranganInput)) $keteranganInput = [$keteranganInput];
            
            $tipeInput = $_POST['tipe'] ?? [];
            if (!is_array($tipeInput)) $tipeInput = [$tipeInput];
            
            $urutanInput = $_POST['urutan'] ?? [];
            if (!is_array($urutanInput)) $urutanInput = [$urutanInput];
            
            $pilihanInput = $_POST['pilihan_opsi'] ?? [];
            if (!is_array($pilihanInput)) $pilihanInput = [$pilihanInput];

            if (empty($jenisQ) || empty($bagianQ)) {
                throw new Exception("Jenis dan Bagian wajib diisi.");
            }

            if ($_POST['aksi'] === 'tambah') {
                $pdo->beginTransaction();
                try {
                    $count = 0;
                    // Get initial Max Order for the group
                    $stmtMax = $pdo->prepare("SELECT MAX(CAST(urutan AS UNSIGNED)) FROM master_pertanyaan WHERE jenis_kuesioner = ? AND kategori_bagian = ?");
                    $stmtMax->execute([$jenisQ, $bagianQ]);
                    $maxExisting = (int)$stmtMax->fetchColumn();
                    $nextAutoUrutan = $maxExisting + 1;

                    foreach ($soalInput as $index => $soalText) {
                        $soal = trim($soalText);
                        if (empty($soal)) continue; // Skip empty questions

                        $ket = $keteranganInput[$index] ?? '';
                        $tipe = $tipeInput[$index] ?? 'text';
                        $pilihan = $pilihanInput[$index] ?? '';
                        
                        $reqUrutan = (int)($urutanInput[$index] ?? 0);
                        
                        $targetUrutan = ($reqUrutan > 0) ? $reqUrutan : $nextAutoUrutan;
                        
                        $stmtCheckMax = $pdo->prepare("SELECT MAX(CAST(urutan AS UNSIGNED)) FROM master_pertanyaan WHERE jenis_kuesioner = ? AND kategori_bagian = ?");
                        $stmtCheckMax->execute([$jenisQ, $bagianQ]);
                        $currentMax = (int)$stmtCheckMax->fetchColumn();
                        
                        if ($targetUrutan <= $currentMax) {
                            $stmtShift = $pdo->prepare("UPDATE master_pertanyaan SET urutan = urutan + 1 WHERE jenis_kuesioner = ? AND kategori_bagian = ? AND CAST(urutan AS UNSIGNED) >= ?");
                            $stmtShift->execute([$jenisQ, $bagianQ, $targetUrutan]);
                            $maxExisting++; 
                        } else {
                            if ($targetUrutan > $currentMax + 1) $targetUrutan = $currentMax + 1;
                        }

                        $sql = "INSERT INTO master_pertanyaan (jenis_kuesioner, kategori_bagian, teks_pertanyaan, keterangan, tipe_input, pilihan_opsi, urutan) VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$jenisQ, $bagianQ, $soal, $ket, $tipe, $pilihan, $targetUrutan]);
                        
                        $count++;
                        
                        // Increment for next loop if using auto
                        if ($reqUrutan <= 0) $nextAutoUrutan = $targetUrutan + 1;
                    }
                    
                    if ($count === 0) throw new Exception("Tidak ada pertanyaan yang ditambahkan.");
                    
                    $pdo->commit();
                    $pesan = "Berhasil menambah $count pertanyaan baru!";
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    throw $e;
                }
            } else {
                // EDIT MODE (Single Question)
                $currentId = (int)($_POST['id'] ?? 0);
                
                // Take the first item from arrays
                $soalQ = $soalInput[0] ?? '';
                $keteranganQ = $keteranganInput[0] ?? '';
                $tipeQ = $tipeInput[0] ?? 'text';
                $urutanQ = (int)($urutanInput[0] ?? 0);
                $pilihanQ = $pilihanInput[0] ?? '';
                
                if (empty($soalQ)) throw new Exception("Teks Pertanyaan wajib diisi.");

                $stmtCurrent = $pdo->prepare("SELECT jenis_kuesioner, kategori_bagian, urutan FROM master_pertanyaan WHERE id = ?");
                $stmtCurrent->execute([$currentId]);
                $current = $stmtCurrent->fetch(PDO::FETCH_ASSOC);
                if (!$current) throw new Exception("Data pertanyaan tidak ditemukan.");

                $pdo->beginTransaction();
                try {
                    $sameGroup = ($current['jenis_kuesioner'] === $jenisQ) && ($current['kategori_bagian'] === $bagianQ);
                    $stmtMax = $pdo->prepare("SELECT MAX(CAST(urutan AS UNSIGNED)) FROM master_pertanyaan WHERE jenis_kuesioner = ? AND kategori_bagian = ? AND id <> ?");
                    $stmtMax->execute([$jenisQ, $bagianQ, $currentId]);
                    $maxPos = (int)$stmtMax->fetchColumn() + 1;
                    if ($urutanQ < 1 || $urutanQ > $maxPos) $urutanQ = $maxPos;

                    if ($sameGroup) {
                        $oldUrutan = (int)$current['urutan'];
                        if ($urutanQ < $oldUrutan) {
                            $stmtShift = $pdo->prepare("UPDATE master_pertanyaan SET urutan = urutan + 1 WHERE jenis_kuesioner = ? AND kategori_bagian = ? AND CAST(urutan AS UNSIGNED) >= ? AND CAST(urutan AS UNSIGNED) < ? AND id <> ?");
                            $stmtShift->execute([$jenisQ, $bagianQ, $urutanQ, $oldUrutan, $currentId]);
                        } elseif ($urutanQ > $oldUrutan) {
                            $stmtShift = $pdo->prepare("UPDATE master_pertanyaan SET urutan = urutan - 1 WHERE jenis_kuesioner = ? AND kategori_bagian = ? AND CAST(urutan AS UNSIGNED) > ? AND CAST(urutan AS UNSIGNED) <= ? AND id <> ?");
                            $stmtShift->execute([$jenisQ, $bagianQ, $oldUrutan, $urutanQ, $currentId]);
                        }
                    } else {
                        $stmtClose = $pdo->prepare("UPDATE master_pertanyaan SET urutan = urutan - 1 WHERE jenis_kuesioner = ? AND kategori_bagian = ? AND CAST(urutan AS UNSIGNED) > ?");
                        $stmtClose->execute([$current['jenis_kuesioner'], $current['kategori_bagian'], (int)$current['urutan']]);
                        $stmtShift = $pdo->prepare("UPDATE master_pertanyaan SET urutan = urutan + 1 WHERE jenis_kuesioner = ? AND kategori_bagian = ? AND CAST(urutan AS UNSIGNED) >= ?");
                        $stmtShift->execute([$jenisQ, $bagianQ, $urutanQ]);
                    }

                    $sql = "UPDATE master_pertanyaan SET jenis_kuesioner=?, kategori_bagian=?, teks_pertanyaan=?, keterangan=?, tipe_input=?, pilihan_opsi=?, urutan=? WHERE id=?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$jenisQ, $bagianQ, $soalQ, $keteranganQ, $tipeQ, $pilihanQ, $urutanQ, $currentId]);
                    $pdo->commit();
                    $pesan = "Data pertanyaan berhasil diperbarui!";
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    throw $e;
                }
            }
        } elseif (isset($_POST['aksi']) && $_POST['aksi'] === 'hapus') {
            $currentId = (int)($_POST['id'] ?? 0);
            $stmtCurrent = $pdo->prepare("SELECT jenis_kuesioner, kategori_bagian, urutan FROM master_pertanyaan WHERE id = ?");
            $stmtCurrent->execute([$currentId]);
            $current = $stmtCurrent->fetch(PDO::FETCH_ASSOC);
            if (!$current) throw new Exception("Data pertanyaan tidak ditemukan.");

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("DELETE FROM master_pertanyaan WHERE id = ?");
                $stmt->execute([$currentId]);
                $stmtShift = $pdo->prepare("UPDATE master_pertanyaan SET urutan = urutan - 1 WHERE jenis_kuesioner = ? AND kategori_bagian = ? AND CAST(urutan AS UNSIGNED) > ?");
                $stmtShift->execute([$current['jenis_kuesioner'], $current['kategori_bagian'], (int)$current['urutan']]);
                $pdo->commit();
                $pesan = "Pertanyaan berhasil dihapus.";
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
        } else {
            // Jika ada aksi lain yang belum di-handle atau bukan POST yang diinginkan
            if (isset($_POST['aksi'])) return; 
        }

        if ($pesan !== "") {
            $_SESSION['flash_message'] = $pesan;
            $redirectTab = $_POST['tab'] ?? (isset($jenisQ) && $jenisQ === 'TKM' ? 'tkm' : 'iplm');
            $redirectPageIplm = (int)($_POST['page_iplm'] ?? 1);
            $redirectPageTkm = (int)($_POST['page_tkm'] ?? 1);
            $_SESSION['atur_pertanyaan_state'] = [
                'tab' => $redirectTab,
                'page_iplm' => max(1, $redirectPageIplm),
                'page_tkm' => max(1, $redirectPageTkm),
            ];
            header("Location: atur_pertanyaan.php"); exit;
        }

    } catch (Exception $e) { 
        $_SESSION['flash_message_error'] = "Error: " . $e->getMessage();
        header("Location: atur_pertanyaan.php"); exit;
    }
}

if (isset($_SESSION['flash_message_error'])) { 
    $pesan = $_SESSION['flash_message_error'];
    $pesan_type = "danger";
    unset($_SESSION['flash_message_error']); 
} elseif (isset($_SESSION['flash_message'])) { 
    $pesan = $_SESSION['flash_message'];
    $pesan_type = "success";
    unset($_SESSION['flash_message']); 
}

// --- 1b. REFRESH URUTAN OTOMATIS (DIPERBAIKI) ---
// Merapikan urutan: Nomor di-reset per kombinasi Jenis Kuesioner + Kategori/Bagian
// Implementasi PHP untuk konsistensi lintas versi MySQL/MariaDB.
try {
    // Ambil semua grup (jenis_kuesioner + kategori_bagian)
    $stmtGroups = $pdo->query("SELECT DISTINCT jenis_kuesioner, kategori_bagian FROM master_pertanyaan ORDER BY jenis_kuesioner, kategori_bagian");
    $groups = $stmtGroups->fetchAll(PDO::FETCH_ASSOC);
    $pdo->beginTransaction();
    $stmtSelect = $pdo->prepare("SELECT id, urutan FROM master_pertanyaan WHERE jenis_kuesioner = ? AND kategori_bagian = ? ORDER BY CAST(urutan AS UNSIGNED), id");
    $stmtUpdate = $pdo->prepare("UPDATE master_pertanyaan SET urutan = ? WHERE id = ?");
    foreach ($groups as $g) {
        $j = $g['jenis_kuesioner'];
        $k = $g['kategori_bagian'];
        $stmtSelect->execute([$j, $k]);
        $rows = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);
        $n = 1;
        foreach ($rows as $r) {
            $id = $r['id'];
            // hanya update jika berbeda untuk mengurangi query
            if ((int)$r['urutan'] !== $n) {
                $stmtUpdate->execute([$n, $id]);
            }
            $n++;
        }
    }
    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // jangan gagalkan halaman jika refresh urutan gagal
}

// --- 2. AMBIL DATA (PAGINASI) ---
$per_page = 50;
$state = $_SESSION['atur_pertanyaan_state'] ?? [];
$page_iplm = max(1, (int)($state['page_iplm'] ?? 1));
$page_tkm = max(1, (int)($state['page_tkm'] ?? 1));
$active_tab = $state['tab'] ?? '';

try {
    $stmtCountI = $pdo->query("SELECT COUNT(*) FROM master_pertanyaan WHERE jenis_kuesioner = 'IPLM'");
    $total_iplm = (int)$stmtCountI->fetchColumn();
} catch (Exception $e) { $total_iplm = 0; }

try {
    $stmtCountT = $pdo->query("SELECT COUNT(*) FROM master_pertanyaan WHERE jenis_kuesioner = 'TKM'");
    $total_tkm = (int)$stmtCountT->fetchColumn();
} catch (Exception $e) { $total_tkm = 0; }

$total_pages_iplm = max(1, (int)ceil($total_iplm / $per_page));
$total_pages_tkm = max(1, (int)ceil($total_tkm / $per_page));

$page_iplm = min($page_iplm, $total_pages_iplm);
$page_tkm = min($page_tkm, $total_pages_tkm);

$offset_iplm = ($page_iplm - 1) * $per_page;
$offset_tkm = ($page_tkm - 1) * $per_page;

$sqlIplm = "SELECT m.* FROM master_pertanyaan m "
    . "LEFT JOIN kategori_bagian kb ON kb.jenis_kuesioner = m.jenis_kuesioner AND kb.name = m.kategori_bagian "
    . "WHERE m.jenis_kuesioner = 'IPLM' "
    . "ORDER BY COALESCE(kb.position, 9999) ASC, m.kategori_bagian ASC, CAST(m.urutan AS UNSIGNED) ASC, m.id ASC "
    . "LIMIT " . intval($per_page) . " OFFSET " . intval($offset_iplm);
$stmtIplm = $pdo->prepare($sqlIplm);
$stmtIplm->execute();
$data_iplm = $stmtIplm->fetchAll(PDO::FETCH_ASSOC);

$sqlTkm = "SELECT m.* FROM master_pertanyaan m "
    . "LEFT JOIN kategori_bagian kb ON kb.jenis_kuesioner = m.jenis_kuesioner AND kb.name = m.kategori_bagian "
    . "WHERE m.jenis_kuesioner = 'TKM' "
    . "ORDER BY COALESCE(kb.position, 9999) ASC, m.kategori_bagian ASC, CAST(m.urutan AS UNSIGNED) ASC, m.id ASC "
    . "LIMIT " . intval($per_page) . " OFFSET " . intval($offset_tkm);
$stmtTkm = $pdo->prepare($sqlTkm);
$stmtTkm->execute();
$data_tkm = $stmtTkm->fetchAll(PDO::FETCH_ASSOC);

// --- 2b. MAX URUTAN PER KATEGORI (UNTUK TOMBOL TAMBAH DI BAGIAN) ---
$maxUrutanIplm = [];
try {
    $stmtMaxI = $pdo->prepare("SELECT kategori_bagian, MAX(CAST(urutan AS UNSIGNED)) AS max_urutan FROM master_pertanyaan WHERE jenis_kuesioner = 'IPLM' GROUP BY kategori_bagian");
    $stmtMaxI->execute();
    $rows = $stmtMaxI->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $key = $r['kategori_bagian'] ?? '';
        if ($key !== '') $maxUrutanIplm[$key] = (int)$r['max_urutan'];
    }
} catch (Exception $e) { $maxUrutanIplm = []; }

$maxUrutanTkm = [];
try {
    $stmtMaxT = $pdo->prepare("SELECT kategori_bagian, MAX(CAST(urutan AS UNSIGNED)) AS max_urutan FROM master_pertanyaan WHERE jenis_kuesioner = 'TKM' GROUP BY kategori_bagian");
    $stmtMaxT->execute();
    $rows = $stmtMaxT->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $key = $r['kategori_bagian'] ?? '';
        if ($key !== '') $maxUrutanTkm[$key] = (int)$r['max_urutan'];
    }
} catch (Exception $e) { $maxUrutanTkm = []; }

// --- 3. SETTING KONTAK IPLM ---
$kontak_setting_id = '';
try {
    $stmtSettingKontak = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'iplm_kontak_pertanyaan_id' LIMIT 1");
    $stmtSettingKontak->execute();
    $kontak_setting_id = $stmtSettingKontak->fetchColumn();
} catch (Exception $e) { $kontak_setting_id = ''; }

// --- 3.5 GLOBAL NUMBERING STYLE SETTINGS ---
$numbering_style_iplm = 'numeric';
$numbering_style_tkm = 'numeric';
try {
    $stmtSettings = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('iplm_numbering_style', 'tkm_numbering_style')");
    $stmtSettings->execute();
    $styleRows = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
    $numbering_style_iplm = $styleRows['iplm_numbering_style'] ?? 'numeric';
    $numbering_style_tkm = $styleRows['tkm_numbering_style'] ?? 'numeric';
} catch (Exception $e) {
    // Default values
}

$list_iplm_questions = [];
try {
    $stmtAllIplm = $pdo->prepare("SELECT id, kategori_bagian, teks_pertanyaan FROM master_pertanyaan WHERE jenis_kuesioner = 'IPLM' ORDER BY CAST(urutan AS UNSIGNED) ASC");
    $stmtAllIplm->execute();
    $list_iplm_questions = $stmtAllIplm->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $list_iplm_questions = []; }

$autofill_jenis_id = '';
$autofill_subjenis_id = '';
$autofill_nama_id = '';
try {
    $stmtAuto = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('iplm_autofill_jenis_id','iplm_autofill_subjenis_id','iplm_autofill_nama_id')");
    $stmtAuto->execute();
    $autoRows = $stmtAuto->fetchAll(PDO::FETCH_KEY_PAIR);
    $autofill_jenis_id = $autoRows['iplm_autofill_jenis_id'] ?? '';
    $autofill_subjenis_id = $autoRows['iplm_autofill_subjenis_id'] ?? '';
    $autofill_nama_id = $autoRows['iplm_autofill_nama_id'] ?? '';
} catch (Exception $e) {}

// Ambil daftar kategori yang dikelola
$kategori_iplm = [];
$kategori_tkm = [];
try {
    $stmtKat = $pdo->prepare("SELECT id, jenis_kuesioner, name, position, numbering_style FROM kategori_bagian ORDER BY jenis_kuesioner, position ASC");
    $stmtKat->execute();
    $krows = $stmtKat->fetchAll(PDO::FETCH_ASSOC);
    foreach ($krows as $kr) {
        if ($kr['jenis_kuesioner'] === 'TKM') $kategori_tkm[] = $kr;
        else $kategori_iplm[] = $kr;
    }
} catch (Exception $e) {
    $kategori_iplm = []; $kategori_tkm = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pertanyaan - DISARPUS</title>
    <!-- Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- GovTech Theme -->
    <link rel="stylesheet" href="../assets/govtech.css">
    <link rel="stylesheet" href="../assets/admin-readability.css">
    <link rel="stylesheet" href="../assets/loader.css">
    <style>
        /* Tab navigation styling */
        .nav-pills .nav-link {
            color: #334155;
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }
        .nav-pills .nav-link:hover {
            background-color: #e2e8f0;
            color: #0f172a;
        }
        .nav-pills .nav-link.active {
            background: #4f6cf6;
            color: #fff;
            border-color: transparent;
            box-shadow: 0 4px 10px rgba(79, 108, 246, 0.3);
            border-radius: 50px !important;
        }
        .btn-primary {
            background-color: #4f6cf6;
            border-color: #4f6cf6;
        }
        .btn-primary:hover {
            background-color: #3d59e0;
            border-color: #3d59e0;
        }

    </style>
</head>
<body>
    <?php include __DIR__ . '/../config/loader.php'; ?>
    <div class="bg-govtech"></div>
    
    <div class="sidebar-backdrop" onclick="document.body.classList.remove('sidebar-open')"></div>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <h6 class="mb-0 fw-bold">ADMIN PANEL</h6>
            </div>
            <button class="btn btn-sm btn-light d-lg-none" onclick="document.body.classList.remove('sidebar-open')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <div class="nav flex-column gap-1">
            <div class="sidebar-label">Utama</div>
            <a href="dashboard.php" class="nav-link">
                <i class="bi bi-grid-fill"></i>
                <span>Dashboard</span>
            </a>
            <a href="perpustakaan.php" class="nav-link">
                <i class="bi bi-building"></i>
                <span>Perpustakaan</span>
            </a>
            
            <div class="sidebar-label mt-3">Pelaporan</div>
            <a href="hasil_kuisioner.php" class="nav-link">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Hasil Kuesioner</span>
            </a>
            <a href="atur_pertanyaan.php" class="nav-link active">
                <i class="bi bi-gear-wide-connected"></i>
                <span>Atur Pertanyaan</span>
            </a>
            <a href="pengaduan.php" class="nav-link">
                <i class="bi bi-chat-left-text-fill"></i>
                <span>Pengaduan</span>
            </a>

            <div class="sidebar-label mt-3">Sistem</div>
            <a href="users.php" class="nav-link">
                <i class="bi bi-people-fill"></i>
                <span>Admin Users</span>
            </a>
            <a href="logout.php" class="nav-link text-danger mt-3">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar</span>
            </a>
        </div>
    </nav>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4 page-header my-4">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-dark btn-sm d-lg-none" onclick="document.body.classList.add('sidebar-open')"><i class="bi bi-list"></i></button>
                <div>
                    <h1 class="h2 fw-bold m-0 page-title">Manajemen Pertanyaan</h1>
                    <p class="text-muted m-0 page-subtitle">Atur struktur dan konten kuesioner IPLM & TKM</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-dark rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalManageBagian">
                    <i class="bi bi-collection me-2"></i> Kelola Bagian
                </button>
                <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm text-white" onclick="bukaModalTambah()">
                    <i class="bi bi-plus-lg me-2"></i> Tambah Soal
                </button>
            </div>
        </div>

        <?php if($pesan): ?>
            <div class="alert alert-<?= $pesan_type ?> alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-<?= $pesan_type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2 fs-5"></i>
                    <div><?= $pesan ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card-clean p-0 overflow-hidden">
            <div class="px-4 pt-4 pb-0">
                <ul class="nav nav-pills gap-2 mb-3" id="myTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active rounded-pill px-4 py-2 fw-bold" data-bs-toggle="tab" data-bs-target="#tab-iplm"
                            style="font-size: 0.95rem;">
                            <i class="bi bi-journal-text me-2"></i>IPLM (Literasi)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link rounded-pill px-4 py-2 fw-bold" data-bs-toggle="tab" data-bs-target="#tab-tkm"
                            style="font-size: 0.95rem;">
                            <i class="bi bi-book-half me-2"></i>TKM (Membaca)
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body p-4 bg-white">
                <div class="tab-content">
                    <!-- TAB IPLM -->
                    <div class="tab-pane fade show active" id="tab-iplm">
                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <div class="card h-100 border rounded-4 shadow-sm">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-start gap-3 mb-4">
                                            <div class="bg-primary-subtle text-primary rounded-circle p-3">
                                                <i class="bi bi-telephone-fill fs-4"></i>
                                            </div>
                                            <div>
                                                <h5 class="fw-bold mb-1">Kontak Validasi</h5>
                                                <p class="text-muted small mb-0">Pertanyaan unik untuk mencegah duplikasi responden (misal: No. HP/Email).</p>
                                            </div>
                                        </div>
                                        <form method="POST" class="d-flex gap-2">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="aksi" value="set_kontak_iplm">
                                            <input type="hidden" name="page_iplm" value="<?= $page_iplm ?>">
                                            <input type="hidden" name="page_tkm" value="<?= $page_tkm ?>">
                                            <select name="kontak_pertanyaan_id" class="form-select bg-light border-0" required>
                                                <option value="" disabled <?= empty($kontak_setting_id) ? 'selected' : '' ?>>-- Pilih Soal Kontak --</option>
                                                <?php foreach ($list_iplm_questions as $q): ?>
                                                    <option value="<?= $q['id'] ?>" <?= ((string)$kontak_setting_id === (string)$q['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($q['teks_pertanyaan']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-dark px-4"><i class="bi bi-check-lg"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card h-100 border rounded-4 shadow-sm">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-start gap-3 mb-4">
                                            <div class="bg-info-subtle text-info rounded-circle p-3">
                                                <i class="bi bi-magic fs-4"></i>
                                            </div>
                                            <div>
                                                <h5 class="fw-bold mb-1">Auto-fill Identitas</h5>
                                                <p class="text-muted small mb-0">Otomatis isi data perpustakaan berdasarkan pemilihan nama perpustakaan.</p>
                                            </div>
                                        </div>
                                        <form method="POST" class="row g-2">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="aksi" value="set_autofill_iplm">
                                            <div class="col-12">
                                                <div class="mb-2">
                                                    <label class="form-label fw-bold text-primary mb-1" style="font-size: 0.7rem;">KOLOM 1: JENIS</label>
                                                    <select name="autofill_jenis_id" class="form-select form-select-sm bg-light border-0" required>
                                                        <option value="" disabled <?= empty($autofill_jenis_id) ? 'selected' : '' ?>>-- Pilih Soal Jenis Perpustakaan --</option>
                                                        <?php foreach ($list_iplm_questions as $q): ?><option value="<?= $q['id'] ?>" <?= ((string)$autofill_jenis_id === (string)$q['id']) ? 'selected' : '' ?>><?= htmlspecialchars($q['teks_pertanyaan']) ?></option><?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label fw-bold text-primary mb-1" style="font-size: 0.7rem;">KOLOM 2: KATEGORI / SUBJENIS</label>
                                                    <select name="autofill_subjenis_id" class="form-select form-select-sm bg-light border-0" required>
                                                        <option value="" disabled <?= empty($autofill_subjenis_id) ? 'selected' : '' ?>>-- Pilih Soal Kategori Perpustakaan --</option>
                                                        <?php foreach ($list_iplm_questions as $q): ?><option value="<?= $q['id'] ?>" <?= ((string)$autofill_subjenis_id === (string)$q['id']) ? 'selected' : '' ?>><?= htmlspecialchars($q['teks_pertanyaan']) ?></option><?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label fw-bold text-primary mb-1" style="font-size: 0.7rem;">KOLOM 3: NAMA PERPUSTAKAAN</label>
                                                    <select name="autofill_nama_id" class="form-select form-select-sm bg-light border-0" required>
                                                        <option value="" disabled <?= empty($autofill_nama_id) ? 'selected' : '' ?>>-- Pilih Soal Nama Perpustakaan --</option>
                                                        <?php foreach ($list_iplm_questions as $q): ?><option value="<?= $q['id'] ?>" <?= ((string)$autofill_nama_id === (string)$q['id']) ? 'selected' : '' ?>><?= htmlspecialchars($q['teks_pertanyaan']) ?></option><?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-12 text-end">
                                                <button type="submit" class="btn btn-sm btn-info fw-bold text-white">Simpan Pengaturan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 border">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="bi bi-list-ol fs-4 text-muted"></i>
                                        <div>
                                             <span class="fw-bold d-block">Gaya Penomoran Halaman</span>
                                             <small class="text-muted">Format nomor untuk bagian/header di kuesioner IPLM</small>
                                        </div>
                                    </div>
                                    <form method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="aksi" value="set_numbering_style">
                                        <input type="hidden" name="jenis" value="IPLM">
                                        <select name="numbering_style" class="form-select form-select-sm" style="width: auto;">
                                            <option value="numeric" <?= ($numbering_style_iplm === 'numeric') ? 'selected' : '' ?>>1, 2, 3 ...</option>
                                            <option value="roman" <?= ($numbering_style_iplm === 'roman') ? 'selected' : '' ?>>I, II, III ...</option>
                                            <option value="none" <?= ($numbering_style_iplm === 'none') ? 'selected' : '' ?>>Tanpa Nomor</option>
                                        </select>
                                        <button class="btn btn-sm btn-dark">Ubah</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <?php renderTable($data_iplm, 'iplm', $page_iplm, $total_pages_iplm, $page_tkm, $maxUrutanIplm, $numbering_style_iplm); ?>
                    </div>

                    <!-- TAB TKM -->
                    <div class="tab-pane fade" id="tab-tkm">
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 border">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="bi bi-list-ol fs-4 text-muted"></i>
                                        <div>
                                             <span class="fw-bold d-block">Gaya Penomoran Halaman</span>
                                             <small class="text-muted">Format nomor untuk bagian/header di kuesioner TKM</small>
                                        </div>
                                    </div>
                                    <form method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="aksi" value="set_numbering_style">
                                        <input type="hidden" name="jenis" value="TKM">
                                        <select name="numbering_style" class="form-select form-select-sm" style="width: auto;">
                                            <option value="numeric" <?= ($numbering_style_tkm === 'numeric') ? 'selected' : '' ?>>1, 2, 3 ...</option>
                                            <option value="roman" <?= ($numbering_style_tkm === 'roman') ? 'selected' : '' ?>>I, II, III ...</option>
                                            <option value="none" <?= ($numbering_style_tkm === 'none') ? 'selected' : '' ?>>Tanpa Nomor</option>
                                        </select>
                                        <button class="btn btn-sm btn-dark">Ubah</button>
                                    </form>
                                </div>
                        </div>
                        <?php renderTable($data_tkm, 'tkm', $page_tkm, $total_pages_tkm, $page_iplm, $maxUrutanTkm, $numbering_style_tkm); ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="modalForm" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-dark text-white rounded-top-4 border-0">
                    <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-question-circle me-2"></i>Form Pertanyaan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <ul class="nav nav-pills nav-fill px-4 pt-4 gap-2" id="modalTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold border" id="tab-single" data-bs-toggle="pill" data-bs-target="#pane-single" type="button"><i class="bi bi-pencil me-2"></i>Input Manual</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold border" id="tab-import" data-bs-toggle="pill" data-bs-target="#pane-import" type="button"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Import CSV</button>
                        </li>
                    </ul>
                    <div class="tab-content px-4 pb-4 pt-3">
                        <div class="tab-pane fade show active" id="pane-single">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="aksi" id="form_aksi" value="tambah">
                                <input type="hidden" name="id" id="form_id">
                                <input type="hidden" name="tab" id="form_tab" value="iplm">
                                <input type="hidden" name="page_iplm" id="form_page_iplm" value="<?= $page_iplm ?>">
                                <input type="hidden" name="page_tkm" id="form_page_tkm" value="<?= $page_tkm ?>">
                                
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-uppercase text-muted">Jenis Kuesioner</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-journal-check"></i></span>
                                            <select name="jenis" id="form_jenis" class="form-select bg-light border-start-0 ps-0" required>
                                                <option value="IPLM">IPLM</option>
                                                <option value="TKM">TKM</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold small text-uppercase text-muted">Kategori / Bagian</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-folder2-open"></i></span>
                                            <select name="bagian" id="form_bagian" class="form-select bg-light border-start-0 ps-0" required>
                                                <option value="" disabled selected>-- Pilih Bagian --</option>
                                                <?php foreach ($kategori_iplm as $kb): ?>
                                                    <option value="<?= htmlspecialchars($kb['name']) ?>" data-jenis="IPLM"><?= htmlspecialchars($kb['name']) ?></option>
                                                <?php endforeach; ?>
                                                <?php foreach ($kategori_tkm as $kb): ?>
                                                    <option value="<?= htmlspecialchars($kb['name']) ?>" data-jenis="TKM"><?= htmlspecialchars($kb['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-4">
                                        <div id="questions_container">
                                            <!-- Rows will be added here by JS -->
                                        </div>
                                        
                                        <div class="d-grid mt-3" id="btn_add_more_container">
                                            <button type="button" class="btn btn-primary fw-bold py-2 rounded-3 shadow-sm text-white" onclick="addQuestionRow()">
                                                <i class="bi bi-plus-circle me-2"></i>Tambah Pertanyaan Lain
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer border-top-0 px-0 pb-0 mt-4">
                                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm"><i class="bi bi-save me-2"></i>Simpan Data</button>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="pane-import">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="aksi" value="import_csv">
                                <div class="mb-4 text-center py-4 bg-light rounded-3 border border-dashed">
                                    <i class="bi bi-cloud-upload fs-1 text-muted mb-2"></i>
                                    <h6 class="fw-bold">Upload File CSV</h6>
                                    <p class="text-muted small mb-3">Pilih file CSV sesuai format template</p>
                                    <input type="file" name="file_csv" class="form-control w-75 mx-auto" accept=".csv" required>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <span class="badge bg-info text-dark rounded-pill"><i class="bi bi-info-circle me-1"></i>Format Wajib</span>
                                    </div>
                                    <a href="#" onclick="downloadTemplateSoal(event)" class="btn btn-sm btn-outline-success rounded-pill fw-bold"><i class="bi bi-download me-1"></i> Download Template</a>
                                </div>
                                
                                <div class="alert alert-light border shadow-sm small mb-3">
                                    <div class="row g-2">
                                        <div class="col-12"><span class="badge bg-dark mb-1">Kolom 1:</span> <code>jenis_kuesioner</code> (IPLM / TKM)</div>
                                        <div class="col-12"><span class="badge bg-dark mb-1">Kolom 2:</span> <code>kategori_bagian</code> (Bagian Soal)</div>
                                        <div class="col-12"><span class="badge bg-dark mb-1">Kolom 3:</span> <code>teks_pertanyaan</code> (Inti Soal)</div>
                                        <div class="col-12"><span class="badge bg-dark mb-1">Kolom 4:</span> <code>keterangan</code> (Opsional)</div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="text-muted" style="font-size: 0.8rem;">
                                        Kolom 5-7 (Opsional): <code>tipe_input, pilihan_opsi, urutan</code>
                                    </div>
                                </div>
                                
                                <div class="modal-footer border-top-0 px-0 pb-0">
                                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-dark fw-bold rounded-pill px-4 shadow-sm"><i class="bi bi-upload me-2"></i>Import Sekarang</button>
                                </div>
                            </form>
                        </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal: Kelola Bagian (Moved Outside) -->
    <div class="modal fade" id="modalManageBagian" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-dark text-white rounded-top-4 border-0">
                    <h5 class="modal-title fw-bold" id="manageBagianTitle"><i class="bi bi-folder2-open me-2 text-white"></i><span class="text-white">Kelola Bagian</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <ul class="nav nav-pills nav-fill mb-4 p-1 bg-light rounded-pill" id="manageBagianTab" role="tablist">
                        <li class="nav-item"><button class="nav-link active rounded-pill fw-bold" id="tab-iplm-bagian" data-bs-toggle="pill" data-bs-target="#pane-iplm-bagian" type="button">IPLM (Literasi)</button></li>
                        <li class="nav-item"><button class="nav-link rounded-pill fw-bold" id="tab-tkm-bagian" data-bs-toggle="pill" data-bs-target="#pane-tkm-bagian" type="button">TKM (Membaca)</button></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="pane-iplm-bagian">
                            <form method="POST" class="row g-2 mb-4 align-items-end">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="aksi" value="add_bagian">
                                <input type="hidden" name="jenis_bagian" value="IPLM">
                                <div class="col-md-9">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Nama Bagian Baru (IPLM)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-plus-square"></i></span>
                                        <input type="text" name="nama_bagian" class="form-control" required placeholder="Contoh: Identitas Responden">
                                    </div>
                                </div>
                                <div class="col-md-3 d-grid">
                                    <button class="btn btn-dark rounded-pill fw-bold" type="submit">Tambah</button>
                                </div>
                            </form>
                            <div class="list-group rounded-3 shadow-sm">
                                <?php foreach ($kategori_iplm as $kb): ?>
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3 border-start-0 border-end-0">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-light rounded-circle p-2 text-muted fw-bold border" style="width:35px;height:35px;display:flex;align-items:center;justify-content:center;"><?= (int)$kb['position'] ?></div>
                                            <strong><?= htmlspecialchars($kb['name']) ?></strong>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-white border hover-bg-light rounded-pill px-3" onclick="openEditBagian(<?= (int)$kb['id'] ?>, <?= htmlspecialchars(json_encode($kb['jenis_kuesioner']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($kb['name']), ENT_QUOTES) ?>, <?= (int)$kb['position'] ?>)" title="Edit bagian"><i class="bi bi-pencil-square"></i></button>
                                            <form method="POST" class="d-inline js-confirm" data-confirm-title="Hapus bagian dan isinya?" data-confirm-text="PERINGATAN: Bagian '<?= htmlspecialchars($kb['name']) ?>' akan dihapus BESERTA SEMUA PERTANYAAN DI DALAMNYA. Tindakan ini tidak bisa dibatalkan." data-confirm-button="Ya, hapus semuanya">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="aksi" value="delete_bagian">
                                                <input type="hidden" name="bagian_id" value="<?= (int)$kb['id'] ?>">
                                                <button class="btn btn-sm btn-white border hover-bg-light rounded-pill px-3 text-danger" type="submit"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="pane-tkm-bagian">
                            <form method="POST" class="row g-2 mb-4 align-items-end">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="aksi" value="add_bagian">
                                <input type="hidden" name="jenis_bagian" value="TKM">
                                <div class="col-md-9">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Nama Bagian Baru (TKM)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-plus-square"></i></span>
                                        <input type="text" name="nama_bagian" class="form-control" required placeholder="Contoh: Ketersediaan Koleksi">
                                    </div>
                                </div>
                                <div class="col-md-3 d-grid">
                                    <button class="btn btn-dark rounded-pill fw-bold" type="submit">Tambah</button>
                                </div>
                            </form>
                            <div class="list-group rounded-3 shadow-sm">
                                <?php foreach ($kategori_tkm as $kb): ?>
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3 border-start-0 border-end-0">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-light rounded-circle p-2 text-muted fw-bold border" style="width:35px;height:35px;display:flex;align-items:center;justify-content:center;"><?= (int)$kb['position'] ?></div>
                                            <strong><?= htmlspecialchars($kb['name']) ?></strong>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-white border hover-bg-light rounded-pill px-3" onclick="openEditBagian(<?= (int)$kb['id'] ?>, <?= htmlspecialchars(json_encode($kb['jenis_kuesioner']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($kb['name']), ENT_QUOTES) ?>, <?= (int)$kb['position'] ?>)" title="Edit bagian"><i class="bi bi-pencil-square"></i></button>
                                            <form method="POST" class="d-inline js-confirm" data-confirm-title="Hapus bagian dan isinya?" data-confirm-text="PERINGATAN: Bagian '<?= htmlspecialchars($kb['name']) ?>' akan dihapus BESERTA SEMUA PERTANYAAN DI DALAMNYA. Tindakan ini tidak bisa dibatalkan." data-confirm-button="Ya, hapus semuanya">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="aksi" value="delete_bagian">
                                                <input type="hidden" name="bagian_id" value="<?= (int)$kb['id'] ?>">
                                                <button class="btn btn-sm btn-white border hover-bg-light rounded-pill px-3 text-danger" type="submit"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Edit Bagian (Desain Baru) -->
    <div class="modal fade" id="modalEditBagian" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-dark text-white rounded-top-4 border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-white"></i><span class="text-white">Edit Bagian</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 pt-4">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="aksi" value="edit_bagian">
                        <input type="hidden" name="bagian_id" id="edit_bagian_id">
                        
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold text-uppercase mb-1">Jenis Kuesioner</label>
                            <input type="text" class="form-control-plaintext fw-bold text-dark pt-0 pb-0" id="edit_bagian_jenis" readonly style="font-size: 1.1rem;">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Posisi Urutan</label>
                            <input type="number" name="posisi_bagian" id="edit_bagian_posisi" class="form-control bg-light border-0" required style="padding: 12px;">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Nama Bagian</label>
                            <input type="text" name="nama_bagian" id="edit_bagian_nama" class="form-control bg-light border-0" required placeholder="Nama bagian..." style="padding: 12px;">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-dark fw-bold py-2 rounded-pill shadow-sm">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php
    function int_to_roman($num) {
        $n = intval($num);
        $map = [1000=>'M',900=>'CM',500=>'D',400=>'CD',100=>'C',90=>'XC',50=>'L',40=>'XL',10=>'X',9=>'IX',5=>'V',4=>'IV',1=>'I'];
        $res = '';
        foreach ($map as $val => $sy) {
            while ($n >= $val) { $res .= $sy; $n -= $val; }
        }
        return $res ?: '0';
    }

    // Overwrite renderTable to include prefixing based on kategori_bagian settings
    function renderTable($dataset, $tab, $page, $total_pages, $other_page, $maxMap = [], $globalNumberingStyle = 'numeric') {
        global $kategori_iplm, $kategori_tkm;
        // build map name -> position (jangan include style lagi, pakai global style)
        $map = [];
        // FIX: Only use categories for the current tab/type to avoid numbering conflicts
        $cats = ($tab === 'tkm') ? $kategori_tkm : $kategori_iplm;
        foreach ($cats as $k) { $map[$k['name']] = (int)$k['position']; }
        ?>
        <div class="mb-3">
            <form id="bulkForm-<?= $tab ?>" method="POST" class="js-confirm" 
                data-confirm-title="Hapus Terpilih?" 
                data-confirm-text="Apakah Anda yakin ingin menghapus data pertanyaan yang dipilih? Tindakan ini tidak dapat dibatalkan." 
                data-confirm-button="Ya, Hapus Semua">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="aksi" value="bulk_delete">
                <input type="hidden" name="tab" value="<?= $tab ?>">
                <input type="hidden" name="page_iplm" value="<?= ($tab === 'iplm') ? $page : $other_page ?>">
                <input type="hidden" name="page_tkm" value="<?= ($tab === 'tkm') ? $page : $other_page ?>">
            
            <!-- Bulk Actions: Static Integrated Layout -->
            <div id="bulkActions-<?= $tab ?>" class="d-none mb-2">
                <div class="d-flex justify-content-end">
                    <div class="bg-light border rounded-3 p-2 px-3 d-flex align-items-center gap-3">
                        <span class="fw-bold text-dark small"><i class="bi bi-check2-circle text-primary me-2"></i><span id="selectedCount-<?= $tab ?>">0</span> Pertanyaan Terpilih</span>
                        <div class="vr mx-1"></div>
                        <button type="submit" class="btn btn-sm btn-danger px-4 fw-bold rounded-2">Hapus Terpilih</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive rounded-4 border shadow-sm">
                <table class="table table-hover align-middle mb-0" style="table-layout: fixed; width: 100%;">
                    <thead class="bg-light">
                    <tr>
                        <th style="width: 50px;" class="text-center py-3">
                             <input class="form-check-input border-dark" type="checkbox" onchange="toggleSelectAll(this, '<?= $tab ?>')">
                        </th>
                        <th style="width: 5%;" class="text-center py-3 text-uppercase small fw-bold text-muted">No</th>
                        <th style="width: 20%;" class="py-3 text-uppercase small fw-bold text-muted">Kategori</th>
                        <th style="width: 45%;" class="py-3 text-uppercase small fw-bold text-muted">Pertanyaan</th>
                        <th style="width: 15%;" class="py-3 text-uppercase small fw-bold text-muted">Tipe</th>
                        <th style="width: 10%;" class="text-center py-3 text-uppercase small fw-bold text-muted">Aksi</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if(empty($dataset)): ?><tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Data pertanyaan belum tersedia</td></tr><?php else: ?>
                    <?php
                        $lastKategori = null;
                        foreach($dataset as $row):
                            $kategori = $row['kategori_bagian'] ?? '';
                            if ($kategori !== $lastKategori):
                                $maxUrut = $maxMap[$kategori] ?? 0;
                                $prefix = '';
                                if (isset($map[$kategori])) {
                                    $p = $map[$kategori];
                                    $style = $globalNumberingStyle; 
                                    if ($style === 'roman' && $p > 0) $prefix = int_to_roman($p) . '. ';
                                    elseif ($style === 'numeric' && $p > 0) $prefix = $p . '. ';
                                    else $prefix = '';
                                }
                    ?>
                        <tr class="table-light">
                            <td colspan="6" class="py-3 px-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary"><i class="bi bi-bookmark-fill me-2"></i><?= htmlspecialchars($prefix . $kategori) ?></span>
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold text-white"
                                        onclick='bukaModalTambah(<?= json_encode($kategori) ?>, <?= json_encode($tab) ?>, <?= (int)$maxUrut + 1 ?>)'>
                                        <i class="bi bi-plus-lg me-1"></i> Tambah Soal
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php
                                $lastKategori = $kategori;
                                endif;
                    ?>
                        <tr>
                            <td class="text-center">
                                <input class="form-check-input border-secondary check-item-<?= $tab ?>" type="checkbox" name="ids[]" value="<?= $row['id'] ?>" onchange="checkSelection('<?= $tab ?>')">
                            </td>
                            <td class="text-center fw-bold text-secondary"><?= $row['urutan'] ?></td>
                            <td><small class="fw-bold text-dark"><?= htmlspecialchars($row['kategori_bagian']) ?></small></td>
                            <td style="word-wrap: break-word; white-space: normal;" class="py-3">
                                <div class="fw-medium text-dark"><?= htmlspecialchars($row['teks_pertanyaan']) ?></div>
                                <?php if($row['keterangan']): ?><small class="text-muted d-block mt-1"><i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($row['keterangan']) ?></small><?php endif; ?>
                                <?php if(($row['tipe_input'] == 'select' || $row['tipe_input'] == 'radio') && $row['pilihan_opsi']): ?>
                                    <div class="mt-2 text-wrap"><span class="badge bg-primary-subtle text-primary border border-primary-subtle text-wrap text-start lh-base" style="white-space: normal;"><i class="bi bi-option me-1"></i>Opsi: <?= htmlspecialchars($row['pilihan_opsi']) ?></span></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark border fw-normal px-3 py-2 rounded-pill"><?= strtoupper($row['tipe_input']) ?></span></td>
                            <td class="text-center">
                                <div class="btn-group shadow-sm rounded-pill" role="group">
                                    <button class="btn btn-sm btn-white border hover-bg-light" onclick='editData(<?= json_encode($row) ?>)' title="Edit"><i class="bi bi-pencil-square text-warning"></i></button>
                                    <button type="button" class="btn btn-sm btn-white border hover-bg-light text-danger" 
                                        onclick="hapusSatu(<?= $row['id'] ?>, '<?= $tab ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            </form>
        </div>
    </div>
        
        <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination pagination-sm justify-content-center m-0 gap-1">
                <!-- PREV -->
                <?php
                $prevPage = max(1, $page-1);
                $pIplm = ($tab === 'iplm') ? $prevPage : $other_page;
                $pTkm = ($tab === 'tkm') ? $prevPage : $other_page;
                $prevUrl = "atur_pertanyaan.php?tab={$tab}&page_iplm={$pIplm}&page_tkm={$pTkm}";
                ?>
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link rounded px-3 border-0 shadow-sm" href="<?= ($page <= 1) ? '#' : $prevUrl ?>" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a>
                </li>
                
                <!-- NUMBERED LINKS -->
                <?php
                $range = 2; 
                $start = max(1, $page - $range);
                $end = min($total_pages, $page + $range);
                
                if ($start > 1) {
                    echo '<li class="page-item"><span class="page-link border-0 text-muted">...</span></li>';
                }

                for ($i = $start; $i <= $end; $i++):
                    $isActive = ($i == $page);
                    $pIplm = ($tab === 'iplm') ? $i : $other_page;
                    $pTkm = ($tab === 'tkm') ? $i : $other_page;
                    $url = "atur_pertanyaan.php?tab={$tab}&page_iplm={$pIplm}&page_tkm={$pTkm}";
                ?>
                    <li class="page-item <?= $isActive ? 'active' : '' ?>">
                        <?php if ($isActive): ?>
                            <span class="page-link bg-dark text-white border-0 shadow-sm rounded fw-bold px-3"><?= $i ?></span>
                        <?php else: ?>
                            <a class="page-link text-dark border-0 shadow-sm rounded px-3 hover-bg-light" href="<?= $url ?>"><?= $i ?></a>
                        <?php endif; ?>
                    </li>
                <?php endfor; 
                
                if ($end < $total_pages) {
                    echo '<li class="page-item"><span class="page-link border-0 text-muted">...</span></li>';
                }
                
                // NEXT
                $nextPage = min($total_pages, $page+1);
                $pIplm = ($tab === 'iplm') ? $nextPage : $other_page;
                $pTkm = ($tab === 'tkm') ? $nextPage : $other_page;
                $nextUrl = "atur_pertanyaan.php?tab={$tab}&page_iplm={$pIplm}&page_tkm={$pTkm}";
                ?>
                
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link rounded px-3 border-0 shadow-sm" href="<?= ($page >= $total_pages) ? '#' : $nextUrl ?>" aria-label="Next"><span aria-hidden="true">&raquo;</span></a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
        <div class="text-center mt-3 small text-muted font-monospace">Halaman <?= $page ?> dari <?= $total_pages ?></div>
    <?php } ?>

    <!-- Hidden Form for Single Delete (To avoid nested forms) -->
    <form id="singleDeleteForm" method="POST" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="aksi" value="hapus">
        <input type="hidden" name="id" id="delete_id">
        <input type="hidden" name="tab" id="delete_tab">
        <input type="hidden" name="page_iplm" value="<?= $page_iplm ?>">
        <input type="hidden" name="page_tkm" value="<?= $page_tkm ?>">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function bindConfirmForms(root = document) {
            root.querySelectorAll('form.js-confirm').forEach((form) => {
                if (form.dataset.confirmBound === '1') return;
                form.dataset.confirmBound = '1';
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const title = form.dataset.confirmTitle || 'Yakin?';
                    const text = form.dataset.confirmText || 'Tindakan ini tidak dapat dibatalkan.';
                    const confirmButton = form.dataset.confirmButton || 'Ya, lanjutkan';
                    if (window.Swal) {
                        Swal.fire({
                            title,
                            text,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: confirmButton,
                            cancelButtonText: 'Batal',
                            confirmButtonColor: '#4f6cf6',
                            cancelButtonColor: '#6c757d',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) form.submit();
                        });
                    } else if (confirm(text)) {
                        form.submit();
                    }
                });
            });
        }

        function toggleSidebar(open) {
            document.body.classList.toggle('sidebar-open', open);
        }

        document.querySelectorAll('.sidebar .nav-link').forEach((link) => {
            link.addEventListener('click', () => toggleSidebar(false));
        });

        const activeTab = <?= json_encode($active_tab) ?>;
        const pageIplm = <?= (int)$page_iplm ?>;
        const pageTkm = <?= (int)$page_tkm ?>;
        if (activeTab) {
            const trigger = document.querySelector(`[data-bs-target="#tab-${activeTab}"]`);
            if (trigger) {
                new bootstrap.Tab(trigger).show();
            }
        }

        const modalForm = new bootstrap.Modal(document.getElementById('modalForm'));

        function setFormContext(tab) {
            document.getElementById('form_tab').value = tab;
            document.getElementById('form_page_iplm').value = pageIplm;
            document.getElementById('form_page_tkm').value = pageTkm;
        }

        function filterBagianByJenis(jenisValue, bagianValue = '') {
            const select = document.getElementById('form_bagian');
            let firstVisible = null;
            Array.from(select.options).forEach(opt => {
                const genisMatch = opt.dataset && opt.dataset.jenis && opt.dataset.jenis.toUpperCase() === jenisValue;
                opt.hidden = !genisMatch;
                if (genisMatch && !firstVisible) firstVisible = opt;
            });

            if (bagianValue && bagianValue !== '') {
                select.value = bagianValue;
            } else if (firstVisible) {
                select.value = firstVisible.value;
            } else {
                select.value = '';
            }
        }

        function toggleOpsiInput(selectElement) {
            if (!selectElement) return;
            const row = selectElement.closest('.question-row');
            if (!row) return;
            
            const tipe = selectElement.value;
            const box = row.querySelector('.box-opsi');
            
            if(tipe === 'select' || tipe === 'radio') {
                box.style.display = 'block';
            } else {
                box.style.display = 'none';
            }
        }
        
        function getQuestionRowTemplate(index, data = null) {
            const soal = data ? (data.teks_pertanyaan || '') : '';
            const ket = data ? (data.keterangan || '') : '';
            const tipe = data ? (data.tipe_input || 'text') : 'text';
            // Urutan: if data exists, use it. if new row, leave empty (auto).
            const urutan = data ? (data.urutan || '') : '';
            const opsi = data ? (data.pilihan_opsi || '') : '';
            
            const isSelectOrRadio = (tipe === 'select' || tipe === 'radio');
            const boxDisplay = isSelectOrRadio ? 'block' : 'none';

            return `
            <div class="question-row card p-3 mb-3 border bg-white shadow-sm position-relative">
                ${index > 0 ? '<button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle" style="width:24px;height:24px;padding:0;line-height:22px;" onclick="removeQuestionRow(this)">&times;</button>' : ''}
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold small text-uppercase text-muted">Teks Pertanyaan</label>
                        <textarea name="soal[]" class="form-control" rows="2" required placeholder="Tulis pertanyaan di sini...">${soal}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small text-uppercase text-muted">Keterangan (Opsional)</label>
                        <textarea name="keterangan[]" class="form-control form-control-sm text-muted" rows="1" placeholder="Penjelasan tambahan">${ket}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase text-muted">Tipe Input</label>
                        <select name="tipe[]" class="form-select form-select-sm" onchange="toggleOpsiInput(this)">
                            <option value="text" ${tipe === 'text' ? 'selected' : ''}>Teks Pendek</option>
                            <option value="number" ${tipe === 'number' ? 'selected' : ''}>Angka</option>
                            <option value="textarea" ${tipe === 'textarea' ? 'selected' : ''}>Teks Panjang</option>
                            <option value="likert" ${tipe === 'likert' ? 'selected' : ''}>Skala Likert</option>
                            <option value="select" ${tipe === 'select' ? 'selected' : ''}>Dropdown</option>
                            <option value="radio" ${tipe === 'radio' ? 'selected' : ''}>Radio Button</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase text-muted">Urutan</label>
                        <input type="number" name="urutan[]" class="form-control form-control-sm" value="${urutan}" placeholder="Auto">
                    </div>
                    <div class="col-12 box-opsi" style="display: ${boxDisplay};">
                        <div class="p-3 bg-primary-subtle border border-primary-subtle rounded-3">
                            <label class="form-label fw-bold text-primary small">Opsi Jawaban (Pisahkan dengan koma)</label>
                            <input type="text" name="pilihan_opsi[]" class="form-control form-control-sm border-primary" value="${opsi}" placeholder="Contoh: Ya, Tidak">
                        </div>
                    </div>
                </div>
            </div>
            `;
        }

        function addQuestionRow(data = null) {
            const container = document.getElementById('questions_container');
            const count = container.children.length;
            const html = getQuestionRowTemplate(count, data);
            container.insertAdjacentHTML('beforeend', html);
        }
        
        function removeQuestionRow(btn) {
            btn.closest('.question-row').remove();
        }

        function bukaModalTambah(bagian = '', tabOverride = null, urutanPreset = null) {
            document.getElementById('modalTitle').innerHTML = '<i class="bi bi-plus-circle me-2 text-white"></i><span class="text-white">Tambah Pertanyaan Baru</span>';
            document.getElementById('form_aksi').value = 'tambah';
            document.getElementById('form_id').value = '';
            
            let currentActive = activeTab || 'iplm';
            const activePane = document.querySelector('.tab-pane.active');
            if (activePane) {
                if (activePane.id === 'tab-iplm') currentActive = 'iplm';
                if (activePane.id === 'tab-tkm') currentActive = 'tkm';
            }
            const tab = tabOverride || currentActive;
            const jenisValue = (tab === 'tkm') ? 'TKM' : 'IPLM';
            document.getElementById('form_jenis').value = jenisValue;
            
            filterBagianByJenis(jenisValue, bagian);
            setFormContext(tab);
            
            // Allow Adding More Rows
            document.getElementById('btn_add_more_container').style.display = 'block';
            
            // Clear and add one empty row
             document.getElementById('questions_container').innerHTML = '';
             let initialData = null;
             if (urutanPreset !== null) initialData = { urutan: urutanPreset };
             addQuestionRow(initialData);
            
            const trigger = document.getElementById('tab-single');
            if (trigger) { new bootstrap.Tab(trigger).show(); }
            modalForm.show();
        }

        function editData(data) {
            document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-white"></i><span class="text-white">Edit Pertanyaan</span>';
            document.getElementById('form_aksi').value = 'edit';
            document.getElementById('form_id').value = data.id;

            document.getElementById('form_jenis').value = data.jenis_kuesioner;
            const jenisTarget = data.jenis_kuesioner === 'TKM' ? 'TKM' : 'IPLM';
            filterBagianByJenis(jenisTarget, data.kategori_bagian);
            
            setFormContext(data.jenis_kuesioner === 'TKM' ? 'tkm' : 'iplm');
            
            // Hide "Add More" in edit mode
            document.getElementById('btn_add_more_container').style.display = 'none';
            
            // Clear and add row with data
            document.getElementById('questions_container').innerHTML = '';
            addQuestionRow(data);
            
            modalForm.show();
        }

        // Simpan & pulihkan posisi scroll hanya setelah submit form (bukan pagination)
        const scrollKey = 'atur_pertanyaan_scroll';
        const restoreKey = 'atur_pertanyaan_restore_scroll';
        const savedScroll = sessionStorage.getItem(scrollKey);
        const shouldRestore = sessionStorage.getItem(restoreKey) === '1';
        if (shouldRestore && savedScroll) {
            window.scrollTo(0, parseInt(savedScroll, 10));
            sessionStorage.removeItem(restoreKey);
        }

        function markScrollForRestore() {
            sessionStorage.setItem(scrollKey, String(window.scrollY));
            sessionStorage.setItem(restoreKey, '1');
        }

        document.querySelectorAll('form').forEach((form) => {
            form.addEventListener('submit', () => {
                markScrollForRestore();
            });
        });

        document.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                sessionStorage.removeItem(restoreKey);
            });
        });

        bindConfirmForms();

        const formJenis = document.getElementById('form_jenis');
        if (formJenis) {
            formJenis.addEventListener('change', (event) => {
                const jenisValue = event.target.value || 'IPLM';
                filterBagianByJenis(jenisValue);
                setFormContext(jenisValue === 'TKM' ? 'tkm' : 'iplm');
            });
        }
    </script>
    <script>
        // Inisialisasi modal edit bagian
        const modalEditBagian = new bootstrap.Modal(document.getElementById('modalEditBagian'));

        function openEditBagian(id, jenis, name, position) {
            // Isi form dengan data yang ada
            document.getElementById('edit_bagian_id').value = id;
            document.getElementById('edit_bagian_jenis').value = jenis;
            document.getElementById('edit_bagian_nama').value = name;
            document.getElementById('edit_bagian_posisi').value = position;
            
            // Tampilkan modal
            modalEditBagian.show();
        }

        function toggleSelectAll(checkbox, tab) {
            const checkboxes = document.querySelectorAll(`.check-item-${tab}`);
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            checkSelection(tab);
        }
        
        function checkSelection(tab) {
            const checkboxes = document.querySelectorAll(`.check-item-${tab}:checked`);
            const count = checkboxes.length;
            const actionDiv = document.getElementById(`bulkActions-${tab}`);
            const countSpan = document.getElementById(`selectedCount-${tab}`);
            
            if (count > 0) {
                actionDiv.classList.remove('d-none');
                countSpan.innerText = count;
            } else {
                actionDiv.classList.add('d-none');
            }
        }

        function hapusSatu(id, tab) {
            Swal.fire({
                title: 'Hapus Pertanyaan?',
                text: "Data pertanyaan ini akan dihapus secara permanen.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4f6cf6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete_id').value = id;
                    document.getElementById('delete_tab').value = tab;
                    document.getElementById('singleDeleteForm').submit();
                }
            });
        }

        function downloadTemplateSoal(e) {
            e.preventDefault();
            const headers = ["jenis_kuesioner","kategori_bagian","teks_pertanyaan","keterangan","tipe_input","pilihan_opsi","urutan"];
            const rows = [
                headers.join(","),
                "IPLM,IDENTITAS,Nama Perpustakaan,,text,,1",
                "TKM,PELAYANAN,Bagaimana pelayanan petugas?,,likert,,2"
            ];
            const csvContent = "data:text/csv;charset=utf-8," + rows.join("\n");
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "template_soal_kuesioner.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
    <script src="../assets/loader.js"></script>
</body>
</html>