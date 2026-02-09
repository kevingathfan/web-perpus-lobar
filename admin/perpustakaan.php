<?php
// admin/perpustakaan.php
session_start();
require '../config/database.php';
require '../config/admin_auth.php';

// --- 0. AMBIL MASTER KATEGORI ---
$stmtK = $pdo->query("SELECT * FROM master_kategori ORDER BY kategori, sub_kategori");
$rawKat = $stmtK->fetchAll(PDO::FETCH_ASSOC);

$strukturJenis = [];
$validasiMap = []; 
$formatFixer = [];

foreach ($rawKat as $r) {
    $strukturJenis[$r['kategori']][] = $r['sub_kategori'];
    $uKat = strtoupper($r['kategori']);
    $uSub = strtoupper($r['sub_kategori']);
    $validasiMap[$uKat][] = $uSub;
    $formatFixer['KAT'][$uKat] = $r['kategori'];
    $formatFixer['SUB'][$uSub] = $r['sub_kategori'];
}

function isKategoriValid($kategori, $subjenis, $map) {
    $k = strtoupper(trim($kategori));
    $s = strtoupper(trim($subjenis));
    return isset($map[$k]) && in_array($s, $map[$k]);
}

if (!isset($_GET['ajax_action'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_periode'])) {
        $_SESSION['perpustakaan_filter'] = [
            'bulan' => $_POST['bulan'] ?? date('m'),
            'tahun' => $_POST['tahun'] ?? date('Y')
        ];
        header("Location: perpustakaan.php");
        exit;
    }
    if (!empty($_GET) && (isset($_GET['bulan']) || isset($_GET['tahun']))) {
        $_SESSION['perpustakaan_filter'] = [
            'bulan' => $_GET['bulan'] ?? date('m'),
            'tahun' => $_GET['tahun'] ?? date('Y')
        ];
        header("Location: perpustakaan.php");
        exit;
    }
}

// --- 1. HANDLE REQUEST AJAX (LIVE SEARCH & STATUS IPLM) ---
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] == 'load_table') {
    
    // Filter Params
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $filterKat = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
    $filterSub = isset($_GET['subjenis']) ? trim($_GET['subjenis']) : '';
    $filterStatus = isset($_GET['status_iplm']) ? trim($_GET['status_iplm']) : '';
    
    // Status Filter Params (Bulan & Tahun) - KHUSUS IPLM
    $filterBulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
    $filterTahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 50;
    $offset = ($page - 1) * $limit;

    // Build Query WHERE
    $whereParts = [];
    $params = [];

    if ($search) {
        $whereParts[] = "(nama ILIKE :search OR jenis ILIKE :search)";
        $params[':search'] = "%$search%";
    }
    if ($filterKat) {
        $whereParts[] = "kategori = :kat";
        $params[':kat'] = $filterKat;
    }
    if ($filterSub) {
        $whereParts[] = "jenis = :sub";
        $params[':sub'] = $filterSub;
    }
    if ($filterStatus === 'sudah') {
        $whereParts[] = "(SELECT COUNT(*) FROM trans_header th WHERE th.library_id = l.id AND th.jenis_kuesioner = 'IPLM' AND th.periode_bulan = :bln AND th.periode_tahun = :thn) > 0";
    } elseif ($filterStatus === 'belum') {
        $whereParts[] = "(SELECT COUNT(*) FROM trans_header th WHERE th.library_id = l.id AND th.jenis_kuesioner = 'IPLM' AND th.periode_bulan = :bln AND th.periode_tahun = :thn) = 0";
    }

    $whereClause = $whereParts ? "WHERE " . implode(" AND ", $whereParts) : "";

    $params[':bln'] = $filterBulan;
    $params[':thn'] = $filterTahun;

    // Hitung Total Data
    $sqlCount = "SELECT COUNT(*) FROM libraries l $whereClause";
    $stmtCount = $pdo->prepare($sqlCount);
    foreach ($params as $key => $val) {
        if (strpos($sqlCount, $key) !== false) $stmtCount->bindValue($key, $val);
    }
    $stmtCount->execute();
    $total_data = $stmtCount->fetchColumn();
    $total_pages = ceil($total_data / $limit);

    // Main Query: Hanya Cek Status IPLM
    // Status TKM dihapus dari query agar lebih ringan
    $sql = "SELECT l.*, 
            (SELECT COUNT(*) FROM trans_header th WHERE th.library_id = l.id AND th.jenis_kuesioner = 'IPLM' AND th.periode_bulan = :bln AND th.periode_tahun = :thn) as status_iplm
            FROM libraries l 
            $whereClause 
            ORDER BY 
            CASE 
                WHEN kategori = 'Umum' THEN 1 
                WHEN kategori = 'Sekolah' THEN 2 
                WHEN kategori = 'Khusus' THEN 3 
                ELSE 4 
            END ASC, 
            nama ASC 
            LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $libraries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Render HTML Baris Tabel
    if (empty($libraries)) {
        echo '<tr><td colspan="6" class="text-center py-5 text-muted">Tidak ada data ditemukan.</td></tr>';
    } else {
        $no = $offset + 1;
        foreach ($libraries as $lib) {
            $kat = $lib['kategori'] ?? 'Umum';
            $bg = ($kat == 'Sekolah') ? 'bg-primary' : (($kat == 'Khusus') ? 'bg-warning text-dark' : 'bg-success');
            
            // Logika Badge Status (Hanya IPLM)
            // Jika > 0 berarti sudah mengisi di bulan & tahun tersebut
            $badgeIplm = ($lib['status_iplm'] > 0) 
                ? '<span class="badge bg-success rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Sudah Mengisi</span>' 
                : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill"><i class="bi bi-x-circle me-1"></i>Belum</span>';
            
            echo '<tr>';
            echo '<td class="text-center fw-bold">' . $no++ . '</td>';
            echo '<td class="fw-bold text-uppercase">' . htmlspecialchars($lib['nama']) . '</td>';
            echo '<td><span class="badge ' . $bg . ' badge-kategori">' . htmlspecialchars($kat) . '</span></td>';
            echo '<td>' . htmlspecialchars($lib['jenis']) . '</td>';
            
            // Kolom Status IPLM (klik untuk pengaturan)
            $statusText = ($lib['status_iplm'] > 0) ? 'sudah' : 'belum';
            echo '<td class="text-center">
                    <button type="button" class="btn btn-link p-0 text-decoration-none btn-status-iplm"
                        data-id="' . $lib['id'] . '"
                        data-status="' . $statusText . '"
                        data-nama="' . htmlspecialchars($lib['nama']) . '">
                        ' . $badgeIplm . '
                    </button>
                  </td>';

            echo '<td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-warning btn-edit-lib"
                                data-id="' . $lib['id'] . '"
                                data-nama="' . htmlspecialchars($lib['nama']) . '"
                                data-kategori="' . htmlspecialchars($lib['kategori'] ?? '') . '"
                                data-subjenis="' . htmlspecialchars($lib['jenis']) . '">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-hapus-lib" data-id="' . $lib['id'] . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                  </td>';
            echo '</tr>';
        }
    }
    exit;
}

