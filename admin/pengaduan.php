<?php
// web-perpus-v1/admin/pengaduan.php
session_start();
require '../config/database.php';
require '../config/admin_auth.php';

// Pastikan kolom is_important dan is_done tersedia
try {
    $pdo->exec("ALTER TABLE pengaduan ADD COLUMN IF NOT EXISTS is_important BOOLEAN DEFAULT FALSE");
    $pdo->exec("ALTER TABLE pengaduan ADD COLUMN IF NOT EXISTS is_done BOOLEAN DEFAULT FALSE");
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
    <title>Data Pengaduan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/loader.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/admin-responsive.css">
    <style>
        /* CSS DISAMAKAN DENGAN DASHBOARD.PHP */
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; overflow-x: hidden; }
        
        .sidebar { min-height: 100vh; width: 260px; background-color: #ffffff; border-right: 1px solid #e0e0e0; position: fixed; top: 0; left: 0; padding: 40px 20px; z-index: 100; }
        .sidebar-header { font-weight: 800; font-size: 24px; margin-bottom: 50px; color: #000; text-align: center; letter-spacing: 1px; }
        
        .nav-link { color: #666; font-weight: 600; font-size: 15px; padding: 12px 20px; margin-bottom: 8px; border-radius: 8px; transition: all 0.3s; display: flex; align-items: center; gap: 10px; }
        .nav-link:hover, .nav-link.active { background-color: #000; color: #fff; }
        
        .main-content { margin-left: 260px; padding: 40px 50px; }
        
        /* STYLE KHUSUS KARTU PESAN */
        .card-msg { border: 1px solid #e0e0e0; border-radius: 16px; background: #fff; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); transition: transform 0.2s; }
        .card-msg:hover { transform: translateY(-3px); }
        .bg-pesan { background-color: #f8f9fa; border-radius: 12px; padding: 15px; border: 1px dashed #dee2e6; }
        .chart-wrap { background: #fff; border: 1px solid #e0e0e0; border-radius: 16px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
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
            <a href="atur_pertanyaan.php" class="nav-link"><i class="bi bi-file-text"></i> KUISIONER</a>
            <a href="pengaduan.php" class="nav-link active"><i class="bi bi-chat-left-text"></i> PENGADUAN</a>
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
                    <h2 class="fw-bold m-0 page-title">Layanan Pengaduan</h2>
                    <p class="text-muted m-0 page-subtitle">Daftar kritik, saran, dan laporan masuk.</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark rounded-pill px-3 py-2">Total Aduan: <?= number_format($total_aduan) ?></span>
                <button class="btn btn-dark btn-sm rounded-pill px-3"><?= ($list_bulan[$bulan_pilih] ?? $bulan_pilih) . ' ' . $tahun_pilih ?></button>
            </div>
        </div>

        <div class="card-msg mb-4">
            <form method="POST" class="row g-2 align-items-end">
                <input type="hidden" name="filter_pengaduan" value="1">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Bulan</label>
                    <select name="bulan" class="form-select">
                        <?php foreach ($list_bulan as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($k == $bulan_pilih) ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tahun</label>
                    <select name="tahun" class="form-select">
                        <?php for($t = date('Y'); $t >= date('Y')-3; $t--): ?>
                            <option value="<?= $t ?>" <?= ($t == $tahun_pilih) ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Filter Pesan</label>
                    <select name="penting" class="form-select">
                        <option value="" <?= ($filter_penting === '') ? 'selected' : '' ?>>Semua</option>
                        <option value="1" <?= ($filter_penting === '1') ? 'selected' : '' ?>>Penting / Bermanfaat</option>
                        <option value="0" <?= ($filter_penting === '0') ? 'selected' : '' ?>>Biasa</option>
                    </select>
                </div>
                <div class="col-md-4 d-grid">
                    <button type="submit" class="btn btn-dark fw-bold">Terapkan Filter</button>
                </div>
            </form>
        </div>

        <div class="chart-wrap mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h6 class="fw-bold mb-0">Grafik Aduan per Bulan</h6>
                    <small class="text-muted">Tahun <?= $tahun_pilih ?></small>
                </div>
            </div>
            <div style="height: 260px;">
                <canvas id="chartPengaduan"></canvas>
            </div>
        </div>

        <?php if(empty($data)): ?>
            <div class="alert alert-light border text-center py-5 rounded-4">
                <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
                <h5 class="fw-bold">Belum ada pesan masuk</h5>
                <p class="text-muted">Kotak masuk pengaduan masih kosong.</p>
            </div>
        <?php else: ?>
            <?php foreach($data as $row): ?>
            <div class="card-msg">
                <div class="mb-2">
                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($row['nama']) ?></h6>
                    <div class="text-muted small">
                        <i class="bi bi-envelope-fill me-1"></i> <?= htmlspecialchars($row['kontak']) ?>
                    </div>
                    <div class="text-muted small mt-1">
                        <i class="bi bi-clock me-1"></i> <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                    </div>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <?php if (!empty($row['is_done'])): ?>
                            <span class="badge bg-success">Sudah Dilakukan</span>
                        <?php endif; ?>
                        <?php if (!empty($row['is_important'])): ?>
                            <span class="badge bg-warning text-dark">Penting</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="bg-pesan mb-3">
                    <p class="m-0 text-secondary" style="white-space: pre-line;"><?= htmlspecialchars($row['pesan']) ?></p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="aksi" value="toggle_important">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button class="btn btn-sm btn-outline-warning fw-bold" type="submit">
                            <?= !empty($row['is_important']) ? 'Batalkan Penting' : 'Tandai Penting' ?>
                        </button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="aksi" value="toggle_done">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button class="btn btn-sm btn-outline-success fw-bold" type="submit">
                            <?= !empty($row['is_done']) ? 'Batalkan Selesai' : 'Tandai Selesai' ?>
                        </button>
                    </form>
                    <form method="POST" class="js-confirm" data-confirm-title="Hapus pesan?" data-confirm-text="Pesan ini akan dihapus permanen dan tidak bisa dikembalikan." data-confirm-button="Ya, hapus">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger fw-bold">
                            Hapus Pesan
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        const chartCtx = document.getElementById('chartPengaduan');
        const chartData = <?= json_encode(array_values($grafik_counts)) ?>;
        const chartImportant = <?= json_encode(array_values($grafik_important)) ?>;
        const chartDone = <?= json_encode(array_values($grafik_done)) ?>;
        new Chart(chartCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_values($list_bulan)) ?>,
                datasets: [{
                    label: 'Total Aduan',
                    data: chartData,
                    backgroundColor: 'rgba(0,0,0,0.75)',
                    borderRadius: 6
                },
                {
                    label: 'Penting',
                    data: chartImportant,
                    backgroundColor: 'rgba(255, 193, 7, 0.85)',
                    borderRadius: 6
                },
                {
                    label: 'Selesai',
                    data: chartDone,
                    backgroundColor: 'rgba(25, 135, 84, 0.85)',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                },
                plugins: {
                    legend: { display: true, position: 'bottom' }
                }
            }
        });

        bindConfirmForms();
    </script>
    <script src="../assets/loader.js"></script>
</body>
</html>
