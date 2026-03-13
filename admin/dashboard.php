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
    $start = str_replace('T', ' ', $settings[$jenis . '_start'] ?? '');
    $end = str_replace('T', ' ', $settings[$jenis . '_end'] ?? '');
    $now = time();
    $isOpen = false; $label = ""; $desc = "";

    if ($mode == 'manual') {
        $isOpen = ($manualStatus == 'buka');
        $label = $isOpen ? "MANUAL: DIBUKA" : "MANUAL: DITUTUP";
        $desc = "Diatur secara manual.";
    } else {
        if ($start && $end) {
            $startTs = strtotime($start);
            $endTs = strtotime($end);
            if ($startTs && $endTs && $now >= $startTs && $now <= $endTs) {
                $isOpen = true; $label = "TERJADWAL: BERJALAN"; $desc = "Tutup: ".date('d M H:i', $endTs);
            } elseif ($startTs && $now < $startTs) {
                $isOpen = false; $label = "TERJADWAL: MENUNGGU"; $desc = "Buka: ".date('d M H:i', $startTs);
            } else {
                $isOpen = false; $label = "TERJADWAL: SELESAI"; $desc = "Tutup: ".date('d M H:i', $endTs ?: strtotime($end));
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
        AND CONCAT(LPAD(periode_tahun, 4, '0'), '-', LPAD(periode_bulan, 2, '0')) BETWEEN :start_key AND :end_key");
    $stmtRangeIplm->execute([':start_key' => $range_start_key, ':end_key' => $range_end_key]);
    $total_range_iplm = $stmtRangeIplm->fetchColumn();
} catch (Exception $e) { $total_range_iplm = 0; }

try {
    $stmtRangeTkm = $pdo->prepare("SELECT COUNT(*) FROM trans_header 
        WHERE jenis_kuesioner = 'TKM' 
        AND CONCAT(LPAD(periode_tahun, 4, '0'), '-', LPAD(periode_bulan, 2, '0')) BETWEEN :start_key AND :end_key");
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

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Royal GovTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/govtech.css">
    <link rel="stylesheet" href="../assets/admin-readability.css">
    <link rel="stylesheet" href="../assets/loader.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>

    </style>
</head>
<body>
    <?php include __DIR__ . '/../config/loader.php'; ?>
    <div class="sidebar-backdrop" onclick="toggleSidebar(false)"></div>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <h6 class="mb-0 fw-bold">ADMIN PANEL</h6>
            </div>
            <button class="btn btn-sm btn-light d-lg-none" onclick="toggleSidebar(false)">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <div class="nav flex-column gap-1">
            <div class="sidebar-label">Utama</div>
            <a href="dashboard.php" class="nav-link active">
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
            <a href="atur_pertanyaan.php" class="nav-link">
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

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-white shadow-sm d-lg-none" onclick="toggleSidebar(true)">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h2 class="fw-bold mb-0 text-dark">Dashboard Overview</h2>
                    <p class="text-muted mb-0">Pantau statistik dan kelola akses survey.</p>
                </div>
            </div>
            <div class="bg-white px-3 py-2 rounded-pill shadow-sm border d-flex align-items-center gap-2">
                <i class="bi bi-calendar-day text-primary"></i>
                <span class="fw-bold text-dark small"><?= date('l, d M Y') ?></span>
            </div>
        </div>

        <!-- Kontrol Akses Kuesioner (Compact Design) -->
        <div class="row g-4 mb-5">
            <?php foreach([
                ['key' => 'IPLM', 'info' => $infoIPLM, 'status_key' => 'status_iplm', 'subtitle' => 'Literasi Masyarakat'],
                ['key' => 'TKM',  'info' => $infoTKM,  'status_key' => 'status_tkm',  'subtitle' => 'Kegemaran Membaca']
            ] as $sess): 
                $isOpen = $sess['info']['open'];
            ?>
            <div class="col-md-6">
                <div class="card-clean p-3 px-4 d-flex align-items-center justify-content-between gap-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm" 
                             style="width: 50px; height: 50px; background-color: <?= $isOpen ? '#dcfce7' : '#fee2e2' ?>; color: <?= $isOpen ? '#166534' : '#991b1b' ?>;">
                            <i class="bi <?= $isOpen ? 'bi-broadcast' : 'bi-lock-fill' ?> fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0"><?= $sess['key'] ?></h6>
                            <div class="small fw-bold <?= $isOpen ? 'text-success' : 'text-danger' ?>">
                                <?= $isOpen ? 'Sesi Dibuka' : 'Sesi Ditutup' ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <?php if($sess['info']['mode'] == 'manual'): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="aksi_status" value="toggle">
                            <input type="hidden" name="kunci" value="<?= $sess['status_key'] ?>">
                            <input type="hidden" name="status_baru" value="<?= $isOpen ? 'tutup' : 'buka' ?>">
                            <button class="btn <?= $isOpen ? 'btn-danger' : 'btn-success' ?> rounded-pill px-4 btn-sm fw-bold shadow-sm" style="min-width: 100px;">
                                <?= $isOpen ? 'Matikan' : 'Aktifkan' ?>
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="badge bg-secondary rounded-pill">Otomatis</span>
                        <?php endif; ?>
                        
                        <button class="btn btn-light btn-sm rounded-circle text-muted" data-bs-toggle="modal" data-bs-target="#modalJadwal<?= $sess['key'] ?>" title="Jadwal Otomatis">
                            <i class="bi bi-gear-fill"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Charts & Stats -->
        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="card-clean p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                        <div>
                            <h5 class="fw-bold mb-1 text-dark">Statistik Partisipasi</h5>
                            <div class="d-flex align-items-center gap-2 text-muted small">
                                <i class="bi bi-bar-chart-fill text-primary"></i>
                                <span>Total <?= number_format($total_responden_tahunan) ?> Responden di Tahun <?= $tahun_chart ?></span>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="POST" id="formChartYear">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="filter_chart" value="1">
                                <select name="tahun_chart" class="form-select border-secondary fw-bold bg-white shadow-sm" style="width: auto; min-width: 150px;" onchange="this.form.submit()">
                                    <?php 
                                    $thn_skrg = date('Y');
                                    for($t = $thn_skrg; $t >= $thn_skrg-3; $t--): ?>
                                        <option value="<?= $t ?>" <?= ($t == $tahun_chart) ? 'selected' : '' ?>>Tahun <?= $t ?></option>
                                    <?php endfor; ?>
                                </select>
                            </form>
                        </div>
                    </div>
                    
                    <div style="height: 350px; width: 100%;">
                        <canvas id="myChart"></canvas>
                    </div>

                    <!-- Range Filter Panel -->
                    <div class="mt-5 pt-4 border-top">
                        <div class="bg-light rounded-4 p-4 border">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark">Analisis Rentang Waktu</h6>
                                    <span class="badge bg-white text-dark border shadow-sm px-3 py-2">
                                        <i class="bi bi-calendar-week me-2 text-primary"></i><?= $range_label ?>
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-end">
                                        <div class="small text-muted fw-bold">TOTAL DATA</div>
                                        <div class="h3 fw-bold mb-0 text-primary"><?= number_format($total_responden_range) ?></div>
                                    </div>
                                    <div class="vr mx-2"></div>
                                    <div class="d-flex gap-3">
                                        <div class="text-center">
                                            <div class="small text-muted fw-bold">IPLM</div>
                                            <div class="h5 fw-bold mb-0"><?= number_format($total_range_iplm) ?></div>
                                        </div>
                                        <div class="text-center">
                                            <div class="small text-muted fw-bold">TKM</div>
                                            <div class="h5 fw-bold mb-0"><?= number_format($total_range_tkm) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form method="POST" class="row g-3 align-items-end">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="filter_range" value="1">
                                
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">DARI BULAN</label>
                                    <select name="range_start_bulan" class="form-select bg-white">
                                        <?php foreach($list_bulan as $k => $v): ?>
                                            <option value="<?= $k ?>" <?= ($k == $range_start_bulan) ? 'selected' : '' ?>><?= $v ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">TAHUN</label>
                                    <select name="range_start_tahun" class="form-select bg-white">
                                        <?php for($t = date('Y'); $t >= date('Y')-2; $t--): ?>
                                            <option value="<?= $t ?>" <?= ($t == $range_start_tahun) ? 'selected' : '' ?>><?= $t ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-1 text-center py-2"><i class="bi bi-arrow-right text-muted"></i></div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">SAMPAI BULAN</label>
                                    <select name="range_end_bulan" class="form-select bg-white">
                                        <?php foreach($list_bulan as $k => $v): ?>
                                            <option value="<?= $k ?>" <?= ($k == $range_end_bulan) ? 'selected' : '' ?>><?= $v ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">TAHUN</label>
                                    <select name="range_end_tahun" class="form-select bg-white">
                                        <?php for($t = date('Y'); $t >= date('Y')-2; $t--): ?>
                                            <option value="<?= $t ?>" <?= ($t == $range_end_tahun) ? 'selected' : '' ?>><?= $t ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                                        <i class="bi bi-funnel-fill me-2"></i>Filter Data
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </main>

    <!-- Modals for Scheduling -->
    <?php foreach(['IPLM', 'TKM'] as $j): $low = strtolower($j); ?>
    <div class="modal fade" id="modalJadwal<?= $j ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Pengaturan Jadwal <?= $j ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <div class="modal-body p-4">
                        <input type="hidden" name="aksi_status" value="save_schedule">
                        <input type="hidden" name="jenis" value="<?= $low ?>">
                        
                        <div class="alert alert-light border mb-4">
                            <div class="d-flex gap-3">
                                <i class="bi bi-info-circle-fill text-primary fs-4"></i>
                                <small class="text-muted">Fitur ini memungkinkan sistem membuka/tutup akses kuesioner secara otomatis berdasarkan tanggal yang ditentukan.</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">Mode Akses</label>
                            <select name="mode" class="form-select py-3 fw-bold" onchange="toggleDateInput('<?= $low ?>', this.value)">
                                <option value="manual" <?= ($settings[$low.'_mode']??'')=='manual'?'selected':'' ?>>Manual (Kontrol Penuh)</option>
                                <option value="auto" <?= ($settings[$low.'_mode']??'')=='auto'?'selected':'' ?>>Otomatis (Terjadwal)</option>
                            </select>
                        </div>
                        
                        <div id="date-inputs-<?= $low ?>" class="bg-light p-3 rounded-3 border" style="<?= ($settings[$low.'_mode']??'')=='manual'?'display:none':'' ?>">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Waktu Mulai</label>
                                <input type="datetime-local" name="start_date" class="form-control" value="<?= !empty($settings[$low.'_start']) ? date('Y-m-d\TH:i', strtotime($settings[$low.'_start'])) : '' ?>">
                            </div>
                            <div class="mb-0">
                                <label class="form-label small fw-bold">Waktu Selesai</label>
                                <input type="datetime-local" name="end_date" class="form-control" value="<?= !empty($settings[$low.'_end']) ? date('Y-m-d\TH:i', strtotime($settings[$low.'_end'])) : '' ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Simpan Perubahan</button>
                    </div>
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
        // Custom Fonts for Chart
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.color = '#64748b';
        
        const gradientIplm = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradientIplm.addColorStop(0, 'rgba(15, 82, 186, 0.2)'); 
        gradientIplm.addColorStop(1, 'rgba(15, 82, 186, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [
                    {
                        label: 'IPLM',
                        data: <?= json_encode($data_iplm_chart) ?>, 
                        borderWidth: 3, 
                        borderColor: '#0F52BA', 
                        backgroundColor: gradientIplm, 
                        pointBackgroundColor: '#0F52BA', 
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5, 
                        pointHoverRadius: 8,
                        pointStyle: 'circle',
                        tension: 0.4, 
                        fill: true,
                        order: 2
                    },
                    {
                        label: 'TKM',
                        data: <?= json_encode($data_tkm_chart) ?>, 
                        borderWidth: 3, 
                        borderColor: '#F4C430', 
                        backgroundColor: 'transparent', 
                        pointBackgroundColor: '#F4C430', 
                        pointBorderColor: '#fff', 
                        pointBorderWidth: 2, 
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointStyle: 'rectRot',
                        tension: 0.4, 
                        borderDash: [6, 4],
                        order: 1
                    }
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        ticks: { stepSize: 1, padding: 10 },
                        grid: { color: '#f1f5f9', drawBorder: false }
                    }, 
                    x: { 
                        grid: { display: false },
                        ticks: { padding: 10 }
                    } 
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { usePointStyle: true, boxWidth: 8, padding: 20, font: { weight: 600 } }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 13 },
                        bodyFont: { size: 13 },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true
                    }
                }
            }
        });
    </script>
    <script src="../assets/loader.js"></script>
</body>
</html>
