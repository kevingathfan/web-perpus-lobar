<?php
// web-perpus-v1/admin/atur_pertanyaan.php
session_start();
require '../config/database.php';
require '../config/admin_auth.php';

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
                // Jika tidak ada header, kembalikan pointer ke awal dan pakai urutan kolom baku
                rewind($handle);
            }

            $allowed_tipe = ['text','number','textarea','likert','select','radio'];
            $count_cache = [];
            $sukses = 0; $gagal = 0;

            $stmtInsert = $pdo->prepare("INSERT INTO master_pertanyaan (jenis_kuesioner, kategori_bagian, teks_pertanyaan, keterangan, tipe_input, pilihan_opsi, urutan) VALUES (?, ?, ?, ?, ?, ?, ?)");

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

                $key = $jenis . '|' . $bagian;
                if (!isset($count_cache[$key])) {
                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM master_pertanyaan WHERE jenis_kuesioner = ? AND kategori_bagian = ?");
                    $stmtCount->execute([$jenis, $bagian]);
                    $count_cache[$key] = (int)$stmtCount->fetchColumn();
                }
                $count_cache[$key] = (int)$count_cache[$key];
                $maxPos = $count_cache[$key] + 1;
                if ($urutan < 1 || $urutan > $maxPos) $urutan = $maxPos;

                try {
                    $stmtInsert->execute([$jenis, $bagian, $soal, $keterangan, $tipe, $pilihan_opsi, $urutan]);
                    $count_cache[$key] += 1;
                    $sukses++;
                } catch (Exception $e) {
                    $gagal++;
                }
            }
            fclose($handle);

            $_SESSION['flash_message'] = "Import selesai. Berhasil: $sukses. Gagal: $gagal.";
            header("Location: atur_pertanyaan.php"); exit;
        }

        if (isset($_POST['aksi']) && $_POST['aksi'] === 'set_kontak_iplm') {
            $kontak_id = (int)($_POST['kontak_pertanyaan_id'] ?? 0);
            $stmtUpdate = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'iplm_kontak_pertanyaan_id'");
            $stmtUpdate->execute([$kontak_id]);
            if ($stmtUpdate->rowCount() === 0) {
                $stmtInsert = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('iplm_kontak_pertanyaan_id', ?)");
                $stmtInsert->execute([$kontak_id]);
            }
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
            foreach ($pairs as $key => $val) {
                $stmtUpdate = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                $stmtUpdate->execute([$val, $key]);
                if ($stmtUpdate->rowCount() === 0) {
                    $stmtInsert = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                    $stmtInsert->execute([$key, $val]);
                }
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

        // Tangkap Data
        $jenis = $_POST['jenis'];
        $bagian = $_POST['bagian'];
        $soal = $_POST['soal'];
        $keterangan = $_POST['keterangan'];
        $tipe = $_POST['tipe'];
        $urutan = $_POST['urutan'];
        // Tangkap Pilihan Opsi (hanya jika dropdown/radio)
        $pilihan_opsi = isset($_POST['pilihan_opsi']) ? $_POST['pilihan_opsi'] : '';

        $urutan = (int)$urutan;
        if ($urutan < 1) $urutan = 0;

        if (isset($_POST['aksi']) && $_POST['aksi'] == 'tambah') {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM master_pertanyaan WHERE jenis_kuesioner = ? AND kategori_bagian = ?");
            $stmtCount->execute([$jenis, $bagian]);
            $count = (int)$stmtCount->fetchColumn();
            if ($urutan < 1 || $urutan > $count + 1) $urutan = $count + 1;

            $sql = "INSERT INTO master_pertanyaan (jenis_kuesioner, kategori_bagian, teks_pertanyaan, keterangan, tipe_input, pilihan_opsi, urutan) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$jenis, $bagian, $soal, $keterangan, $tipe, $pilihan_opsi, $urutan]);
            $pesan = "Berhasil menambah pertanyaan baru!";
        } 
        elseif (isset($_POST['aksi']) && $_POST['aksi'] == 'edit') {
            $currentId = (int)$_POST['id'];
            $stmtCurrent = $pdo->prepare("SELECT jenis_kuesioner, kategori_bagian, urutan FROM master_pertanyaan WHERE id = ?");
            $stmtCurrent->execute([$currentId]);
            $current = $stmtCurrent->fetch(PDO::FETCH_ASSOC);
            if (!$current) {
                throw new Exception("Data pertanyaan tidak ditemukan.");
            }

            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM master_pertanyaan WHERE jenis_kuesioner = ? AND kategori_bagian = ? AND id <> ?");
            $stmtCount->execute([$jenis, $bagian, $currentId]);
            $count = (int)$stmtCount->fetchColumn();
            if ($urutan < 1 || $urutan > $count + 1) $urutan = $count + 1;

            $pdo->beginTransaction();
            try {
                $stmtSwap = $pdo->prepare("SELECT id FROM master_pertanyaan WHERE jenis_kuesioner = ? AND kategori_bagian = ? AND urutan = ? AND id <> ? LIMIT 1");
                $stmtSwap->execute([$jenis, $bagian, $urutan, $currentId]);
                $swapId = $stmtSwap->fetchColumn();
                if ($swapId) {
                    $swapUrutan = (int)$current['urutan'];
                    if ($current['jenis_kuesioner'] !== $jenis || $current['kategori_bagian'] !== $bagian) {
                        $swapUrutan = $count + 1;
                    }
                    $stmtUpdSwap = $pdo->prepare("UPDATE master_pertanyaan SET urutan = ? WHERE id = ?");
                    $stmtUpdSwap->execute([$swapUrutan, $swapId]);
                }

                $sql = "UPDATE master_pertanyaan SET jenis_kuesioner=?, kategori_bagian=?, teks_pertanyaan=?, keterangan=?, tipe_input=?, pilihan_opsi=?, urutan=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$jenis, $bagian, $soal, $keterangan, $tipe, $pilihan_opsi, $urutan, $currentId]);
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            $pesan = "Data pertanyaan berhasil diperbarui!";
        } 
        elseif (isset($_POST['aksi']) && $_POST['aksi'] == 'hapus') {
            $stmt = $pdo->prepare("DELETE FROM master_pertanyaan WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $pesan = "Pertanyaan berhasil dihapus.";
        }
        $_SESSION['flash_message'] = $pesan;
        $redirectTab = $_POST['tab'] ?? (($jenis === 'TKM') ? 'tkm' : 'iplm');
        $redirectPageIplm = (int)($_POST['page_iplm'] ?? 1);
        $redirectPageTkm = (int)($_POST['page_tkm'] ?? 1);
        $_SESSION['atur_pertanyaan_state'] = [
            'tab' => $redirectTab,
            'page_iplm' => max(1, $redirectPageIplm),
            'page_tkm' => max(1, $redirectPageTkm),
        ];
        header("Location: atur_pertanyaan.php"); exit;
    } catch (Exception $e) { $pesan = "Error: " . $e->getMessage(); }
}

if (isset($_SESSION['flash_message'])) { $pesan = $_SESSION['flash_message']; unset($_SESSION['flash_message']); }

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

$stmtIplm = $pdo->prepare("SELECT * FROM master_pertanyaan WHERE jenis_kuesioner = 'IPLM' ORDER BY kategori_bagian ASC, urutan ASC LIMIT :limit OFFSET :offset");
$stmtIplm->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmtIplm->bindValue(':offset', $offset_iplm, PDO::PARAM_INT);
$stmtIplm->execute();
$data_iplm = $stmtIplm->fetchAll(PDO::FETCH_ASSOC);

$stmtTkm = $pdo->prepare("SELECT * FROM master_pertanyaan WHERE jenis_kuesioner = 'TKM' ORDER BY kategori_bagian ASC, urutan ASC LIMIT :limit OFFSET :offset");
$stmtTkm->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmtTkm->bindValue(':offset', $offset_tkm, PDO::PARAM_INT);
$stmtTkm->execute();
$data_tkm = $stmtTkm->fetchAll(PDO::FETCH_ASSOC);

// --- 2b. MAX URUTAN PER KATEGORI (UNTUK TOMBOL TAMBAH DI BAGIAN) ---
$maxUrutanIplm = [];
try {
    $stmtMaxI = $pdo->prepare("SELECT kategori_bagian, MAX(urutan) AS max_urutan FROM master_pertanyaan WHERE jenis_kuesioner = 'IPLM' GROUP BY kategori_bagian");
    $stmtMaxI->execute();
    $rows = $stmtMaxI->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $key = $r['kategori_bagian'] ?? '';
        if ($key !== '') $maxUrutanIplm[$key] = (int)$r['max_urutan'];
    }
} catch (Exception $e) { $maxUrutanIplm = []; }

$maxUrutanTkm = [];
try {
    $stmtMaxT = $pdo->prepare("SELECT kategori_bagian, MAX(urutan) AS max_urutan FROM master_pertanyaan WHERE jenis_kuesioner = 'TKM' GROUP BY kategori_bagian");
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

$list_iplm_questions = [];
try {
    $stmtAllIplm = $pdo->prepare("SELECT id, kategori_bagian, teks_pertanyaan FROM master_pertanyaan WHERE jenis_kuesioner = 'IPLM' ORDER BY urutan ASC");
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pertanyaan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/loader.css">
    <link rel="stylesheet" href="../assets/admin-responsive.css">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; width: 260px; background-color: #ffffff; border-right: 1px solid #e0e0e0; position: fixed; top: 0; left: 0; padding: 40px 20px; z-index: 100; }
        .sidebar-header { font-weight: 800; font-size: 24px; margin-bottom: 50px; color: #000; text-align: center; letter-spacing: 1px; }
        .nav-link { color: #666; font-weight: 600; font-size: 15px; padding: 12px 20px; margin-bottom: 8px; border-radius: 8px; transition: all 0.3s; display: flex; align-items: center; gap: 10px; }
        .nav-link:hover, .nav-link.active { background-color: #000; color: #fff; }
        .main-content { margin-left: 260px; padding: 40px 50px; }
        .card-custom { background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 16px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
        .nav-tabs .nav-link { color: #666; font-weight: 600; border: none; border-bottom: 3px solid transparent; }
        .nav-tabs .nav-link.active { color: #000; border-bottom: 3px solid #000; background: transparent; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../config/loader.php'; ?>
    <div class="sidebar-backdrop" onclick="toggleSidebar(false)"></div>

    <nav class="sidebar">
        <div class="d-flex justify-content-between align-items-center sidebar-header">
            <span>DISARPUS</span>
            <button class="btn btn-sm btn-outline-dark d-lg-none" onclick="toggleSidebar(false)"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="nav flex-column">
            <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-fill"></i> DASHBOARD</a>
            <a href="perpustakaan.php" class="nav-link"><i class="bi bi-building"></i> PERPUSTAKAAN</a>
            <a href="hasil_kuisioner.php" class="nav-link"><i class="bi bi-table"></i> HASIL KUISIONER</a>
            <a href="atur_pertanyaan.php" class="nav-link active"><i class="bi bi-file-text"></i> KUISIONER</a>
            <a href="pengaduan.php" class="nav-link"><i class="bi bi-chat-left-text"></i> PENGADUAN</a>
            <a href="users.php" class="nav-link"><i class="bi bi-people-fill"></i> ADMIN</a>
            <div class="mt-5 pt-5 border-top"><a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> KELUAR</a></div>
        </div>
    </nav>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4 page-header">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-dark btn-sm d-lg-none" onclick="toggleSidebar(true)"><i class="bi bi-list"></i></button>
                <div><h2 class="fw-bold m-0 page-title">Manajemen Pertanyaan</h2><p class="text-muted m-0 page-subtitle">Kelola soal kuesioner IPLM & TKM</p></div>
            </div>
            <button class="btn btn-dark rounded-pill px-4 fw-bold w-100-on-mobile" onclick="bukaModalTambah()"><i class="bi bi-plus-lg me-2"></i> Tambah Soal</button>
        </div>

        <?php if($pesan): ?><div class="alert alert-success alert-dismissible fade show"><?= $pesan ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

        <div class="card card-custom p-4">
            
            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-iplm">IPLM (Data Statistik)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tkm">TKM (Survei Perilaku)</button></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-iplm">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center icon-circle" style="width:36px;height:36px;">
                                    <i class="bi bi-telephone-fill"></i>
                                </div>
                                <div>
                                    <div class="fw-bold">Kontak Pengisi IPLM</div>
                                    <small class="text-muted">Pilih pertanyaan yang dipakai sebagai kontak untuk validasi duplikat per bulan.</small>
                                </div>
                            </div>
                            <form method="POST" class="row g-2 align-items-end">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="aksi" value="set_kontak_iplm">
                                <input type="hidden" name="page_iplm" value="<?= $page_iplm ?>">
                                <input type="hidden" name="page_tkm" value="<?= $page_tkm ?>">
                                <div class="col-md-10">
                                    <label class="form-label fw-bold">Pertanyaan Kontak IPLM</label>
                                    <select name="kontak_pertanyaan_id" class="form-select" required>
                                        <option value="" disabled <?= empty($kontak_setting_id) ? 'selected' : '' ?>>-- Pilih Pertanyaan --</option>
                                        <?php foreach ($list_iplm_questions as $q): ?>
                                            <option value="<?= $q['id'] ?>" <?= ((string)$kontak_setting_id === (string)$q['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($q['kategori_bagian'] . ' — ' . $q['teks_pertanyaan']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="submit" class="btn btn-dark fw-bold">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center icon-circle" style="width:36px;height:36px;">
                                    <i class="bi bi-magic"></i>
                                </div>
                                <div>
                                    <div class="fw-bold">Auto-fill Identitas IPLM</div>
                                    <small class="text-muted">Pilih pertanyaan yang otomatis diisi dari pilihan perpustakaan.</small>
                                </div>
                            </div>
                            <form method="POST" class="row g-3 align-items-end">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="aksi" value="set_autofill_iplm">
                                <input type="hidden" name="page_iplm" value="<?= $page_iplm ?>">
                                <input type="hidden" name="page_tkm" value="<?= $page_tkm ?>">
                                <div class="col-lg-4">
                                    <label class="form-label fw-bold">Pertanyaan Jenis</label>
                                    <select name="autofill_jenis_id" class="form-select" required>
                                        <option value="" disabled <?= empty($autofill_jenis_id) ? 'selected' : '' ?>>-- Pilih Pertanyaan --</option>
                                        <?php foreach ($list_iplm_questions as $q): ?>
                                            <option value="<?= $q['id'] ?>" <?= ((string)$autofill_jenis_id === (string)$q['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($q['kategori_bagian'] . ' — ' . $q['teks_pertanyaan']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label fw-bold">Pertanyaan Sub Jenis</label>
                                    <select name="autofill_subjenis_id" class="form-select" required>
                                        <option value="" disabled <?= empty($autofill_subjenis_id) ? 'selected' : '' ?>>-- Pilih Pertanyaan --</option>
                                        <?php foreach ($list_iplm_questions as $q): ?>
                                            <option value="<?= $q['id'] ?>" <?= ((string)$autofill_subjenis_id === (string)$q['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($q['kategori_bagian'] . ' — ' . $q['teks_pertanyaan']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label fw-bold">Pertanyaan Nama</label>
                                    <select name="autofill_nama_id" class="form-select" required>
                                        <option value="" disabled <?= empty($autofill_nama_id) ? 'selected' : '' ?>>-- Pilih Pertanyaan --</option>
                                        <?php foreach ($list_iplm_questions as $q): ?>
                                            <option value="<?= $q['id'] ?>" <?= ((string)$autofill_nama_id === (string)$q['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($q['kategori_bagian'] . ' — ' . $q['teks_pertanyaan']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 d-grid">
                                    <button type="submit" class="btn btn-dark fw-bold">Simpan Auto-fill</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php renderTable($data_iplm, 'iplm', $page_iplm, $total_pages_iplm, $page_tkm, $maxUrutanIplm); ?>
                </div>
                <div class="tab-pane fade" id="tab-tkm">
                    <?php renderTable($data_tkm, 'tkm', $page_tkm, $total_pages_tkm, $page_iplm, $maxUrutanTkm); ?>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="modalForm" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="modalTitle">Form Pertanyaan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <ul class="nav nav-pills px-4 pt-4" id="modalTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold" id="tab-single" data-bs-toggle="pill" data-bs-target="#pane-single" type="button">Tambah Soal</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold" id="tab-import" data-bs-toggle="pill" data-bs-target="#pane-import" type="button">Import CSV</button>
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
                                        <label class="form-label fw-bold">Jenis Kuesioner</label>
                                        <select name="jenis" id="form_jenis" class="form-select" required>
                                            <option value="IPLM">IPLM</option>
                                            <option value="TKM">TKM</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold">Kategori / Bagian</label>
                                        <input type="text" name="bagian" id="form_bagian" class="form-control" required placeholder="Contoh: IDENTITAS RESPONDEN">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Teks Pertanyaan</label>
                                        <textarea name="soal" id="form_soal" class="form-control" rows="2" required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Keterangan / Penjelasan (Opsional)</label>
                                        <textarea name="keterangan" id="form_keterangan" class="form-control" rows="2" placeholder="Muncul kecil di bawah pertanyaan (miring)"></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Tipe Input</label>
                                        <select name="tipe" id="form_tipe" class="form-select" onchange="toggleOpsiInput()">
                                            <option value="text">Teks Pendek (Nama, Alamat)</option>
                                            <option value="number">Angka (Umur, Jumlah)</option>
                                            <option value="textarea">Teks Panjang (Saran)</option>
                                            <option value="likert">Skala Likert (Setuju - Tidak Setuju)</option>
                                            <option value="select">Dropdown (Pilihan Ganda)</option>
                                            <option value="radio">Radio Button (Pilihan Ganda)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Nomor Urut</label>
                                        <input type="number" name="urutan" id="form_urutan" class="form-control" value="1">
                                    </div>

                                    <div class="col-12" id="box_opsi" style="display:none;">
                                        <div class="p-3 bg-light border rounded">
                                            <label class="form-label fw-bold text-primary">Opsi Jawaban (Pisahkan dengan koma)</label>
                                            <input type="text" name="pilihan_opsi" id="form_pilihan_opsi" class="form-control border-primary" placeholder="Contoh: < 20 Tahun, 21-30 Tahun, 31-40 Tahun, > 40 Tahun">
                                            <small class="text-muted">Masukkan pilihan jawaban dipisahkan tanda koma (,)</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer mt-4">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary fw-bold">Simpan Data</button>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="pane-import">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="aksi" value="import_csv">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">File CSV</label>
                                    <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                                    <div class="form-text">
                                        Header wajib (4 kolom): <code>jenis_kuesioner,kategori_bagian,teks_pertanyaan,keterangan</code><br>
                                        Kolom opsional: <code>tipe_input,pilihan_opsi,urutan</code><br>
                                        Contoh baris: <code>IPLM,IDENTITAS,Nama Perpustakaan,,text,,</code>
                                    </div>
                                </div>
                                <div class="alert alert-info small mb-3">
                                    Default: `tipe_input` akan dianggap <strong>text</strong> jika kosong. `urutan` otomatis di posisi terakhir per kategori.
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-dark fw-bold">Import CSV</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php function renderTable($dataset, $tab, $page, $total_pages, $other_page, $maxMap = []) { ?>
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
                <thead>
                    <tr class="table-light">
                        <th width="5%" class="text-center">No</th>
                        <th width="20%">Kategori</th>
                        <th>Pertanyaan</th>
                        <th width="15%">Tipe</th>
                        <th width="10%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($dataset)): ?><tr><td colspan="5" class="text-center py-4">Data kosong</td></tr><?php else: ?>
                    <?php
                        $lastKategori = null;
                        foreach($dataset as $row):
                            $kategori = $row['kategori_bagian'] ?? '';
                            if ($kategori !== $lastKategori):
                                $maxUrut = $maxMap[$kategori] ?? 0;
                    ?>
                        <tr class="table-secondary">
                            <td colspan="5">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold"><?= htmlspecialchars($kategori) ?></span>
                                    <button type="button" class="btn btn-sm btn-dark"
                                        onclick='bukaModalTambah(<?= json_encode($kategori) ?>, <?= json_encode($tab) ?>, <?= (int)$maxUrut + 1 ?>)'>
                                        <i class="bi bi-plus-lg me-1"></i> Tambah Soal di Bagian Ini
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php
                            $lastKategori = $kategori;
                            endif;
                    ?>
                        <tr>
                            <td class="text-center fw-bold"><?= $row['urutan'] ?></td>
                            <td><small class="fw-bold text-muted"><?= htmlspecialchars($row['kategori_bagian']) ?></small></td>
                            <td>
                                <div><?= htmlspecialchars($row['teks_pertanyaan']) ?></div>
                                <?php if($row['keterangan']): ?><small class="text-muted fst-italic">Ket: <?= htmlspecialchars($row['keterangan']) ?></small><?php endif; ?>
                                <?php if(($row['tipe_input'] == 'select' || $row['tipe_input'] == 'radio') && $row['pilihan_opsi']): ?>
                                    <div class="mt-1"><span class="badge bg-info text-dark">Opsi: <?= htmlspecialchars($row['pilihan_opsi']) ?></span></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= strtoupper($row['tipe_input']) ?></span></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-warning me-1" onclick='editData(<?= json_encode($row) ?>)'><i class="bi bi-pencil-square"></i></button>
                                <form method="POST" class="d-inline js-confirm" data-confirm-title="Hapus pertanyaan?" data-confirm-text="Pertanyaan ini akan dihapus permanen." data-confirm-button="Ya, hapus">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="tab" value="<?= $tab ?>">
                                    <input type="hidden" name="page_iplm" value="<?= ($tab === 'iplm') ? $page : $other_page ?>">
                                    <input type="hidden" name="page_tkm" value="<?= ($tab === 'tkm') ? $page : $other_page ?>">
                                    <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Halaman <?= $page ?> dari <?= $total_pages ?></small>
            <div class="btn-group">
                <?php
                    $prev = max(1, $page - 1);
                    $next = min($total_pages, $page + 1);
                    $prevDisabled = ($page <= 1);
                    $nextDisabled = ($page >= $total_pages);
                ?>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="nav_only" value="1">
                    <input type="hidden" name="tab" value="<?= $tab ?>">
                    <input type="hidden" name="page_iplm" value="<?= ($tab === 'iplm') ? $prev : $other_page ?>">
                    <input type="hidden" name="page_tkm" value="<?= ($tab === 'tkm') ? $prev : $other_page ?>">
                    <button class="btn btn-sm btn-outline-dark <?= $prevDisabled ? 'disabled' : '' ?>" <?= $prevDisabled ? 'disabled' : '' ?>>Sebelumnya</button>
                </form>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="nav_only" value="1">
                    <input type="hidden" name="tab" value="<?= $tab ?>">
                    <input type="hidden" name="page_iplm" value="<?= ($tab === 'iplm') ? $next : $other_page ?>">
                    <input type="hidden" name="page_tkm" value="<?= ($tab === 'tkm') ? $next : $other_page ?>">
                    <button class="btn btn-sm btn-outline-dark <?= $nextDisabled ? 'disabled' : '' ?>" <?= $nextDisabled ? 'disabled' : '' ?>>Berikutnya</button>
                </form>
            </div>
        </div>
    <?php } ?>

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

        function toggleOpsiInput() {
            const tipe = document.getElementById('form_tipe').value;
            const box = document.getElementById('box_opsi');
            // Tampilkan kotak opsi jika tipe adalah Select atau Radio
            if(tipe === 'select' || tipe === 'radio') {
                box.style.display = 'block';
            } else {
                box.style.display = 'none';
            }
        }

        function bukaModalTambah(bagian = '', tabOverride = null, urutanPreset = null) {
            document.getElementById('modalTitle').innerText = 'Tambah Pertanyaan Baru';
            document.getElementById('form_aksi').value = 'tambah';
            document.getElementById('form_id').value = '';
            
            // Reset Form
            const tab = tabOverride || activeTab || 'iplm';
            document.getElementById('form_jenis').value = (tab === 'tkm') ? 'TKM' : 'IPLM';
            document.getElementById('form_bagian').value = bagian || '';
            document.getElementById('form_soal').value = '';
            document.getElementById('form_keterangan').value = '';
            document.getElementById('form_tipe').value = 'text';
            document.getElementById('form_pilihan_opsi').value = '';
            document.getElementById('form_urutan').value = (urutanPreset !== null) ? urutanPreset : '';
            
            setFormContext(tab);
            toggleOpsiInput();
            const trigger = document.getElementById('tab-single');
            if (trigger) {
                new bootstrap.Tab(trigger).show();
            }
            modalForm.show();
        }

        function editData(data) {
            document.getElementById('modalTitle').innerText = 'Edit Pertanyaan';
            document.getElementById('form_aksi').value = 'edit';
            document.getElementById('form_id').value = data.id;

            document.getElementById('form_jenis').value = data.jenis_kuesioner;
            document.getElementById('form_bagian').value = data.kategori_bagian;
            document.getElementById('form_soal').value = data.teks_pertanyaan;
            document.getElementById('form_keterangan').value = data.keterangan || '';
            document.getElementById('form_tipe').value = data.tipe_input;
            document.getElementById('form_pilihan_opsi').value = data.pilihan_opsi || '';
            document.getElementById('form_urutan').value = data.urutan;

            setFormContext(data.jenis_kuesioner === 'TKM' ? 'tkm' : 'iplm');
            toggleOpsiInput(); // Cek apakah field opsi perlu ditampilkan
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
    </script>
    <script src="../assets/loader.js"></script>
</body>
</html>
