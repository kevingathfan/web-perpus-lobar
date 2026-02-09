<?php
// web-perpus-v1/admin/dashboard.php
session_start();
require '../config/database.php';
require '../config/admin_auth.php';

// --- 1. SETTING ZONA WAKTU ---
date_default_timezone_set('Asia/Makassar'); 

// --- FILTER STATE (SESSION, CLEAN URL) ---
$dashboard_filter = $_SESSION['dashboard_filter'] ?? [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_chart'])) {
    $dashboard_filter['tahun_chart'] = $_POST['tahun_chart'] ?? date('Y');
    $_SESSION['dashboard_filter'] = $dashboard_filter;
    header("Location: dashboard.php"); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_range'])) {
    $dashboard_filter['range_start_bulan'] = $_POST['range_start_bulan'] ?? '01';
    $dashboard_filter['range_start_tahun'] = $_POST['range_start_tahun'] ?? date('Y');
    $dashboard_filter['range_end_bulan'] = $_POST['range_end_bulan'] ?? date('m');
    $dashboard_filter['range_end_tahun'] = $_POST['range_end_tahun'] ?? date('Y');
    $_SESSION['dashboard_filter'] = $dashboard_filter;
    header("Location: dashboard.php"); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_periode'])) {
    $dashboard_filter['bulan'] = $_POST['bulan'] ?? date('m');
    $dashboard_filter['tahun'] = $_POST['tahun'] ?? date('Y');
    $_SESSION['dashboard_filter'] = $dashboard_filter;
    header("Location: dashboard.php"); exit;
}
if (!empty($_GET)) {
    $keys = ['tahun_chart','range_start_bulan','range_start_tahun','range_end_bulan','range_end_tahun','bulan','tahun'];
    $incoming = [];
    foreach ($keys as $k) {
        if (isset($_GET[$k])) $incoming[$k] = $_GET[$k];
    }
    if (!empty($incoming)) {
        $_SESSION['dashboard_filter'] = array_merge($dashboard_filter, $incoming);
        header("Location: dashboard.php"); exit;
    }
}

// ==========================================
// 2. LOGIKA STATUS & JADWAL
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A. Toggle Manual
    if (isset($_POST['aksi_status']) && $_POST['aksi_status'] == 'toggle') {
        $kunci = $_POST['kunci']; 
        $status_baru = $_POST['status_baru'];
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$status_baru, $kunci]);
        
        $kunci_mode = ($kunci == 'status_iplm') ? 'iplm_mode' : 'tkm_mode';
        $pdo->prepare("UPDATE settings SET setting_value = 'manual' WHERE setting_key = ?")->execute([$kunci_mode]);
        header("Location: dashboard.php"); exit;
    }
    // B. Simpan Jadwal
    if (isset($_POST['aksi_status']) && $_POST['aksi_status'] == 'save_schedule') {
        $jenis = $_POST['jenis'];
        $mode  = $_POST['mode'];
        $start = str_replace('T', ' ', $_POST['start_date']);
        $end   = str_replace('T', ' ', $_POST['end_date']);

        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?")->execute([$mode, $jenis . '_mode']);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?")->execute([$start, $jenis . '_start']);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?")->execute([$end, $jenis . '_end']);
        header("Location: dashboard.php"); exit;
    }
}

// Ambil Settings
$stmtSet = $pdo->query("SELECT * FROM settings");
$settings = $stmtSet->fetchAll(PDO::FETCH_KEY_PAIR);

function getStatusInfo($jenis, $settings) {
    $mode = $settings[$jenis . '_mode'] ?? 'manual';
    $manualStatus = $settings['status_' . $jenis] ?? 'buka';
    $start = $settings[$jenis . '_start'] ?? '';
    $end = $settings[$jenis . '_end'] ?? '';
    $now = date('Y-m-d H:i:s');
    $isOpen = false; $label = ""; $desc = "";

    if ($mode == 'manual') {
        $isOpen = ($manualStatus == 'buka');
        $label = $isOpen ? "MANUAL: DIBUKA" : "MANUAL: DITUTUP";
        $desc = "Diatur secara manual.";
    } else {
        if ($start && $end) {
            if ($now >= $start && $now <= $end) {
                $isOpen = true; $label = "TERJADWAL: BERJALAN"; $desc = "Tutup: ".date('d M H:i', strtotime($end));
            } elseif ($now < $start) {
                $isOpen = false; $label = "TERJADWAL: MENUNGGU"; $desc = "Buka: ".date('d M H:i', strtotime($start));
            } else {
                $isOpen = false; $label = "TERJADWAL: SELESAI"; $desc = "Tutup: ".date('d M H:i', strtotime($end));
            }
        } else {
            $isOpen = false; $label = "TERJADWAL: BELUM SET"; $desc = "Atur tanggal dulu.";
        }
    }
    return ['open' => $isOpen, 'label' => $label, 'desc' => $desc, 'mode' => $mode];
}
$infoIPLM = getStatusInfo('iplm', $settings);
$infoTKM  = getStatusInfo('tkm', $settings);


