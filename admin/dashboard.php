<?php
// web-perpus-v1/admin/dashboard.php
session_start();
require '../config/database.php';

// --- 1. SETTING ZONA WAKTU ---
date_default_timezone_set('Asia/Makassar'); 

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
$tahun_chart = isset($_GET['tahun_chart']) ? $_GET['tahun_chart'] : date('Y');

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
// 4. LOGIKA KARTU STATISTIK (BULANAN)
// ==========================================
$bulan_pilih = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_pilih = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$list_bulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
$label_periode = $list_bulan[$bulan_pilih] . " " . $tahun_pilih;

try { 
    $stmt = $pdo->query("SELECT COUNT(*) FROM libraries"); 
    $total_perpus = $stmt->fetchColumn(); 
} catch (Exception $e) { $total_perpus = 0; }

try {
    $stmtIplm = $pdo->prepare("SELECT COUNT(*) FROM trans_header WHERE jenis_kuesioner = 'IPLM' AND periode_bulan = :bln AND periode_tahun = :thn");
    $stmtIplm->execute([':bln' => $bulan_pilih, ':thn' => $tahun_pilih]); 
    $total_iplm = $stmtIplm->fetchColumn();
} catch (Exception $e) { $total_iplm = 0; }

try {
    $stmtTkm = $pdo->prepare("SELECT COUNT(*) FROM trans_header WHERE jenis_kuesioner = 'TKM' AND periode_bulan = :bln AND periode_tahun = :thn");
    $stmtTkm->execute([':bln' => $bulan_pilih, ':thn' => $tahun_pilih]); 
    $total_tkm = $stmtTkm->fetchColumn();
} catch (Exception $e) { $total_tkm = 0; }

$belum_iplm = max(0, $total_perpus - $total_iplm);

