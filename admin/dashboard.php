<?php
// web-perpus-v1/admin/dashboard.php
session_start();
require '../config/database.php';

// --- 1. LOGIKA FILTER PERIODE (BARU) ---
// Ambil data dari URL, jika tidak ada pakai bulan/tahun saat ini
$bulan_pilih = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_pilih = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// Array Nama Bulan untuk Label
$list_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

$label_periode = $list_bulan[$bulan_pilih] . " " . $tahun_pilih;

// --- 2. QUERY DATABASE ---

// A. TOTAL PERPUSTAKAAN (TETAP - Tidak terpengaruh filter)
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM libraries");
    $total_perpus = $stmt->fetchColumn();
} catch (Exception $e) { $total_perpus = 0; }

// B. IPLM (BERUBAH - Terpengaruh Filter Bulan/Tahun)
try {
    // Menggunakan syntax PostgreSQL: EXTRACT(MONTH ...)
    $sqlIplm = "SELECT COUNT(*) FROM trans_iplm 
                WHERE EXTRACT(MONTH FROM created_at) = :bln 
                AND EXTRACT(YEAR FROM created_at) = :thn";
    $stmtIplm = $pdo->prepare($sqlIplm);
    $stmtIplm->execute([':bln' => $bulan_pilih, ':thn' => $tahun_pilih]);
    $total_iplm = $stmtIplm->fetchColumn();
} catch (Exception $e) { $total_iplm = 0; }

// C. TKM (BERUBAH - Terpengaruh Filter Bulan/Tahun)
try {
    $sqlTkm = "SELECT COUNT(*) FROM trans_tkm 
               WHERE EXTRACT(MONTH FROM created_at) = :bln 
               AND EXTRACT(YEAR FROM created_at) = :thn";
    $stmtTkm = $pdo->prepare($sqlTkm);
    $stmtTkm->execute([':bln' => $bulan_pilih, ':thn' => $tahun_pilih]);
    $total_tkm = $stmtTkm->fetchColumn();
} catch (Exception $e) { $total_tkm = 0; }

// Hitung "Belum Mengisi" secara dinamis berdasarkan filter
$belum_iplm = $total_perpus - $total_iplm;
if ($belum_iplm < 0) $belum_iplm = 0;