// ==========================================
// 3. LOGIKA CHART DINAMIS
// ==========================================
$dashboard_filter = $_SESSION['dashboard_filter'] ?? $dashboard_filter;
$tahun_chart = $dashboard_filter['tahun_chart'] ?? date('Y');

function getDataBulanan($pdo, $jenis, $tahun) {
    $sql = "SELECT periode_bulan as bulan, COUNT(*) as total 
            FROM trans_header 
            WHERE jenis_kuesioner = :jenis 
            AND periode_tahun = :thn 
            GROUP BY periode_bulan 
            ORDER BY periode_bulan ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':jenis' => $jenis, ':thn' => $tahun]);
    $hasil = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $data_final = [];
    for ($i = 1; $i <= 12; $i++) {
        $key = str_pad($i, 2, '0', STR_PAD_LEFT); 
        $data_final[] = isset($hasil[$key]) ? (int)$hasil[$key] : 0;
    }
    return $data_final;
}

$data_iplm_chart = getDataBulanan($pdo, 'IPLM', $tahun_chart);
$data_tkm_chart  = getDataBulanan($pdo, 'TKM', $tahun_chart);
$total_responden_tahunan = array_sum($data_iplm_chart) + array_sum($data_tkm_chart);
$chart_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

// ==========================================
// 4. TOTAL RESPONDEN (RENTANG PERIODE)
// ==========================================
$list_bulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
$range_start_bulan = $dashboard_filter['range_start_bulan'] ?? '01';
$range_start_tahun = $dashboard_filter['range_start_tahun'] ?? date('Y');
$range_end_bulan = $dashboard_filter['range_end_bulan'] ?? date('m');
$range_end_tahun = $dashboard_filter['range_end_tahun'] ?? date('Y');

$range_start_bulan = str_pad((int)$range_start_bulan, 2, '0', STR_PAD_LEFT);
$range_end_bulan = str_pad((int)$range_end_bulan, 2, '0', STR_PAD_LEFT);
$range_start_tahun = (int)$range_start_tahun;
$range_end_tahun = (int)$range_end_tahun;

$range_start_key = sprintf('%04d-%02d', $range_start_tahun, (int)$range_start_bulan);
$range_end_key = sprintf('%04d-%02d', $range_end_tahun, (int)$range_end_bulan);

if ($range_start_key > $range_end_key) {
    $tmp_key = $range_start_key;
    $range_start_key = $range_end_key;
    $range_end_key = $tmp_key;

    $tmp_bulan = $range_start_bulan;
    $range_start_bulan = $range_end_bulan;
    $range_end_bulan = $tmp_bulan;

    $tmp_tahun = $range_start_tahun;
    $range_start_tahun = $range_end_tahun;
    $range_end_tahun = $tmp_tahun;
}

$range_label = ($range_start_key === $range_end_key)
    ? ($list_bulan[$range_start_bulan] ?? $range_start_bulan) . " " . $range_start_tahun
    : ($list_bulan[$range_start_bulan] ?? $range_start_bulan) . " " . $range_start_tahun . " - " . ($list_bulan[$range_end_bulan] ?? $range_end_bulan) . " " . $range_end_tahun;