// --- PERPUS TERAKTIF ---
$list_aktif = [];
try {
    $sqlAktif = "SELECT l.nama, COUNT(th.id) as jumlah 
                 FROM trans_header th
                 JOIN libraries l ON th.library_id = l.id
                 WHERE th.periode_bulan = :bln AND th.periode_tahun = :thn
                 GROUP BY l.nama
                 ORDER BY jumlah DESC
                 LIMIT 5";
    $stmtAktif = $pdo->prepare($sqlAktif);
    $stmtAktif->execute([':bln' => $bulan_pilih, ':thn' => $tahun_pilih]);
    $list_aktif = $stmtAktif->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - DISARPUS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="sidebar-header">DISARPUS</div>
        <div class="nav flex-column">
            <a href="dashboard.php" class="nav-link active"><i class="bi bi-grid-fill"></i> DASHBOARD</a>
            <a href="perpustakaan.php" class="nav-link"><i class="bi bi-building"></i> PERPUSTAKAAN</a>
            <a href="atur_pertanyaan.php" class="nav-link"><i class="bi bi-file-text"></i> KUISIONER</a>
            <a href="pengaduan.php" class="nav-link"><i class="bi bi-chat-left-text"></i> PENGADUAN</a>
            <div class="mt-5 pt-5 border-top">
                <a href="../index.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> KELUAR</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div><h1 class="h2 fw-bold m-0">Dashboard Admin</h1><p class="text-muted m-0">Statistik dan Pengaturan Akses</p></div>
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
            <div class="col-lg-8">
                <div class="card-clean">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="fw-bold mb-1">Statistik Kuisioner</h5>
                            <small class="text-muted"><?= number_format($total_responden_tahunan) ?> Total Responden (Tahun <?= $tahun_chart ?>)</small>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="GET" id="formChartYear">
                                <input type="hidden" name="bulan" value="<?= $bulan_pilih ?>">
                                <input type="hidden" name="tahun" value="<?= $tahun_pilih ?>">
                                
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
                    <div class="d-flex justify-content-end mt-3 border-top pt-3">
                        <button class="btn-export-text text-success bg-white border-0 fw-bold" data-bs-toggle="modal" data-bs-target="#modalExport">
                            <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> MENU EXPORT DATA
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-clean d-flex flex-column">
                    <h5 class="fw-bold text-center mb-1">Perpustakaan Teraktif</h5>
                    <p class="text-center text-muted small mb-4">Periode <?= $label_periode ?></p>
                    
                    <ul class="list-unstyled list-active flex-grow-1">
                        <?php if (empty($list_aktif)): ?>
                            <li class="text-center text-muted py-4 small">Belum ada data aktivitas.</li>
                        <?php else: ?>
                            <?php $rank = 1; foreach ($list_aktif as $aktif): ?>
                                <li>
                                    <span><?= $rank++ ?>. <?= htmlspecialchars($aktif['nama']) ?></span> 
                                    <span class="badge-count"><?= $aktif['jumlah'] ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="text-center mt-3"><a href="perpustakaan.php" class="btn btn-outline-dark btn-sm rounded-pill w-100">Lihat Semua Data</a></div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 border-start border-4 border-dark ps-3">
            <div>
                <h5 class="fw-bold m-0">Rincian Statistik Data</h5>
                <small class="text-muted">Periode: <?= $label_periode ?></small>
            </div>
            <button type="button" class="btn-filter" data-bs-toggle="modal" data-bs-target="#filterModal">
                <i class="bi bi-funnel"></i> Filter Periode
            </button>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card-clean bg-light border-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold m-0"><i class="bi bi-book-half me-2"></i>Data IPLM</h6>
                        </div>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="stat-box highlight text-start align-items-start px-4">
                                <span class="stat-label mb-2 text-white-50">Total Perpustakaan</span>
                                <span class="stat-number"><?= $total_perpus ?></span>
                                <small class="mt-2 text-white-50 fs-6">Unit Terdaftar</small>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="row g-3 h-100">
                                <div class="col-12">
                                    <div class="stat-box bg-white flex-row justify-content-between px-4">
                                        <div class="text-start"><span class="stat-number d-block"><?= $total_iplm ?></span><span class="stat-label">Sudah Mengisi</span></div>
                                        <div class="icon-box fs-1 text-success opacity-25"><i class="bi bi-check-circle-fill"></i></div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="stat-box bg-white flex-row justify-content-between px-4">
                                        <div class="text-start"><span class="stat-number d-block text-danger"><?= $belum_iplm ?></span><span class="stat-label">Belum Mengisi</span></div>
                                        <div class="icon-box fs-1 text-danger opacity-25"><i class="bi bi-exclamation-circle-fill"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card-clean bg-light border-0 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold m-0"><i class="bi bi-people-fill me-2"></i>Data TKM</h6>
                         </div>
                    <div class="stat-box bg-white h-75 d-flex flex-column justify-content-center">
                        <div class="mb-3"><span class="badge bg-dark rounded-pill px-3 py-2 mb-2">Total Partisipan</span></div>
                        <span class="display-3 fw-bold text-dark"><?= number_format($total_tkm) ?></span>
                        <span class="text-muted mt-2">Orang Responden</span>
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
                    <form action="" method="GET">
                        <input type="hidden" name="tahun_chart" value="<?= $tahun_chart ?>">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">BULAN</label>
                            <select name="bulan" class="form-select border-secondary">
                                <?php foreach($list_bulan as $key => $val): ?>
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

    <div class="modal fade" id="modalExport" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cloud-download me-2"></i> Export Data Excel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="export_data.php" method="GET" target="_blank">
                    <div class="modal-body p-4">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">1. Jenis Data</label>
                            <div class="d-flex gap-3">
                                <div class="form-check custom-radio">
                                    <input class="form-check-input" type="radio" name="jenis" id="exp_iplm" value="iplm" checked>
                                    <label class="form-check-label fw-bold" for="exp_iplm">Data IPLM</label>
                                </div>
                                <div class="form-check custom-radio">
                                    <input class="form-check-input" type="radio" name="jenis" id="exp_tkm" value="tkm">
                                    <label class="form-check-label fw-bold" for="exp_tkm">Data TKM</label>
                                </div>
                            </div>
                        </div>

                        <label class="form-label fw-bold small text-muted text-uppercase">2. Rentang Periode</label>
                        <div class="row g-2 align-items-center mb-2">
                            <div class="col-12 fw-bold small">DARI:</div>
                            <div class="col-7">
                                <select name="start_bulan" class="form-select">
                                    <?php foreach($list_bulan as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= ($k == '01') ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-5">
                                <select name="start_tahun" class="form-select">
                                    <?php for($t = date('Y'); $t >= date('Y')-2; $t--): ?>
                                        <option value="<?= $t ?>"><?= $t ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 align-items-center">
                            <div class="col-12 fw-bold small mt-2">SAMPAI:</div>
                            <div class="col-7">
                                <select name="end_bulan" class="form-select">
                                    <?php foreach($list_bulan as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= ($k == date('m')) ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-5">
                                <select name="end_tahun" class="form-select">
                                    <?php for($t = date('Y'); $t >= date('Y')-2; $t--): ?>
                                        <option value="<?= $t ?>"><?= $t ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success fw-bold px-4 rounded-pill">
                            <i class="bi bi-download me-1"></i> Download .XLS
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php foreach(['IPLM', 'TKM'] as $j): $low = strtolower($j); ?>
    <div class="modal fade" id="modalJadwal<?= $j ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0">
                <div class="modal-header bg-dark text-white"><h5 class="modal-title fw-bold">Pengaturan <?= $j ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <form method="POST">
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
</body>
</html>