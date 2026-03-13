<?php
// web-perpus-v1/admin/pengaduan.php
session_start();
require '../config/database.php';
require '../config/admin_auth.php';

// Pastikan kolom is_important dan is_done tersedia
try {
    $pdo->exec("ALTER TABLE pengaduan ADD COLUMN IF NOT EXISTS is_important TINYINT(1) NOT NULL DEFAULT 0");
    $pdo->exec("ALTER TABLE pengaduan ADD COLUMN IF NOT EXISTS is_done TINYINT(1) NOT NULL DEFAULT 0");
} catch (Exception $e) {}

// Hapus Pengaduan
if (isset($_POST['aksi']) && $_POST['aksi'] == 'hapus') {
    $stmt = $pdo->prepare("DELETE FROM pengaduan WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    header("Location: pengaduan.php"); exit;
}
// Tandai penting / bermanfaat
if (isset($_POST['aksi']) && $_POST['aksi'] == 'toggle_important') {
    $stmt = $pdo->prepare("UPDATE pengaduan SET is_important = NOT COALESCE(is_important, FALSE) WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    header("Location: pengaduan.php"); exit;
}
// Tandai sudah dilakukan
if (isset($_POST['aksi']) && $_POST['aksi'] == 'toggle_done') {
    $stmt = $pdo->prepare("UPDATE pengaduan SET is_done = NOT COALESCE(is_done, FALSE) WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    header("Location: pengaduan.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_pengaduan'])) {
    $_SESSION['pengaduan_filter'] = [
        'bulan' => $_POST['bulan'] ?? date('m'),
        'tahun' => $_POST['tahun'] ?? date('Y'),
        'penting' => $_POST['penting'] ?? ''
    ];
    header("Location: pengaduan.php");
    exit;
}

if (!empty($_GET)) {
    $session_filter = [];
    if (isset($_GET['bulan'])) $session_filter['bulan'] = $_GET['bulan'];
    if (isset($_GET['tahun'])) $session_filter['tahun'] = $_GET['tahun'];
    if (isset($_GET['penting'])) $session_filter['penting'] = $_GET['penting'];
    if (!empty($session_filter)) {
        $_SESSION['pengaduan_filter'] = $session_filter;
        header("Location: pengaduan.php");
        exit;
    }
}

// Filter Periode (Bulanan)
$list_bulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
$pengaduan_filter = $_SESSION['pengaduan_filter'] ?? [];
$bulan_pilih = isset($pengaduan_filter['bulan']) ? str_pad($pengaduan_filter['bulan'], 2, '0', STR_PAD_LEFT) : date('m');
$tahun_pilih = isset($pengaduan_filter['tahun']) ? (int)$pengaduan_filter['tahun'] : (int)date('Y');
$filter_penting = $pengaduan_filter['penting'] ?? '';

// Data grafik (jumlah aduan per bulan pada tahun terpilih)
$grafik_counts = array_fill(1, 12, 0);
$grafik_important = array_fill(1, 12, 0);
$grafik_done = array_fill(1, 12, 0);
try {
    $stmtGrafik = $pdo->prepare("SELECT 
            EXTRACT(MONTH FROM created_at) AS bln, 
            COUNT(*) AS total,
            SUM(CASE WHEN COALESCE(is_important, FALSE) = TRUE THEN 1 ELSE 0 END) AS important_count,
            SUM(CASE WHEN COALESCE(is_done, FALSE) = TRUE THEN 1 ELSE 0 END) AS done_count
        FROM pengaduan
        WHERE EXTRACT(YEAR FROM created_at) = :thn
        GROUP BY EXTRACT(MONTH FROM created_at)
        ORDER BY bln ASC");
    $stmtGrafik->execute([':thn' => $tahun_pilih]);
    while ($row = $stmtGrafik->fetch(PDO::FETCH_ASSOC)) {
        $idx = (int)$row['bln'];
        $grafik_counts[$idx] = (int)$row['total'];
        $grafik_important[$idx] = (int)$row['important_count'];
        $grafik_done[$idx] = (int)$row['done_count'];
    }
} catch (Exception $e) {}

// Ambil Data (filter per bulan)
$where = "EXTRACT(MONTH FROM created_at) = :bln AND EXTRACT(YEAR FROM created_at) = :thn";
if ($filter_penting === '1') {
    $where .= " AND COALESCE(is_important, FALSE) = TRUE";
} elseif ($filter_penting === '0') {
    $where .= " AND COALESCE(is_important, FALSE) = FALSE";
}
$stmt = $pdo->prepare("SELECT * FROM pengaduan WHERE $where ORDER BY created_at DESC");
$stmt->execute([':bln' => (int)$bulan_pilih, ':thn' => $tahun_pilih]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_aduan = count($data);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan Pengaduan - Admin Royal GovTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/govtech.css">
    <link rel="stylesheet" href="../assets/admin-readability.css">
    <link rel="stylesheet" href="../assets/loader.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <a href="atur_pertanyaan.php" class="nav-link">
                <i class="bi bi-gear-wide-connected"></i>
                <span>Atur Pertanyaan</span>
            </a>
            <a href="pengaduan.php" class="nav-link active">
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
                    <h2 class="fw-bold mb-0 text-dark">Layanan Pengaduan</h2>
                    <p class="text-muted mb-0">Kelola kritik, saran, dan laporan dari masyarakat.</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="bg-white px-3 py-2 rounded-pill shadow-sm border d-flex align-items-center gap-2">
                    <i class="bi bi-calendar-check text-primary"></i>
                    <span class="fw-bold text-dark small"><?= ($list_bulan[$bulan_pilih] ?? $bulan_pilih) . ' ' . $tahun_pilih ?></span>
                </div>
                <div class="bg-primary text-white px-3 py-2 rounded-pill shadow-sm d-flex align-items-center gap-2">
                    <i class="bi bi-inbox-fill"></i>
                    <span class="fw-bold small"><?= number_format($total_aduan) ?> Aduan</span>
                </div>
            </div>
        </div>

        <!-- Filter & Chart Row -->
        <div class="row g-4 mb-4">
            <!-- Filter -->
            <div class="col-lg-4">
                <div class="card-clean h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <div class="bg-primary-subtle d-flex align-items-center justify-content-center rounded-circle text-primary" style="width: 40px; height: 40px; flex-shrink: 0;">
                                <i class="bi bi-funnel-fill"></i>
                            </div>
                            <h6 class="fw-bold mb-0">Filter Data</h6>
                        </div>
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="filter_pengaduan" value="1">
                            
                            <div class="col-12">
                                <label class="form-label small text-muted text-uppercase fw-bold">Bulan</label>
                                <select name="bulan" class="form-select bg-light border-0">
                                    <?php foreach ($list_bulan as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= ($k == $bulan_pilih) ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-muted text-uppercase fw-bold">Tahun</label>
                                <select name="tahun" class="form-select bg-light border-0">
                                    <?php for($t = date('Y'); $t >= date('Y')-3; $t--): ?>
                                        <option value="<?= $t ?>" <?= ($t == $tahun_pilih) ? 'selected' : '' ?>><?= $t ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-muted text-uppercase fw-bold">Status Pesan</label>
                                <select name="penting" class="form-select bg-light border-0">
                                    <option value="" <?= ($filter_penting === '') ? 'selected' : '' ?>>Semua Pesan</option>
                                    <option value="1" <?= ($filter_penting === '1') ? 'selected' : '' ?>>Ditandai Penting</option>
                                    <option value="0" <?= ($filter_penting === '0') ? 'selected' : '' ?>>Biasa</option>
                                </select>
                            </div>
                            <div class="col-12 pt-2">
                                <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm">
                                    <i class="bi bi-search me-2"></i>Terapkan Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Chart -->
            <div class="col-lg-8">
                <div class="card-clean h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-warning-subtle d-flex align-items-center justify-content-center rounded-circle text-warning-emphasis" style="width: 40px; height: 40px; flex-shrink: 0;">
                                    <i class="bi bi-bar-chart-fill"></i>
                                </div>
                                <h6 class="fw-bold mb-0">Statistik Aduan</h6>
                            </div>
                            <span class="badge bg-light text-muted border">Tahun <?= $tahun_pilih ?></span>
                        </div>
                        <div style="height: 300px;">
                            <canvas id="chartPengaduan"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message List -->
        <?php if(empty($data)): ?>
            <div class="card-clean text-center py-5">
                <div class="card-body">
                    <div class="bg-light rounded-circle d-inline-flex p-4 mb-3">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Belum ada pesan masuk</h5>
                    <p class="text-muted mb-0">Tidak ada pengaduan untuk periode yang dipilih.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach($data as $row): ?>
                <div class="col-12">
                    <div class="card-clean hover-card">
                        <div class="card-body p-4">
                            <div class="d-flex flex-column flex-md-row gap-4">
                                <!-- User Info -->
                                <div class="d-flex flex-column gap-1" style="min-width: 200px;">
                                    <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($row['nama']) ?></h6>
                                    <div class="text-muted small d-flex align-items-center gap-2">
                                        <i class="bi bi-envelope text-primary"></i> <?= htmlspecialchars($row['kontak']) ?>
                                    </div>
                                    <div class="text-muted small d-flex align-items-center gap-2">
                                        <i class="bi bi-calendar3"></i> <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                                    </div>
                                    <div class="mt-2 d-flex flex-wrap gap-1">
                                        <?php if (!empty($row['is_done'])): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>
                                        <?php endif; ?>
                                        <?php if (!empty($row['is_important'])): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill"><i class="bi bi-star-fill me-1"></i>Penting</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Message Content -->
                                <div class="flex-grow-1 border-start-md ps-md-4">
                                    <div class="bg-light p-3 rounded-3 border mb-3">
                                        <p class="m-0 text-dark" style="white-space: pre-line; line-height: 1.6;"><?= htmlspecialchars($row['pesan']) ?></p>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="d-flex flex-wrap gap-2">
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="aksi" value="toggle_important">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button class="btn btn-sm btn-outline-warning rounded-pill fw-bold px-3" type="submit">
                                                <i class="bi bi-star<?= !empty($row['is_important']) ? '-fill' : '' ?> me-1"></i>
                                                <?= !empty($row['is_important']) ? 'Lepas Penting' : 'Tandai Penting' ?>
                                            </button>
                                        </form>
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="aksi" value="toggle_done">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button class="btn btn-sm btn-outline-success rounded-pill fw-bold px-3" type="submit">
                                                <i class="bi bi-check-lg me-1"></i>
                                                <?= !empty($row['is_done']) ? 'Batal Selesai' : 'Tandai Selesai' ?>
                                            </button>
                                        </form>
                                        <form method="POST" class="js-confirm ms-auto" data-confirm-title="Hapus pesan?" data-confirm-text="Pesan ini akan dihapus permanen dan tidak bisa dikembalikan." data-confirm-button="Ya, hapus">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button class="btn btn-sm btn-white text-danger border hover-bg-light rounded-pill fw-bold px-3">
                                                <i class="bi bi-trash me-1"></i>Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

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
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
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

        const chartCtx = document.getElementById('chartPengaduan');
        const chartData = <?= json_encode(array_values($grafik_counts)) ?>;
        const chartImportant = <?= json_encode(array_values($grafik_important)) ?>;
        const chartDone = <?= json_encode(array_values($grafik_done)) ?>;
        
        // Custom Font
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.color = '#6c757d';

        new Chart(chartCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_values($list_bulan)) ?>,
                datasets: [{
                    label: 'Total Aduan',
                    data: chartData,
                    backgroundColor: '#0F52BA',
                    borderRadius: 4,
                    barPercentage: 0.6
                },
                {
                    label: 'Penting',
                    data: chartImportant,
                    backgroundColor: '#F4C430',
                    borderRadius: 4,
                    barPercentage: 0.6
                },
                {
                    label: 'Selesai',
                    data: chartDone,
                    backgroundColor: '#198754',
                    borderRadius: 4,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: { usePointStyle: true, boxWidth: 8, padding: 20, font: { weight: 600 } }
                    },
                    tooltip: {
                        backgroundColor: '#1e1e2d',
                        titleFont: { size: 13 },
                        bodyFont: { size: 13 },
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#f1f1f1', drawBorder: false },
                        ticks: { stepSize: 1, font: { size: 11 } }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
            }
        });

        bindConfirmForms();
    </script>
    <script src="../assets/loader.js"></script>
</body>
</html>