try {
    $stmtRangeIplm = $pdo->prepare("SELECT COUNT(*) FROM trans_header 
        WHERE jenis_kuesioner = 'IPLM' 
        AND (LPAD(CAST(periode_tahun AS text), 4, '0') || '-' || LPAD(CAST(periode_bulan AS text), 2, '0')) BETWEEN :start_key AND :end_key");
    $stmtRangeIplm->execute([':start_key' => $range_start_key, ':end_key' => $range_end_key]);
    $total_range_iplm = $stmtRangeIplm->fetchColumn();
} catch (Exception $e) { $total_range_iplm = 0; }

try {
    $stmtRangeTkm = $pdo->prepare("SELECT COUNT(*) FROM trans_header 
        WHERE jenis_kuesioner = 'TKM' 
        AND (LPAD(CAST(periode_tahun AS text), 4, '0') || '-' || LPAD(CAST(periode_bulan AS text), 2, '0')) BETWEEN :start_key AND :end_key");
    $stmtRangeTkm->execute([':start_key' => $range_start_key, ':end_key' => $range_end_key]);
    $total_range_tkm = $stmtRangeTkm->fetchColumn();
} catch (Exception $e) { $total_range_tkm = 0; }

$total_responden_range = (int)$total_range_iplm + (int)$total_range_tkm;


// ==========================================
// 5. LOGIKA KARTU STATISTIK (BULANAN)
// ==========================================
$bulan_pilih = $dashboard_filter['bulan'] ?? date('m');
$tahun_pilih = $dashboard_filter['tahun'] ?? date('Y');
$label_periode = $list_bulan[$bulan_pilih] . " " . $tahun_pilih;


// (perpustakaan teraktif dihapus sesuai permintaan)
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - DISARPUS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/loader.css">
    <link rel="stylesheet" href="../assets/admin-responsive.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; overflow-x: hidden; }
        .sidebar { min-height: 100vh; width: 260px; background-color: #ffffff; border-right: 1px solid #e0e0e0; position: fixed; top: 0; left: 0; padding: 40px 20px; z-index: 100; }
        .sidebar-header { font-weight: 800; font-size: 24px; margin-bottom: 50px; color: #000; text-align: center; letter-spacing: 1px; }
        .nav-link { color: #666; font-weight: 600; font-size: 15px; padding: 12px 20px; margin-bottom: 8px; border-radius: 8px; transition: all 0.3s; display: flex; align-items: center; gap: 10px; }
        .nav-link:hover, .nav-link.active { background-color: #000; color: #fff; }
        .main-content { margin-left: 260px; padding: 40px 50px; }
        .card-clean { background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 16px; padding: 25px; height: 100%; box-shadow: 0 5px 20px rgba(0,0,0,0.03); transition: transform 0.3s; }
        .card-clean:hover { transform: translateY(-3px); }
        .status-card { border-left: 5px solid; transition: 0.3s; position: relative; }
        .status-open { border-left-color: #198754; background-color: #f8fff9; } 
        .status-closed { border-left-color: #dc3545; background-color: #fff8f8; }
        .stat-box { background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 12px; padding: 20px; text-align: center; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .stat-box.highlight { background-color: #000; color: #fff; border: 1px solid #000; }
        .stat-number { font-size: 32px; font-weight: 800; line-height: 1.2; }
        .btn-filter { background-color: #f1f3f5; color: #333; border: none; padding: 8px 16px; border-radius: 30px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; cursor: pointer; }
        .badge-count { background: #000; color: #fff; padding: 2px 8px; border-radius: 6px; font-size: 12px; }
        .list-active li { padding: 12px 0; border-bottom: 1px dashed #e0e0e0; font-size: 14px; display: flex; justify-content: space-between; }
        .list-active li:last-child { border-bottom: none; }
        .btn-export-text { font-size: 13px; font-weight: 600; text-decoration: none; color: #666; padding: 6px 12px; border-radius: 6px; transition: 0.2s; }
        .btn-export-text:hover { background: #eee; color: #000; }
        .range-inline { border-top: 1px dashed #dee2e6; padding-top: 18px; margin-top: 14px; }
        .range-panel { border: 1px solid #e9ecef; border-radius: 14px; padding: 14px 16px; background: #f8f9fa; }
        .range-pill { border: 1px solid #e9ecef; border-radius: 999px; padding: 6px 10px; font-size: 12px; color: #6c757d; background: #fff; display: inline-block; }
        .range-kpi { border: 1px solid #e9ecef; background: #fff; border-radius: 14px; padding: 12px 14px; }
        .range-kpi .label { font-size: 11px; letter-spacing: 0.4px; text-transform: uppercase; color: #6c757d; }
        .range-kpi .value { font-size: 20px; font-weight: 800; line-height: 1.1; }
        .range-kpi.total { border-color: #111; }
        .range-form .form-select { border-radius: 10px; }
        .range-form .btn { border-radius: 10px; }
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
            <a href="dashboard.php" class="nav-link active"><i class="bi bi-grid-fill"></i> DASHBOARD</a>
            <a href="perpustakaan.php" class="nav-link"><i class="bi bi-building"></i> PERPUSTAKAAN</a>
            <a href="hasil_kuisioner.php" class="nav-link"><i class="bi bi-table"></i> HASIL KUISIONER</a>
            <a href="atur_pertanyaan.php" class="nav-link"><i class="bi bi-file-text"></i> KUISIONER</a>
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
                <div><h1 class="h2 fw-bold m-0 page-title">Dashboard Admin</h1><p class="text-muted m-0 page-subtitle">Statistik dan Pengaturan Akses</p></div>
            </div>
            <button class="btn btn-dark btn-sm rounded-pill px-3"><?= date('d M Y') ?></button>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card card-clean p-3 <?= $infoIPLM['open'] ? 'status-open' : 'status-closed' ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="fw-bold m-0 mb-1"><i class="bi bi-file-earmark-text-fill me-2"></i>Status IPLM</h6>
                            <span class="badge <?= $infoIPLM['open'] ? 'bg-success' : 'bg-danger' ?> mb-2"><?= $infoIPLM['label'] ?></span>
                            <p class="text-muted small m-0"><?= $infoIPLM['desc'] ?></p>
                        </div>
                        <div class="text-end">
                            <?php if($infoIPLM['mode'] == 'manual'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="aksi_status" value="toggle"><input type="hidden" name="kunci" value="status_iplm">
                                    <input type="hidden" name="status_baru" value="<?= $infoIPLM['open'] ? 'tutup' : 'buka' ?>">
                                    <button class="btn btn-sm btn-dark rounded-pill fw-bold mb-2 w-100"><?= $infoIPLM['open'] ? 'TUTUP' : 'BUKA' ?></button>
                                </form>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-dark rounded-pill w-100" data-bs-toggle="modal" data-bs-target="#modalJadwalIPLM">Atur Jadwal</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-clean p-3 <?= $infoTKM['open'] ? 'status-open' : 'status-closed' ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="fw-bold m-0 mb-1"><i class="bi bi-people-fill me-2"></i>Status TKM</h6>
                            <span class="badge <?= $infoTKM['open'] ? 'bg-success' : 'bg-danger' ?> mb-2"><?= $infoTKM['label'] ?></span>
                            <p class="text-muted small m-0"><?= $infoTKM['desc'] ?></p>
                        </div>
                        <div class="text-end">
                            <?php if($infoTKM['mode'] == 'manual'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="aksi_status" value="toggle"><input type="hidden" name="kunci" value="status_tkm">
                                    <input type="hidden" name="status_baru" value="<?= $infoTKM['open'] ? 'tutup' : 'buka' ?>">
                                    <button class="btn btn-sm btn-dark rounded-pill fw-bold mb-2 w-100"><?= $infoTKM['open'] ? 'TUTUP' : 'BUKA' ?></button>
                                </form>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-dark rounded-pill w-100" data-bs-toggle="modal" data-bs-target="#modalJadwalTKM">Atur Jadwal</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="card-clean">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="fw-bold mb-1">Statistik Kuisioner</h5>
                            <small class="text-muted"><?= number_format($total_responden_tahunan) ?> Total Responden (Tahun <?= $tahun_chart ?>)</small>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="POST" id="formChartYear">
                                <input type="hidden" name="filter_chart" value="1">
                                <select name="tahun_chart" class="form-select form-select-sm border-secondary fw-bold" style="width: auto;" onchange="this.form.submit()">
                                    <?php 
                                    $thn_skrg = date('Y');
                                    for($t = $thn_skrg; $t >= $thn_skrg-3; $t--): ?>
                                        <option value="<?= $t ?>" <?= ($t == $tahun_chart) ? 'selected' : '' ?>>Tahun <?= $t ?></option>
                                    <?php endfor; ?>
                                </select>
                            </form>
                        </div>
                    </div>
                    <div style="height: 300px;"><canvas id="myChart"></canvas></div>

                    <div class="range-inline">
                        <div class="range-panel">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div>
                                    <h6 class="fw-bold mb-1">Total Responden (Rentang)</h6>
                                    <div class="range-pill">Periode: <?= $range_label ?></div>
                                </div>
                            </div>

                            <div class="row g-3 align-items-stretch">
                                <div class="col-lg-7">
                                    <form method="POST" class="range-form row g-2 align-items-center">
                                        <input type="hidden" name="filter_range" value="1">
                                        <div class="col-12 fw-bold small">DARI:</div>
                                        <div class="col-md-6 col-7">
                                            <select name="range_start_bulan" class="form-select">
                                                <?php foreach($list_bulan as $k => $v): ?>
                                                    <option value="<?= $k ?>" <?= ($k == $range_start_bulan) ? 'selected' : '' ?>><?= $v ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4 col-5">
                                            <select name="range_start_tahun" class="form-select">
                                                <?php for($t = date('Y'); $t >= date('Y')-2; $t--): ?>
                                                    <option value="<?= $t ?>" <?= ($t == $range_start_tahun) ? 'selected' : '' ?>><?= $t ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-12 fw-bold small mt-2">SAMPAI:</div>
                                        <div class="col-md-6 col-7">
                                            <select name="range_end_bulan" class="form-select">
                                                <?php foreach($list_bulan as $k => $v): ?>
                                                    <option value="<?= $k ?>" <?= ($k == $range_end_bulan) ? 'selected' : '' ?>><?= $v ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4 col-5">
                                            <select name="range_end_tahun" class="form-select">
                                                <?php for($t = date('Y'); $t >= date('Y')-2; $t--): ?>
                                                    <option value="<?= $t ?>" <?= ($t == $range_end_tahun) ? 'selected' : '' ?>><?= $t ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4 col-12 mt-2">
                                            <button type="submit" class="btn btn-dark w-100">Terapkan</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-lg-5">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="range-kpi">
                                                <div class="label">IPLM</div>
                                                <div class="value text-primary"><?= number_format($total_range_iplm) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="range-kpi">
                                                <div class="label">TKM</div>
                                                <div class="value text-danger"><?= number_format($total_range_tkm) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="range-kpi total d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="label">Total Responden</div>
                                                    <div class="value"><?= number_format($total_responden_range) ?></div>
                                                </div>
                                                <i class="bi bi-graph-up-arrow fs-3 text-dark"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </main>

    <?php foreach(['IPLM', 'TKM'] as $j): $low = strtolower($j); ?>
    <div class="modal fade" id="modalJadwal<?= $j ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0">
                <div class="modal-header bg-dark text-white"><h5 class="modal-title fw-bold">Pengaturan <?= $j ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <div class="modal-body p-4">
                        <input type="hidden" name="aksi_status" value="save_schedule">
                        <input type="hidden" name="jenis" value="<?= $low ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Mode Akses</label>
                            <select name="mode" class="form-select" onchange="toggleDateInput('<?= $low ?>', this.value)">
                                <option value="manual" <?= ($settings[$low.'_mode']??'')=='manual'?'selected':'' ?>>Manual</option>
                                <option value="auto" <?= ($settings[$low.'_mode']??'')=='auto'?'selected':'' ?>>Otomatis</option>
                            </select>
                        </div>
                        <div id="date-inputs-<?= $low ?>" style="<?= ($settings[$low.'_mode']??'')=='manual'?'display:none':'' ?>">
                            <div class="mb-3"><label class="form-label small">Mulai</label><input type="datetime-local" name="start_date" class="form-control" value="<?= !empty($settings[$low.'_start']) ? date('Y-m-d\TH:i', strtotime($settings[$low.'_start'])) : '' ?>"></div>
                            <div class="mb-3"><label class="form-label small">Selesai</label><input type="datetime-local" name="end_date" class="form-control" value="<?= !empty($settings[$low.'_end']) ? date('Y-m-d\TH:i', strtotime($settings[$low.'_end'])) : '' ?>"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-0"><button type="submit" class="btn btn-dark fw-bold">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar(open) {
            document.body.classList.toggle('sidebar-open', open);
        }

        document.querySelectorAll('.sidebar .nav-link').forEach((link) => {
            link.addEventListener('click', () => toggleSidebar(false));
        });

        function toggleDateInput(jenis, val) {
            document.getElementById('date-inputs-' + jenis).style.display = (val === 'auto') ? 'block' : 'none';
        }

        const ctx = document.getElementById('myChart');
        const gradientIplm = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradientIplm.addColorStop(0, '#0000001a'); gradientIplm.addColorStop(1, 'rgba(0, 0, 0, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [
                    {
                        label: 'IPLM',
                        data: <?= json_encode($data_iplm_chart) ?>, 
                        borderWidth: 2, borderColor: '#0d6efd', backgroundColor: gradientIplm, pointBackgroundColor: '#ffffff', pointRadius: 4, tension: 0.4, fill: true
                    },
                    {
                        label: 'TKM',
                        data: <?= json_encode($data_tkm_chart) ?>, 
                        borderWidth: 2, borderColor: '#d62a2a', backgroundColor: 'transparent', pointBackgroundColor: '#d62a2a', pointBorderColor: '#ffffff', pointBorderWidth: 2, pointRadius: 4, tension: 0.4, borderDash: [5, 5]
                    }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { grid: { display: false } } } }
        });
    </script>
    <script src="../assets/loader.js"></script>
</body>
</html>