// --- 3. DATA CHART (TETAP - Statistik Tahunan/Global) ---
// Bagian ini TIDAK menggunakan filter bulan agar tren tahunan tetap terlihat
$chart_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$data_iplm_chart = [200, 220, 240, 300, 400, 350, 0, 0, 0, 0, 0, 0];
$data_tkm_chart  = [180, 250, 300, 350, 320, 410, 0, 0, 0, 0, 0, 0];
// Total responden di atas chart (misal data tahunan)
$total_responden_tahunan = array_sum($data_iplm_chart) + array_sum($data_tkm_chart); 
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

    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; overflow-x: hidden; }

        /* --- SIDEBAR --- */
        .sidebar {
            min-height: 100vh;
            width: 260px;
            background-color: #ffffff;
            border-right: 1px solid #e0e0e0;
            position: fixed;
            top: 0; left: 0;
            padding: 40px 20px;
            z-index: 100;
        }

        .sidebar-header {
            font-weight: 800; font-size: 24px; margin-bottom: 50px; color: #000;
            text-align: center; letter-spacing: 1px;
        }

        .nav-link {
            color: #666; font-weight: 600; font-size: 15px; padding: 12px 20px;
            margin-bottom: 8px; border-radius: 8px; transition: all 0.3s;
            display: flex; align-items: center; gap: 10px;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: #000; color: #fff;
        }

        /* --- MAIN CONTENT --- */
        .main-content { margin-left: 260px; padding: 40px 50px; }
        .page-header h1 { font-weight: 800; font-size: 32px; color: #000; margin: 0; }
        .page-header p { font-size: 16px; color: #666; margin-top: 5px; }

        /* --- CARDS & BOXES --- */
        .card-clean {
            background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 16px;
            padding: 25px; height: 100%; box-shadow: 0 5px 20px rgba(0,0,0,0.03);
            transition: transform 0.3s;
        }
        .card-clean:hover { transform: translateY(-3px); }

        /* Tombol Filter Keren */
        .btn-filter {
            background-color: #f1f3f5; color: #333; border: none; padding: 8px 16px;
            border-radius: 30px; font-size: 13px; font-weight: 600; transition: all 0.2s;
            display: inline-flex; align-items: center; gap: 6px; text-decoration: none; cursor: pointer;
        }
        .btn-filter:hover { background-color: #000; color: #fff; }

        /* Statistik Box */
        .stat-box {
            background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 12px;
            padding: 20px; text-align: center; height: 100%; display: flex;
            flex-direction: column; justify-content: center; align-items: center;
            transition: all 0.3s;
        }
        
        .stat-box.highlight {
            background-color: #000; color: #fff; border: 1px solid #000;
        }
        
        .stat-number { font-size: 32px; font-weight: 800; line-height: 1.2; }
        .stat-label { font-size: 14px; font-weight: 500; margin-top: 5px; opacity: 0.8; }
        
        /* List Perpus Aktif */
        .list-active li {
            padding: 12px 0; border-bottom: 1px dashed #e0e0e0; font-size: 14px;
            display: flex; justify-content: space-between;
        }
        .list-active li:last-child { border-bottom: none; }
        .badge-count { 
            background: #000; color: #fff; padding: 2px 8px; border-radius: 6px; font-size: 12px; 
        }

        .btn-export-text {
            font-size: 13px; font-weight: 600; text-decoration: none; color: #666;
            padding: 6px 12px; border-radius: 6px; transition: 0.2s;
        }
        .btn-export-text:hover { background: #eee; color: #000; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="sidebar-header">DISARPUS</div>
        <div class="nav flex-column">
            <a href="#" class="nav-link active"><i class="bi bi-grid-fill"></i> DASHBOARD</a>
            <a href="#" class="nav-link"><i class="bi bi-building"></i> PERPUSTAKAAN</a>
            <a href="#" class="nav-link"><i class="bi bi-file-text"></i> KUISIONER</a>
            <a href="#" class="nav-link"><i class="bi bi-chat-left-text"></i> PENGADUAN</a>
            
            <div class="mt-5 pt-5 border-top">
                <a href="../index.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> KELUAR</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        
        <div class="page-header mb-5 d-flex justify-content-between align-items-center">
            <div>
                <h1>Dashboard Admin</h1>
                <p>Ringkasan statistik literasi dan kegemaran membaca</p>
            </div>
            <div class="d-flex gap-2">
                 <button class="btn btn-dark btn-sm rounded-pill px-3"><?= date('d M Y') ?></button>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-lg-8">
                <div class="card-clean">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="fw-bold mb-1">Statistik Kuisioner</h5>
                            <small class="text-muted"><?= number_format($total_responden_tahunan) ?> Total Responden (Tahunan)</small>
                        </div>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm border-secondary fw-bold" style="width: auto;">
                                <option>Tahun 2026</option>
                                <option>Tahun 2025</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="height: 300px;">
                        <canvas id="myChart"></canvas>
                    </div>

                    <div class="d-flex justify-content-end mt-3 border-top pt-3">
                        <a href="export_data.php?jenis=iplm" class="btn-export-text text-primary me-2">
                            <i class="bi bi-file-earmark-excel-fill"></i> Unduh Data IPLM
                        </a>
                        <a href="export_data.php?jenis=tkm" class="btn-export-text text-danger">
                            <i class="bi bi-file-earmark-pdf-fill"></i> Unduh Data TKM
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-clean d-flex flex-column">
                    <h5 class="fw-bold text-center mb-1">Perpustakaan Teraktif</h5>
                    <p class="text-center text-muted small mb-4">Periode Januari 2026</p>

                    <ul class="list-unstyled list-active flex-grow-1">
                        <li><span>1. Perpus Desa Gerung</span> <span class="badge-count">300</span></li>
                        <li><span>2. Perpus SMAN 1 Lembar</span> <span class="badge-count">285</span></li>
                        <li><span>3. Perpus SDN 2 Kuripan</span> <span class="badge-count">250</span></li>
                        <li><span>4. Perpus Desa Narmada</span> <span class="badge-count">210</span></li>
                        <li><span>5. TBM Lingsar Membaca</span> <span class="badge-count">190</span></li>
                    </ul>

                    <div class="text-center mt-3">
                        <a href="#" class="btn btn-outline-dark btn-sm rounded-pill w-100">Lihat Semua Data</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 border-start border-4 border-dark ps-3">
            <h5 class="fw-bold m-0">Rincian Statistik Data</h5>
            <span class="badge bg-secondary rounded-pill">Periode: <?= $label_periode ?></span>
        </div>
        
        <div class="row g-4">
            
            <div class="col-lg-7">
                <div class="card-clean bg-light border-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold m-0"><i class="bi bi-book-half me-2"></i>Data IPLM</h6>
                        
                        <button type="button" class="btn-filter" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="bi bi-funnel"></i> Filter Bulan
                        </button>
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
                                        <div class="text-start">
                                            <span class="stat-number d-block"><?= $total_iplm ?></span>
                                            <span class="stat-label">Sudah Mengisi</span>
                                        </div>
                                        <div class="icon-box fs-1 text-success opacity-25"><i class="bi bi-check-circle-fill"></i></div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="stat-box bg-white flex-row justify-content-between px-4">
                                        <div class="text-start">
                                            <span class="stat-number d-block text-danger"><?= $belum_iplm ?></span>
                                            <span class="stat-label">Belum Mengisi</span>
                                        </div>
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
                        
                         <button type="button" class="btn-filter" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="bi bi-funnel"></i> Filter Responden
                        </button>
                    </div>

                    <div class="stat-box bg-white h-75 d-flex flex-column justify-content-center">
                        <div class="mb-3">
                            <span class="badge bg-dark rounded-pill px-3 py-2 mb-2">Total Partisipan</span>
                        </div>
                        <span class="display-3 fw-bold text-dark"><?= number_format($total_tkm) ?></span>
                        <span class="text-muted mt-2">Orang Responden</span>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content rounded-4 border-0 p-3">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Pilih Periode</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="GET">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">BULAN</label>
                            <select name="bulan" class="form-select border-secondary">
                                <?php foreach($list_bulan as $key => $val): ?>
                                    <option value="<?= $key ?>" <?= ($key == $bulan_pilih) ? 'selected' : '' ?>>
                                        <?= $val ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">TAHUN</label>
                            <select name="tahun" class="form-select border-secondary">
                                <?php 
                                $thn_sekarang = date('Y');
                                for($t = $thn_sekarang; $t >= $thn_sekarang - 2; $t--): ?>
                                    <option value="<?= $t ?>" <?= ($t == $tahun_pilih) ? 'selected' : '' ?>>
                                        <?= $t ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 rounded-pill fw-bold">Terapkan Filter</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const ctx = document.getElementById('myChart');
        
        // Gradient Warna Chart
        const gradientIplm = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradientIplm.addColorStop(0, 'rgba(0, 0, 0, 0.1)'); 
        gradientIplm.addColorStop(1, 'rgba(0, 0, 0, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [
                    {
                        label: 'IPLM',
                        data: <?= json_encode($data_iplm_chart) ?>,
                        borderWidth: 2,
                        borderColor: '#000000', 
                        backgroundColor: gradientIplm,
                        pointBackgroundColor: '#000',
                        pointRadius: 4,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'TKM',
                        data: <?= json_encode($data_tkm_chart) ?>,
                        borderWidth: 2,
                        borderColor: '#adb5bd',
                        backgroundColor: 'transparent',
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#adb5bd',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        tension: 0.4,
                        borderDash: [5, 5]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8, font: { family: 'Poppins' } } },
                    tooltip: { backgroundColor: '#000', titleFont: { family: 'Poppins' }, bodyFont: { family: 'Poppins' }, padding: 10, cornerRadius: 8 }
                },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#f0f0f0' }, ticks: { font: { family: 'Poppins' } } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Poppins' } } }
                }
            }
        });
    </script>
</body>
</html>