// --- 2. PROSES CRUD (POST) ---
$pesan = "";
$tipe_pesan = "success";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['form_type']) && $_POST['form_type'] == 'library') {
            
            if ($_POST['aksi'] == 'tambah') {
                if (!isKategoriValid($_POST['kategori'], $_POST['subjenis'], $validasiMap)) throw new Exception("Kategori tidak valid.");
                $sql = "INSERT INTO libraries (nama, kategori, jenis) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([strtoupper($_POST['nama']), $_POST['kategori'], $_POST['subjenis']]);
                $pesan = "Perpustakaan berhasil ditambahkan!";
            } 
            elseif ($_POST['aksi'] == 'edit') {
                if (!isKategoriValid($_POST['kategori'], $_POST['subjenis'], $validasiMap)) throw new Exception("Kategori tidak valid.");
                $sql = "UPDATE libraries SET nama=?, kategori=?, jenis=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([strtoupper($_POST['nama']), $_POST['kategori'], $_POST['subjenis'], $_POST['id']]);
                $pesan = "Data diperbarui!";
            } 
            elseif ($_POST['aksi'] == 'hapus') {
                $stmt = $pdo->prepare("DELETE FROM libraries WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $pesan = "Perpustakaan dihapus.";
            } 
            elseif ($_POST['aksi'] == 'reset_status') {
                $libraryId = (int)($_POST['library_id'] ?? 0);
                $jenis = strtoupper(trim($_POST['jenis'] ?? ''));
                $bulan = str_pad((string)($_POST['bulan'] ?? ''), 2, '0', STR_PAD_LEFT);
                $tahun = (int)($_POST['tahun'] ?? 0);

                if (!$libraryId || !in_array($jenis, ['IPLM', 'TKM'], true) || !$bulan || !$tahun) {
                    throw new Exception("Parameter reset status tidak valid.");
                }

                $pdo->beginTransaction();
                try {
                    $stmtHeader = $pdo->prepare("SELECT id FROM trans_header WHERE library_id = ? AND jenis_kuesioner = ? AND periode_bulan = ? AND periode_tahun = ?");
                    $stmtHeader->execute([$libraryId, $jenis, $bulan, $tahun]);
                    $headerIds = $stmtHeader->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($headerIds)) {
                        $inQuery = implode(',', array_fill(0, count($headerIds), '?'));
                        $stmtDelDetail = $pdo->prepare("DELETE FROM trans_detail WHERE header_id IN ($inQuery)");
                        $stmtDelDetail->execute($headerIds);
                        $stmtDelHeader = $pdo->prepare("DELETE FROM trans_header WHERE id IN ($inQuery)");
                        $stmtDelHeader->execute($headerIds);
                    }
                    $pdo->commit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
                $pesan = "Status perpustakaan berhasil direset menjadi belum mengisi.";
            }
            elseif ($_POST['aksi'] == 'import_csv') {
                if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] == 0) {
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
                    $header = fgetcsv($handle);
                    if (!$header || count($header) < 3) {
                        fclose($handle);
                        throw new Exception("Format CSV tidak valid. Harus memiliki minimal 3 kolom: Nama, Kategori, Subjenis.");
                    }
                    
                    $sukses = 0; $gagal = 0;
                    $stmt = $pdo->prepare("INSERT INTO libraries (nama, kategori, jenis) VALUES (?, ?, ?)");
                    
                    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (count($row) < 3) { $gagal++; continue; }
                        $nama = trim($row[0] ?? '');
                        $rawKat = trim($row[1] ?? 'Umum');
                        $rawSub = trim($row[2] ?? '');
                        
                        $nama_ok = ($nama !== '' && mb_strlen($nama) >= 3 && preg_match('/[A-Za-z]/', $nama));
                        if ($nama_ok) {
                            if (isKategoriValid($rawKat, $rawSub, $validasiMap)) {
                                $keyKat = strtoupper($rawKat);
                                $keySub = strtoupper($rawSub);
                                $fixedKat = $formatFixer['KAT'][$keyKat];
                                $fixedSub = $formatFixer['SUB'][$keySub];

                                $cek = $pdo->prepare("SELECT id FROM libraries WHERE nama = ?");
                                $cek->execute([strtoupper($nama)]);
                                if ($cek->rowCount() == 0) {
                                    $stmt->execute([strtoupper($nama), $fixedKat, $fixedSub]);
                                    $sukses++;
                                }
                            } else { $gagal++; }
                        } else { $gagal++; }
                    }
                    fclose($handle);
                    $pesan = "Import Selesai. Masuk: $sukses. Gagal: $gagal";
                    if ($gagal > 0) $tipe_pesan = "warning";
                } else { throw new Exception("Gagal upload CSV."); }
            }
        }
        elseif (isset($_POST['form_type']) && $_POST['form_type'] == 'category') {
            if ($_POST['aksi'] == 'tambah') {
                $stmt = $pdo->prepare("INSERT INTO master_kategori (kategori, sub_kategori) VALUES (?, ?)");
                $stmt->execute([$_POST['kategori'], $_POST['sub_kategori']]);
                $pesan = "Kategori ditambahkan!";
            } elseif ($_POST['aksi'] == 'hapus') {
                $stmt = $pdo->prepare("DELETE FROM master_kategori WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $pesan = "Kategori dihapus.";
            }
        }
        $_SESSION['flash_message'] = $pesan;
        $_SESSION['flash_type'] = $tipe_pesan;
        header("Location: perpustakaan.php"); exit;
    } catch (Exception $e) {
        $pesan = "Error: " . $e->getMessage();
        $tipe_pesan = "danger";
    }
}

