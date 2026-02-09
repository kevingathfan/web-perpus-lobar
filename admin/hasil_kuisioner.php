<?php
// web-perpus-v1/admin/hasil_kuisioner.php
session_start();
require '../config/database.php';
require '../config/admin_auth.php';

date_default_timezone_set('Asia/Makassar');

$list_bulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus_header') {
    $headerId = (int)($_POST['header_id'] ?? 0);
    if ($headerId > 0) {
        $pdo->beginTransaction();
        try {
            $stmtDelDetail = $pdo->prepare("DELETE FROM trans_detail WHERE header_id = ?");
            $stmtDelDetail->execute([$headerId]);
            $stmtDelHeader = $pdo->prepare("DELETE FROM trans_header WHERE id = ?");
            $stmtDelHeader->execute([$headerId]);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
    header("Location: hasil_kuisioner.php");
    exit;
}

$filter_keys = ['jenis','start_bulan','start_tahun','end_bulan','end_tahun'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') !== 'hapus_header') {
    $session_filter = [];
    foreach ($filter_keys as $k) {
        if (isset($_POST[$k])) $session_filter[$k] = $_POST[$k];
    }
    $_SESSION['hasil_kuisioner_filter'] = $session_filter;
    header("Location: hasil_kuisioner.php");
    exit;
}

if (!empty($_GET)) {
    $session_filter = [];
    foreach ($filter_keys as $k) {
        if (isset($_GET[$k])) $session_filter[$k] = $_GET[$k];
    }
    if (!empty($session_filter)) {
        $_SESSION['hasil_kuisioner_filter'] = $session_filter;
        header("Location: hasil_kuisioner.php");
        exit;
    }
}

$filter = $_SESSION['hasil_kuisioner_filter'] ?? [];

$jenis = isset($filter['jenis']) ? strtolower($filter['jenis']) : 'iplm';
if (!in_array($jenis, ['iplm', 'tkm'])) $jenis = 'iplm';

$start_bln = isset($filter['start_bulan']) ? str_pad($filter['start_bulan'], 2, '0', STR_PAD_LEFT) : '01';
$start_thn = isset($filter['start_tahun']) ? (int)$filter['start_tahun'] : (int)date('Y');
$end_bln   = isset($filter['end_bulan']) ? str_pad($filter['end_bulan'], 2, '0', STR_PAD_LEFT) : date('m');
$end_thn   = isset($filter['end_tahun']) ? (int)$filter['end_tahun'] : (int)date('Y');

$start_key = sprintf('%04d-%02d', $start_thn, (int)$start_bln);
$end_key   = sprintf('%04d-%02d', $end_thn, (int)$end_bln);
if ($start_key > $end_key) {
    $tmp = $start_bln; $start_bln = $end_bln; $end_bln = $tmp;
    $tmp = $start_thn; $start_thn = $end_thn; $end_thn = $tmp;
    $start_key = sprintf('%04d-%02d', $start_thn, (int)$start_bln);
    $end_key   = sprintf('%04d-%02d', $end_thn, (int)$end_bln);
}

$jenis_upper = strtoupper($jenis);
$start_period = (int)($start_thn . $start_bln);
$end_period   = (int)($end_thn . $end_bln);

// Ambil pertanyaan
$stmtSoal = $pdo->prepare("SELECT id, teks_pertanyaan, kategori_bagian, tipe_input FROM master_pertanyaan WHERE jenis_kuesioner = ? ORDER BY kategori_bagian ASC, urutan ASC");
$stmtSoal->execute([$jenis_upper]);
$daftar_soal = $stmtSoal->fetchAll(PDO::FETCH_ASSOC);

// Ambil header responden
$sql = "SELECT h.id as header_id, h.periode_bulan, h.periode_tahun,
               l.nama as nama_perpus, l.jenis as jenis_perpus, l.kategori
        FROM trans_header h
        LEFT JOIN libraries l ON h.library_id = l.id
        WHERE h.jenis_kuesioner = :jenis
        AND (CAST(CONCAT(h.periode_tahun, h.periode_bulan) AS INTEGER) >= :start_p)
        AND (CAST(CONCAT(h.periode_tahun, h.periode_bulan) AS INTEGER) <= :end_p)
        ORDER BY h.id ASC";
$stmtData = $pdo->prepare($sql);
$stmtData->execute([
    ':jenis'   => $jenis_upper,
    ':start_p' => $start_period,
    ':end_p'   => $end_period
]);
$responden = $stmtData->fetchAll(PDO::FETCH_ASSOC);

// Ambil jawaban detail
$jawaban_map = [];
$list_header_ids = array_column($responden, 'header_id');
if (!empty($list_header_ids)) {
    $inQuery = implode(',', array_fill(0, count($list_header_ids), '?'));
    $stmtDetail = $pdo->prepare("SELECT header_id, pertanyaan_id, jawaban FROM trans_detail WHERE header_id IN ($inQuery)");
    $stmtDetail->execute($list_header_ids);
    while ($row = $stmtDetail->fetch(PDO::FETCH_ASSOC)) {
        $jawaban_map[$row['header_id']][$row['pertanyaan_id']] = $row['jawaban'];
    }
}

$likert_map = [
    '1' => 'Sangat Tidak Setuju',
    '2' => 'Tidak Setuju',
    '3' => 'Setuju',
    '4' => 'Sangat Setuju'
];

$periode_label = ($start_key === $end_key)
    ? ($list_bulan[$start_bln] ?? $start_bln) . " " . $start_thn
    : ($list_bulan[$start_bln] ?? $start_bln) . " " . $start_thn . " - " . ($list_bulan[$end_bln] ?? $end_bln) . " " . $end_thn;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Kuisioner - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/loader.css">
    <link rel="stylesheet" href="../assets/admin-responsive.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; overflow-x: hidden; }
        .sidebar { min-height: 100vh; width: 260px; background-color: #ffffff; border-right: 1px solid #e0e0e0; position: fixed; top: 0; left: 0; padding: 40px 20px; z-index: 100; }
        .sidebar-header { margin-bottom: 28px; display: flex; align-items: flex-start; justify-content: space-between; }
        .sidebar-brand { display: flex; flex-direction: column; align-items: center; gap: 8px; text-align: center; flex: 1; }
        .sidebar-title { font-weight: 800; font-size: 22px; color: #000; letter-spacing: 2px; line-height: 1.2; }
        .sidebar-logo { width: 64px; height: 64px; object-fit: contain; }
        .nav-link { color: #666; font-weight: 600; font-size: 15px; padding: 12px 20px; margin-bottom: 8px; border-radius: 8px; transition: all 0.3s; display: flex; align-items: center; gap: 10px; }
        .nav-link:hover, .nav-link.active { background-color: #000; color: #fff; }
        .main-content { margin-left: 260px; padding: 40px 50px; }
        .card-clean { background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 16px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
        .table thead th { white-space: nowrap; }
        .table td { vertical-align: middle; }
        .table-responsive { max-height: 70vh; overflow: auto; }
        .table-sm td, .table-sm th { font-size: 12px; }
        .cell-wrap { white-space: normal; word-break: break-word; min-width: 180px; max-width: 260px; }
        .cell-wide { min-width: 220px; }
        .sticky-head th { position: sticky; top: 0; z-index: 7; background: #f8f9fa; }
        .sticky-col { position: sticky; z-index: 6; background: #fff; }
        .sticky-col-name { left: 0; min-width: 240px; max-width: 320px; box-shadow: 8px 0 8px -8px rgba(0,0,0,0.2); }
        .sticky-right { position: sticky; right: 0; z-index: 8; background: #fff; box-shadow: -8px 0 8px -8px rgba(0,0,0,0.2); }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; background: #f1f3f5; color: #555; }
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
            <a href="perpustakaan.php" class="nav-link"><i class="bi bi-building"></i> PERPUSTAKAAN</a>
            <a href="hasil_kuisioner.php" class="nav-link active"><i class="bi bi-table"></i> HASIL KUISIONER</a>
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
                <div>
                    <h1 class="h2 fw-bold m-0 page-title">Hasil Kuisioner</h1>
                    <p class="text-muted m-0 page-subtitle">Tabel hasil IPLM & TKM berdasarkan rentang periode.</p>
                </div>
            </div>
            <div class="pill">Periode: <?= $periode_label ?></div>
        </div>

        <div class="card-clean mb-4">
            <form method="POST" class="row g-3 align-items-end">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="col-lg-3">
                    <label class="form-label fw-bold">Jenis Kuisioner</label>
                    <select name="jenis" class="form-select">
                        <option value="iplm" <?= $jenis === 'iplm' ? 'selected' : '' ?>>IPLM</option>
                        <option value="tkm" <?= $jenis === 'tkm' ? 'selected' : '' ?>>TKM</option>
                    </select>
                </div>
                <div class="col-lg-3">
                    <label class="form-label fw-bold">Mulai</label>
                    <div class="row g-2">
                        <div class="col-7">
                            <select name="start_bulan" class="form-select">
                                <?php foreach($list_bulan as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= ($k == $start_bln) ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-5">
                            <select name="start_tahun" class="form-select">
                                <?php for($t = date('Y'); $t >= date('Y')-2; $t--): ?>
                                    <option value="<?= $t ?>" <?= ($t == $start_thn) ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <label class="form-label fw-bold">Sampai</label>
                    <div class="row g-2">
                        <div class="col-7">
                            <select name="end_bulan" class="form-select">
                                <?php foreach($list_bulan as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= ($k == $end_bln) ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-5">
                            <select name="end_tahun" class="form-select">
                                <?php for($t = date('Y'); $t >= date('Y')-2; $t--): ?>
                                    <option value="<?= $t ?>" <?= ($t == $end_thn) ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 d-grid">
                    <button type="submit" class="btn btn-dark fw-bold">Terapkan Filter</button>
                </div>
            </form>
            <div class="d-flex justify-content-end mt-3">
                <form method="POST" action="export_data.php" target="_blank">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="jenis" value="<?= htmlspecialchars($jenis) ?>">
                    <input type="hidden" name="start_bulan" value="<?= htmlspecialchars($start_bln) ?>">
                    <input type="hidden" name="start_tahun" value="<?= htmlspecialchars($start_thn) ?>">
                    <input type="hidden" name="end_bulan" value="<?= htmlspecialchars($end_bln) ?>">
                    <input type="hidden" name="end_tahun" value="<?= htmlspecialchars($end_thn) ?>">
                    <button type="submit" class="btn btn-success fw-bold rounded-pill px-4">
                        <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Export Excel
                    </button>
                </form>
            </div>
        </div>

        <div class="card-clean">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle table-sm">
                    <thead class="table-light sticky-head">
                        <tr>
                            <th>No</th>
                            <th>Periode</th>
                            <?php if ($jenis === 'iplm'): ?>
                                <th class="sticky-col sticky-col-name cell-wide">Nama Perpustakaan</th>
                                <th>Kategori</th>
                                <th>Jenis</th>
                            <?php endif; ?>
                            <?php foreach ($daftar_soal as $s): ?>
                                <th class="cell-wrap"><?= htmlspecialchars($s['teks_pertanyaan']) ?></th>
                            <?php endforeach; ?>
                            <th class="sticky-right" style="min-width:70px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($responden)): ?>
                            <tr>
                                <td colspan="<?= ($jenis === 'iplm' ? 6 : 3) + count($daftar_soal) ?>" class="text-center text-muted py-4">
                                    Belum ada data pada periode ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($responden as $row): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['periode_bulan'] . '/' . $row['periode_tahun']) ?></td>
                                    <?php if ($jenis === 'iplm'): ?>
                                        <td class="sticky-col sticky-col-name cell-wide"><?= htmlspecialchars($row['nama_perpus'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['kategori'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['jenis_perpus'] ?? '-') ?></td>
                                    <?php endif; ?>
                                    <?php foreach ($daftar_soal as $s): ?>
                                        <?php
                                            $val = $jawaban_map[$row['header_id']][$s['id']] ?? '-';
                                            if ($s['tipe_input'] === 'likert' && isset($likert_map[$val])) $val = $likert_map[$val];
                                        ?>
                                        <td class="cell-wrap"><?= htmlspecialchars((string)$val) ?></td>
                                    <?php endforeach; ?>
                                    <td class="sticky-right text-center">
                                        <form method="POST" class="d-inline js-confirm" data-confirm-title="Hapus data?" data-confirm-text="Data kuisioner pada periode ini akan dihapus permanen." data-confirm-button="Ya, hapus">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="aksi" value="hapus_header">
                                            <input type="hidden" name="header_id" value="<?= (int)$row['header_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
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

        bindConfirmForms();
    </script>
    <script src="../assets/loader.js"></script>
</body>
</html>