if (isset($_SESSION['flash_message'])) { 
    $pesan = $_SESSION['flash_message']; 
    $tipe_pesan = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Generate List Bulan & Tahun untuk Filter
$bulanList = [
    '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April',
    '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus',
    '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'
];
$tahunIni = date('Y');

// --- RINCIAN STATISTIK DATA (BULANAN) ---
$perpus_filter = $_SESSION['perpustakaan_filter'] ?? [];
$bulan_pilih = $perpus_filter['bulan'] ?? date('m');
$tahun_pilih = $perpus_filter['tahun'] ?? date('Y');
$label_periode = $bulanList[$bulan_pilih] . " " . $tahun_pilih;

try { 
    $stmt = $pdo->query("SELECT COUNT(*) FROM libraries"); 
    $total_perpus = $stmt->fetchColumn(); 
} catch (Exception $e) { $total_perpus = 0; }

try {
    $stmtIplm = $pdo->prepare("SELECT COUNT(*) FROM trans_header WHERE jenis_kuesioner = 'IPLM' AND periode_bulan = :bln AND periode_tahun = :thn");
    $stmtIplm->execute([':bln' => $bulan_pilih, ':thn' => $tahun_pilih]); 
    $total_iplm = $stmtIplm->fetchColumn();
} catch (Exception $e) { $total_iplm = 0; }

$belum_iplm = max(0, $total_perpus - $total_iplm);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Perpustakaan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/loader.css">
    <link rel="stylesheet" href="../assets/admin-responsive.css">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; width: 260px; background-color: #ffffff; border-right: 1px solid #e0e0e0; position: fixed; top: 0; left: 0; padding: 40px 20px; z-index: 100; }
        .sidebar-header { margin-bottom: 28px; display: flex; align-items: flex-start; justify-content: space-between; }
        .sidebar-brand { display: flex; flex-direction: column; align-items: center; gap: 8px; text-align: center; flex: 1; }
        .sidebar-title { font-weight: 800; font-size: 22px; color: #000; letter-spacing: 2px; line-height: 1.2; }
        .sidebar-logo { width: 64px; height: 64px; object-fit: contain; }
        .nav-link { color: #666; font-weight: 600; font-size: 15px; padding: 12px 20px; margin-bottom: 8px; border-radius: 8px; transition: all 0.3s; display: flex; align-items: center; gap: 10px; }
        .nav-link:hover, .nav-link.active { background-color: #000; color: #fff; }
        .main-content { margin-left: 260px; padding: 40px 50px; }
        .card-clean { background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 16px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
        .stat-box { background-color: #ffffff; border: 1px solid #dee2e6; border-radius: 14px; padding: 16px; text-align: left; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: flex-start; }
        .stat-box.highlight { background-color: #000; color: #fff; border: 1px solid #000; }
        .stat-number { font-size: 24px; font-weight: 800; line-height: 1.2; }
        .stats-compact .card-clean { padding: 18px; }
        .stats-compact .stat-box { padding: 12px; }
        .stats-compact .stat-label { font-size: 13px; color: #495057; }
        .stats-compact .stat-number { font-size: 24px; }
        .stat-total { position: relative; overflow: hidden; border: 1px solid #111; background: #111827; box-shadow: 0 8px 18px rgba(0,0,0,0.15); }
        .stat-total .stat-label { letter-spacing: 0.5px; text-transform: uppercase; font-size: 12px; color: #e5e7eb; }
        .stat-total .stat-number { font-size: 30px; color: #ffffff; }
        .stat-total .stat-sub { font-size: 13px; color: #d1d5db; opacity: 1; }
        .stat-total .stat-badge {
            position: absolute;
            right: 12px;
            top: 12px;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .stat-mini {
            border: 1px solid #e9ecef;
            background: #ffffff;
            box-shadow: 0 6px 12px rgba(0,0,0,0.04);
        }
        .stat-mini .stat-label { font-size: 13px; color: #343a40; }
        .stat-mini .stat-number { font-size: 24px; color: #111; }
        .stat-mini .icon-box { opacity: 1; color: #ffffff; }
        .stat-mini.success .icon-box { color: #198754; }
        .stat-mini.danger .icon-box { color: #dc3545; }
        .stat-mini .icon-box { font-size: 22px; }
        .stat-mini.success { border-left: 4px solid #198754; }
        .stat-mini.danger { border-left: 4px solid #dc3545; }
        .btn-filter { background-color: #f1f3f5; color: #333; border: none; padding: 8px 16px; border-radius: 30px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; cursor: pointer; }
        .nav-tabs .nav-link { color: #666; font-weight: 600; border: none; border-bottom: 3px solid transparent; }
        .nav-tabs .nav-link.active { color: #000; border-bottom: 3px solid #000; background: transparent; }
        .badge-kategori { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .search-box { border-radius: 30px; padding-left: 20px; border: 1px solid #ced4da; }
        .filter-select { border-radius: 30px; border: 1px solid #ced4da; font-size: 14px; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../config/loader.php'; ?>
    <div class="sidebar-backdrop" onclick="toggleSidebar(false)"></div>

    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <span class="sidebar-title">DISARPUS</span>
                <img src="../assets/logo_disarpus.png" alt="Logo Disarpus" class="sidebar-logo">
            </div>
            <button class="btn btn-sm btn-outline-dark d-lg-none" onclick="toggleSidebar(false)"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="nav flex-column">
            <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-fill"></i> DASHBOARD</a>
            <a href="perpustakaan.php" class="nav-link active"><i class="bi bi-building"></i> PERPUSTAKAAN</a>
            <a href="hasil_kuisioner.php" class="nav-link"><i class="bi bi-table"></i> HASIL KUISIONER</a>
            <a href="atur_pertanyaan.php" class="nav-link"><i class="bi bi-file-text"></i>KUISIONER</a>
            <a href="pengaduan.php" class="nav-link"><i class="bi bi-chat-left-text"></i> PENGADUAN</a>
            <a href="users.php" class="nav-link"><i class="bi bi-people-fill"></i> ADMIN</a>
            <div class="mt-5 pt-5 border-top">
                <a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> KELUAR</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4 page-header">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-dark btn-sm d-lg-none" onclick="toggleSidebar(true)"><i class="bi bi-list"></i></button>
                <div>
                    <h1 class="h2 fw-bold m-0 page-title">Manajemen Perpustakaan</h1>
                    <p class="text-muted m-0 page-subtitle">Kelola data unit perpustakaan dan cek status IPLM</p>
                </div>
            </div>
        </div>

        <?php if($pesan): ?>
            <div class="alert alert-<?= $tipe_pesan ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i> <?= $pesan ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3 border-start border-4 border-dark ps-3">
            <div>
                <h5 class="fw-bold m-0">Rincian Statistik Data</h5>
                <small class="text-muted">Periode: <?= $label_periode ?></small>
            </div>
            <button type="button" class="btn-filter" data-bs-toggle="modal" data-bs-target="#filterModal">
                <i class="bi bi-funnel"></i> Filter Periode
            </button>
        </div>
        
        <div class="row g-4 mb-4 stats-compact">
            <div class="col-12">
                <div class="card-clean bg-light border-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold m-0"><i class="bi bi-book-half me-2"></i>Data IPLM</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="stat-box stat-total text-start align-items-start px-3">
                                <span class="stat-label mb-2">Total Perpustakaan</span>
                                <span class="stat-number"><?= $total_perpus ?></span>
                                <small class="stat-sub mt-2">Unit Terdaftar</small>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="row g-3 h-100">
                                <div class="col-12">
                                    <div class="stat-box stat-mini success flex-row justify-content-between px-3">
                                        <div class="text-start"><span class="stat-number d-block"><?= $total_iplm ?></span><span class="stat-label">Sudah Mengisi</span></div>
                                        <div class="icon-box"><i class="bi bi-check-circle"></i></div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="stat-box stat-mini danger flex-row justify-content-between px-3">
                                        <div class="text-start"><span class="stat-number d-block text-danger"><?= $belum_iplm ?></span><span class="stat-label">Belum Mengisi</span></div>
                                        <div class="icon-box"><i class="bi bi-exclamation-circle"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-clean p-4">
            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="lib-tab" data-bs-toggle="tab" data-bs-target="#tab-lib" type="button">Data Perpustakaan</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="kat-tab" data-bs-toggle="tab" data-bs-target="#tab-kat" type="button">Atur Jenis & Subjenis</button>
                </li>
            </ul>

            <div class="tab-content">
                
                <div class="tab-pane fade show active" id="tab-lib">
                    
                    <div class="row g-2 mb-3 bg-light p-3 rounded-3 border align-items-center">
                        <div class="col-md-3 text-md-end fw-bold small text-uppercase text-muted">Cek Status IPLM Periode:</div>
                        <div class="col-md-2">
                            <select id="filterBulan" class="form-select filter-select bg-white">
                                <?php foreach($bulanList as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= ($k == $bulan_pilih) ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="filterTahun" class="form-select filter-select bg-white">
                                <?php for($t=$tahunIni; $t>=$tahunIni-2; $t--): ?>
                                    <option value="<?= $t ?>" <?= ($t == $tahun_pilih) ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-4 align-items-center">
                        <div class="col-md-4">
                            <input type="text" id="liveSearch" class="form-control search-box" placeholder="Ketik nama perpustakaan...">
                        </div>
                        <div class="col-md-3">
                            <select id="filterKategori" class="form-select filter-select">
                                <option value="">Semua Kategori</option>
                                <?php foreach(array_unique(array_column($rawKat, 'kategori')) as $k): ?>
                                    <option value="<?= $k ?>"><?= $k ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterSubjenis" class="form-select filter-select">
                                <option value="">Semua Sub Jenis</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="filterStatus" class="form-select filter-select">
                                <option value="">Semua Status</option>
                                <option value="sudah">Sudah Mengisi</option>
                                <option value="belum">Belum Mengisi</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-outline-secondary fw-bold rounded-pill w-100" type="button" onclick="resetFilters()">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </div>
                        <div class="col-md-2 text-end">
                            <button class="btn btn-dark fw-bold rounded-pill w-100" onclick="openModalLib()">
                                <i class="bi bi-plus-lg me-1"></i> Tambah
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%" class="text-center">No</th>
                                    <th width="40%">Nama Perpustakaan</th>
                                    <th width="15%">Jenis</th>
                                    <th width="20%">Sub Jenis</th>
                                    <th width="10%" class="text-center">Status IPLM</th>
                                    <th width="10%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-dark"></div></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-kat">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-light border-0 p-3 h-100">
                                <h6 class="fw-bold mb-3">Tambah Jenis Baru</h6>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="form_type" value="category">
                                    <input type="hidden" name="aksi" value="tambah">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Kategori Utama</label>
                                        <input type="text" list="listKategori" name="kategori" class="form-control" required placeholder="Contoh: Sekolah">
                                        <datalist id="listKategori"><option value="Sekolah"><option value="Umum"><option value="Khusus"></datalist>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Sub Kategori</label>
                                        <input type="text" name="sub_kategori" class="form-control" required placeholder="Contoh: Perpustakaan SD">
                                    </div>
                                    <button type="submit" class="btn btn-dark w-100">Simpan Jenis</button>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="table-responsive" style="max-height: 500px; overflow-y:auto;">
                                <table class="table table-bordered table-sm align-middle">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Kategori Utama</th>
                                            <th>Sub Kategori</th>
                                            <th class="text-center" width="10%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($rawKat as $row): ?>
                                        <tr>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['kategori']) ?></span></td>
                                            <td><?= htmlspecialchars($row['sub_kategori']) ?></td>
                                            <td class="text-center">
                                                <form method="POST" class="js-confirm" data-confirm-title="Hapus jenis?" data-confirm-text="Jenis ini akan dihapus permanen." data-confirm-button="Ya, hapus" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="form_type" value="category">
                                                    <input type="hidden" name="aksi" value="hapus">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-x-lg"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content rounded-4 border-0 p-3">
                <div class="modal-header border-0"><h5 class="modal-title fw-bold">Pilih Periode</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" name="filter_periode" value="1">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">BULAN</label>
                            <select name="bulan" class="form-select border-secondary">
                                <?php foreach($bulanList as $key => $val): ?>
                                    <option value="<?= $key ?>" <?= ($key == $bulan_pilih) ? 'selected' : '' ?>><?= $val ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">TAHUN</label>
                            <select name="tahun" class="form-select border-secondary">
                                <?php for($t = date('Y'); $t >= date('Y') - 2; $t--): ?>
                                    <option value="<?= $t ?>" <?= ($t == $tahun_pilih) ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 rounded-pill fw-bold">Terapkan Filter</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalLib" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="modalTitle">Tambah Perpustakaan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <ul class="nav nav-pills mb-3 justify-content-center" id="modalTab" role="tablist">
                        <li class="nav-item"><button class="nav-link active btn-sm fw-bold" id="manual-tab" data-bs-toggle="pill" data-bs-target="#tab-manual">Input Manual</button></li>
                        <li class="nav-item"><button class="nav-link btn-sm fw-bold" id="csv-tab" data-bs-toggle="pill" data-bs-target="#tab-csv">Import CSV</button></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-manual">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="form_type" value="library">
                                <input type="hidden" name="aksi" id="formAksi" value="tambah">
                                <input type="hidden" name="id" id="formId">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nama Perpustakaan</label>
                                    <input type="text" name="nama" id="inputNama" class="form-control" required placeholder="Contoh: Perpustakaan SMAN 1 Gerung">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Jenis</label>
                                        <select name="kategori" id="selectKategori" class="form-select" onchange="updateSubJenisModal()" required>
                                            <option value="">-- Pilih --</option>
                                            <?php foreach(array_unique(array_column($rawKat, 'kategori')) as $k): ?>
                                                <option value="<?= $k ?>"><?= $k ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Sub Jenis</label>
                                        <select name="subjenis" id="selectSubJenis" class="form-select" required>
                                            <option value="">-- Pilih Kategori --</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-dark w-100 fw-bold">Simpan Data</button>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="tab-csv">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="form_type" value="library">
                                <input type="hidden" name="aksi" value="import_csv">
                                <div class="alert alert-info small">Format: Nama, Kategori, Subjenis. <br>Nama akan otomatis dikapitalisasi.</div>
                                <div class="mb-3"><input type="file" name="file_csv" class="form-control" accept=".csv" required></div>
                                <button type="submit" class="btn btn-success w-100 fw-bold">Import CSV</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="formHapus" method="POST" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="form_type" value="library">
        <input type="hidden" name="aksi" value="hapus">
        <input type="hidden" name="id" id="hapusId">
    </form>

    <form id="formResetStatus" method="POST" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="form_type" value="library">
        <input type="hidden" name="aksi" value="reset_status">
        <input type="hidden" name="library_id" id="resetLibraryId">
        <input type="hidden" name="jenis" value="IPLM">
        <input type="hidden" name="bulan" value="<?= $bulan_pilih ?>">
        <input type="hidden" name="tahun" value="<?= $tahun_pilih ?>">
    </form>

    <div class="modal fade" id="modalStatusIplm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">Pengaturan Status IPLM</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-2 fw-bold" id="statusLibName"></div>
                    <div class="small text-muted mb-3">Periode: <?= $label_periode ?></div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="status_iplm" id="statusSudah" value="sudah" disabled>
                        <label class="form-check-label" for="statusSudah">Sudah Mengisi</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="status_iplm" id="statusBelum" value="belum">
                        <label class="form-check-label" for="statusBelum">Belum Mengisi</label>
                    </div>

                    <div class="alert alert-warning small mb-0" id="statusWarning">
                        Mengubah ke "Belum Mengisi" akan menghapus data jawaban pada periode ini.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-dark fw-bold" id="btnSimpanStatus" disabled>Simpan</button>
                </div>
            </div>
        </div>
    </div>

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

        const subJenisData = <?= json_encode($strukturJenis) ?>;
        const modalLib = new bootstrap.Modal(document.getElementById('modalLib'));
        const modalStatusIplm = new bootstrap.Modal(document.getElementById('modalStatusIplm'));
        let debounceTimer;
        
        function loadTable(page = 1) {
            const search = document.getElementById('liveSearch').value;
            const kat = document.getElementById('filterKategori').value;
            const sub = document.getElementById('filterSubjenis').value;
            const status = document.getElementById('filterStatus').value;
            const bln = document.getElementById('filterBulan').value;
            const thn = document.getElementById('filterTahun').value;
            const tbody = document.getElementById('tableBody');

            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-dark"></div></td></tr>';

            const url = `perpustakaan.php?ajax_action=load_table&search=${encodeURIComponent(search)}&kategori=${encodeURIComponent(kat)}&subjenis=${encodeURIComponent(sub)}&status_iplm=${encodeURIComponent(status)}&bulan=${bln}&tahun=${thn}&page=${page}`;

            fetch(url)
                .then(response => response.text())
                .then(html => {
                    tbody.innerHTML = html;
                    attachEditDeleteEvents();
                    bindConfirmForms(tbody);
                    bindStatusEvents(tbody);
                });
        }

        // Listeners
        document.getElementById('liveSearch').addEventListener('keyup', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadTable(1), 300);
        });
        document.getElementById('filterKategori').addEventListener('change', function() {
            const kat = this.value;
            const subSelect = document.getElementById('filterSubjenis');
            subSelect.innerHTML = '<option value="">Semua Sub Jenis</option>';
            if (kat && subJenisData[kat]) {
                subJenisData[kat].forEach(sub => {
                    const opt = document.createElement('option');
                    opt.value = sub; opt.text = sub; subSelect.appendChild(opt);
                });
            }
            loadTable(1);
        });
        document.getElementById('filterSubjenis').addEventListener('change', () => loadTable(1));
        document.getElementById('filterStatus').addEventListener('change', () => loadTable(1));
        document.getElementById('filterBulan').addEventListener('change', () => loadTable(1));
        document.getElementById('filterTahun').addEventListener('change', () => loadTable(1));

        loadTable();

        // Modal Helpers
        function updateSubJenisModal(selectedValue = null) {
            const kat = document.getElementById('selectKategori').value;
            const subSelect = document.getElementById('selectSubJenis');
            subSelect.innerHTML = '<option value="">-- Pilih Sub Jenis --</option>';
            if (kat && subJenisData[kat]) {
                subJenisData[kat].forEach(sub => {
                    const opt = document.createElement('option');
                    opt.value = sub; opt.text = sub;
                    if (selectedValue && sub === selectedValue) opt.selected = true;
                    subSelect.appendChild(opt);
                });
            }
        }

        function openModalLib() {
            document.getElementById('modalTitle').innerText = "Tambah Perpustakaan";
            new bootstrap.Tab(document.querySelector('#manual-tab')).show();
            document.getElementById('formAksi').value = "tambah";
            document.getElementById('formId').value = "";
            document.getElementById('inputNama').value = "";
            document.getElementById('selectKategori').value = "";
            updateSubJenisModal(); 
            modalLib.show();
        }

        function attachEditDeleteEvents() {
            document.querySelectorAll('.btn-edit-lib').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const nama = this.getAttribute('data-nama');
                    const kategori = this.getAttribute('data-kategori');
                    const subjenis = this.getAttribute('data-subjenis');

                    document.getElementById('modalTitle').innerText = "Edit Perpustakaan";
                    new bootstrap.Tab(document.querySelector('#manual-tab')).show();
                    document.getElementById('formAksi').value = "edit";
                    document.getElementById('formId').value = id;
                    document.getElementById('inputNama').value = nama;
                    document.getElementById('selectKategori').value = kategori;
                    updateSubJenisModal(subjenis);
                    modalLib.show();
                });
            });
            document.querySelectorAll('.btn-hapus-lib').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const doSubmit = () => {
                        document.getElementById('hapusId').value = id;
                        document.getElementById('formHapus').submit();
                    };
                    if (window.Swal) {
                        Swal.fire({
                            title: 'Hapus perpustakaan?',
                            text: 'Data perpustakaan akan dihapus permanen.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, hapus',
                            cancelButtonText: 'Batal',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) doSubmit();
                        });
                    } else if (confirm('Yakin ingin menghapus perpustakaan ini?')) {
                        doSubmit();
                    }
                });
            });
        }

        function bindStatusEvents(root = document) {
            root.querySelectorAll('.btn-status-iplm').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const status = this.getAttribute('data-status');
                    const nama = this.getAttribute('data-nama');

                    document.getElementById('statusLibName').innerText = nama || '-';
                    document.getElementById('resetLibraryId').value = id;

                    const radioSudah = document.getElementById('statusSudah');
                    const radioBelum = document.getElementById('statusBelum');
                    const btnSimpan = document.getElementById('btnSimpanStatus');

                    if (status === 'sudah') {
                        radioSudah.checked = true;
                        radioBelum.checked = false;
                        btnSimpan.disabled = false;
                    } else {
                        radioSudah.checked = false;
                        radioBelum.checked = true;
                        btnSimpan.disabled = true;
                    }

                    modalStatusIplm.show();
                });
            });
        }

        document.getElementById('btnSimpanStatus').addEventListener('click', () => {
            const radioBelum = document.getElementById('statusBelum');
            if (radioBelum.checked) {
                if (window.Swal) {
                    Swal.fire({
                        title: 'Reset status?',
                        text: 'Data jawaban pada periode ini akan dihapus permanen.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, reset',
                        cancelButtonText: 'Batal',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('formResetStatus').submit();
                        }
                    });
                } else {
                    document.getElementById('formResetStatus').submit();
                }
            }
        });

        function resetFilters() {
            document.getElementById('liveSearch').value = '';
            document.getElementById('filterKategori').value = '';
            const subSelect = document.getElementById('filterSubjenis');
            subSelect.innerHTML = '<option value="">Semua Sub Jenis</option>';
            document.getElementById('filterStatus').value = '';
            document.getElementById('filterBulan').value = '<?= $bulan_pilih ?>';
            document.getElementById('filterTahun').value = '<?= $tahun_pilih ?>';
            loadTable(1);
        }

        bindConfirmForms();
        bindStatusEvents();
    </script>
    <script src="../assets/loader.js"></script>
</body>
</html>
