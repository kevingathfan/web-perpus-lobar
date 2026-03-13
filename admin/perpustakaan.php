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

        // START TRY BLOCK
        try {
            // Build Query WHERE
            $whereParts = [];
            $params = [];

            if ($search) {
                $searchLower = strtolower($search);
                $whereParts[] = "(LOWER(nama) LIKE :search OR LOWER(jenis) LIKE :search)";
                $params[':search'] = "%$searchLower%";
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

            // Main Query
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
                echo '<tr><td colspan="7" class="text-center py-5 text-muted">Tidak ada data ditemukan.</td></tr>';
            } else {
                $no = $offset + 1;
                foreach ($libraries as $lib) {
                    $kat = $lib['kategori'] ?? 'Umum';
                    $bg = ($kat == 'Sekolah') ? 'bg-primary' : (($kat == 'Khusus') ? 'bg-warning text-dark' : 'bg-success');
                    
                    $badgeIplm = ($lib['status_iplm'] > 0) 
                        ? '<span class="badge bg-success rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Sudah Mengisi</span>' 
                        : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill"><i class="bi bi-x-circle me-1"></i>Belum</span>';
                    
                    echo '<tr>';
                    echo '<td class="text-center"><input type="checkbox" class="form-check-input check-item" value="' . $lib['id'] . '" onchange="handleCheckboxChange(this)"></td>';
                    echo '<td class="text-center fw-bold">' . $no++ . '</td>';
                    echo '<td class="fw-bold text-uppercase">' . htmlspecialchars($lib['nama']) . '</td>';
                    echo '<td><span class="badge ' . $bg . ' badge-kategori">' . htmlspecialchars($kat) . '</span></td>';
                    echo '<td>' . htmlspecialchars($lib['jenis']) . '</td>';
                    
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
                                <button type="button" class="btn btn-danger" onclick="deleteSingle(' . $lib['id'] . ')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>';
                    echo '</tr>';
                }
                
                // PAGINATION
                if ($total_pages > 1) {
                    echo '<tr id="pagination-row"><td colspan="7" class="p-0 border-0"><nav class="mt-4"><ul class="pagination pagination-sm justify-content-center">';
                    
                    $prevDisabled = ($page <= 1) ? 'disabled' : '';
                    $prevPage = max(1, $page - 1);
                    echo '<li class="page-item ' . $prevDisabled . '"><a class="page-link" href="#" onclick="loadTable(' . $prevPage . '); return false;">&laquo;</a></li>';

                    $range = 2;
                    $start = max(1, $page - $range);
                    $end = min($total_pages, $page + $range);

                    if ($start > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';

                    for ($i = $start; $i <= $end; $i++) {
                        $isActive = ($i == $page) ? 'active' : '';
                        $bgStyle = ($i == $page) ? 'bg-dark border-dark' : 'text-dark';
                        if ($i == $page) {
                            echo '<li class="page-item active"><span class="page-link bg-dark border-dark">' . $i . '</span></li>';
                        } else {
                            echo '<li class="page-item"><a class="page-link text-dark" href="#" onclick="loadTable(' . $i . '); return false;">' . $i . '</a></li>';
                        }
                    }

                    if ($end < $total_pages) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';

                    $nextDisabled = ($page >= $total_pages) ? 'disabled' : '';
                    $nextPage = min($total_pages, $page + 1);
                    echo '<li class="page-item ' . $nextDisabled . '"><a class="page-link" href="#" onclick="loadTable(' . $nextPage . '); return false;">&raquo;</a></li>';

                    echo '</ul><div class="text-center mt-2 small text-muted">Halaman ' . $page . ' dari ' . $total_pages . '</div></nav></td></tr>';
                }
            }
        } catch (Exception $e) {
            echo '<tr><td colspan="7" class="text-center text-danger py-5">Terjadi kesalahan server: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
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
                    
                    $namaInput = trim($_POST['nama']);
                    // Normalize: remove extra spaces (multiple spaces -> single space)
                    $namaInput = preg_replace('/\s+/', ' ', $namaInput);
                    
                    // Cek duplikat case-insensitive dengan normalisasi spasi
                    $stmtCek = $pdo->prepare("SELECT id FROM libraries WHERE LOWER(TRIM(REPLACE(REPLACE(nama, '  ', ' '), '  ', ' '))) = LOWER(?)");
                    $stmtCek->execute([$namaInput]);
                    if ($stmtCek->rowCount() > 0) throw new Exception("Nama perpustakaan sudah ada (duplikat).");

                    $stmt = $pdo->prepare("INSERT INTO libraries (nama, kategori, jenis) VALUES (?, ?, ?)");
                    $stmt->execute([strtoupper($namaInput), $_POST['kategori'], $_POST['subjenis']]);
                    if(isset($_POST['ajax']) && $_POST['ajax'] == 1) { echo json_encode(['status'=>'success', 'message'=>'Perpustakaan berhasil ditambahkan!']); exit; }
                    $pesan = "Perpustakaan berhasil ditambahkan!";
                } 
                elseif ($_POST['aksi'] == 'edit') {
                    if (!isKategoriValid($_POST['kategori'], $_POST['subjenis'], $validasiMap)) throw new Exception("Kategori tidak valid.");
                    $sql = "UPDATE libraries SET nama=?, kategori=?, jenis=? WHERE id=?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([strtoupper($_POST['nama']), $_POST['kategori'], $_POST['subjenis'], $_POST['id']]);
                    if(isset($_POST['ajax']) && $_POST['ajax'] == 1) { echo json_encode(['status'=>'success', 'message'=>'Data diperbarui!']); exit; }
                    $pesan = "Data diperbarui!";
                } 
                elseif ($_POST['aksi'] == 'hapus') {
                    $stmt = $pdo->prepare("DELETE FROM libraries WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    if(isset($_POST['ajax']) && $_POST['ajax'] == 1) { echo json_encode(['status'=>'success']); exit; }
                    $pesan = "Perpustakaan dihapus.";
                } 
                elseif ($_POST['aksi'] == 'hapus_bulk') {
                    $ids = $_POST['ids'] ?? [];
                    if (!empty($ids)) {
                        $inQuery = implode(',', array_fill(0, count($ids), '?'));
                        $stmt = $pdo->prepare("DELETE FROM libraries WHERE id IN ($inQuery)");
                        $stmt->execute($ids);
                        echo json_encode(['status'=>'success', 'count'=>count($ids)]);
                    } else {
                        echo json_encode(['status'=>'error', 'message'=>'Tidak ada data dipilih']);
                    }
                    exit;
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
                    if(isset($_POST['ajax']) && $_POST['ajax'] == 1) { echo json_encode(['status'=>'success', 'message'=>$pesan]); exit; }
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
                            throw new Exception("Format CSV tidak valid. Harus memiliki minimal 3 kolom: Nama, Jenis, Subjenis.");
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

                                    $cek = $pdo->prepare("SELECT id FROM libraries WHERE LOWER(nama) = LOWER(?)");
                                    $cek->execute([$nama]);
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
                    $kategoriInput = trim($_POST['kategori']);
                    $subInput = trim($_POST['sub_kategori']);

                    // Cek duplikat case-insensitive
                    $stmtCek = $pdo->prepare("SELECT id FROM master_kategori WHERE LOWER(kategori) = LOWER(?) AND LOWER(sub_kategori) = LOWER(?)");
                    $stmtCek->execute([$kategoriInput, $subInput]);
                    if ($stmtCek->rowCount() > 0) throw new Exception("Kategori/Sub Jenis tersebut sudah ada.");

                    $stmt = $pdo->prepare("INSERT INTO master_kategori (kategori, sub_kategori) VALUES (?, ?)");
                    
                    // Kategori: Title Case
                    $kategoriSave = ucwords(strtolower($kategoriInput));
                    
                    // Sub Jenis: "Perpustakaan" Capitalized, sisanya sesuai input user
                    $words = explode(' ', $subInput);
                    foreach ($words as &$w) {
                        if (strtolower($w) === 'perpustakaan') {
                            $w = 'Perpustakaan';
                        }
                    }
                    $subSave = implode(' ', $words);
                    
                    $stmt->execute([$kategoriSave, $subSave]);
                    $pesan = "Kategori ditambahkan!";
                } elseif ($_POST['aksi'] == 'hapus') {
                    $stmt = $pdo->prepare("DELETE FROM master_kategori WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $pesan = "Kategori dihapus.";
                }

                $_SESSION['flash_message'] = $pesan;
                $_SESSION['flash_type'] = $tipe_pesan;
                header("Location: perpustakaan.php?tab=category"); 
                exit;
            }
            $_SESSION['flash_message'] = $pesan;
            $_SESSION['flash_type'] = $tipe_pesan;
            header("Location: perpustakaan.php"); exit;
        } catch (Exception $e) {
            if(isset($_POST['ajax']) && $_POST['ajax'] == 1) { echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]); exit; }
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
        <title>Manajemen Perpustakaan - Royal GovTech</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
        <link rel="stylesheet" href="../assets/govtech.css">
        <link rel="stylesheet" href="../assets/admin-readability.css">
        <link rel="stylesheet" href="../assets/loader.css">
    </head>
    <body>
        <?php include __DIR__ . '/../config/loader.php'; ?>
        <div class="sidebar-backdrop" onclick="toggleSidebar(false)"></div>

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
                <a href="perpustakaan.php" class="nav-link active">
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

        <main class="main-content">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-white shadow-sm d-lg-none" onclick="toggleSidebar(true)">
                        <i class="bi bi-list"></i>
                    </button>
                    <div>
                        <h2 class="fw-bold mb-0 text-dark">Manajemen Perpustakaan</h2>
                        <p class="text-muted mb-0">Kelola data unit perpustakaan dan cek status IPLM.</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-white shadow-sm fw-bold rounded-pill" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="bi bi-funnel me-2 text-primary"></i> Filter Periode
                    </button>
                </div>
            </div>

            <?php if($pesan): ?>
                <div class="alert alert-<?= $tipe_pesan ?> alert-dismissible fade show shadow-sm border-0" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle-fill fs-5"></i>
                        <div><?= $pesan ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats Overview -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card-clean p-4 bg-primary-subtle border-primary border-opacity-25">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-bar-chart-fill text-primary"></i>
                                <h6 class="fw-bold m-0 text-primary-emphasis">Statistik IPLM (<?= $label_periode ?>)</h6>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="bg-white p-3 rounded-3 shadow-sm h-100 border">
                                    <div class="small text-muted fw-bold mb-1">TOTAL UNIT</div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h2 class="fw-bold mb-0 text-dark"><?= number_format($total_perpus) ?></h2>
                                        <span class="badge bg-light text-dark border">Perpustakaan</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="row g-3 h-100">
                                    <div class="col-md-6">
                                        <div class="bg-success text-white p-3 rounded-3 shadow-sm h-100 d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="small fw-bold opacity-75">SUDAH MENGISI</div>
                                                <h2 class="fw-bold mb-0"><?= number_format($total_iplm) ?></h2>
                                            </div>
                                            <i class="bi bi-check-circle-fill fs-1 opacity-25"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="bg-white p-3 rounded-3 shadow-sm h-100 border border-danger d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="small fw-bold text-danger">BELUM MENGISI</div>
                                                <h2 class="fw-bold mb-0 text-danger"><?= number_format($belum_iplm) ?></h2>
                                            </div>
                                            <i class="bi bi-exclamation-circle-fill fs-1 text-danger opacity-25"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-clean p-0 overflow-hidden">
                <div class="p-4 border-bottom bg-light">
                    <ul class="nav nav-pills card-header-pills" id="myTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold px-4" id="lib-tab" data-bs-toggle="tab" data-bs-target="#tab-lib" type="button">
                                <i class="bi bi-list-ul me-2"></i>Data Perpustakaan
                            </button>
                        </li>
                        <li class="nav-item ms-2">
                            <button class="nav-link fw-bold px-4" id="kat-tab" data-bs-toggle="tab" data-bs-target="#tab-kat" type="button">
                                <i class="bi bi-tags me-2"></i>Atur Kategori
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content p-4">
                    
                    <div class="tab-pane fade show active" id="tab-lib">
                        
                        <div class="d-flex flex-wrap gap-2 mb-4 bg-light p-3 rounded-3 border align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-calendar-check text-muted"></i>
                                <span class="fw-bold small text-muted text-uppercase">Filter Status Periode:</span>
                            </div>
                            <div class="d-flex gap-2">
                                <select id="filterBulan" class="form-select form-select-sm border-secondary fw-bold" style="width: 120px;">
                                    <?php foreach($bulanList as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= ($k == $bulan_pilih) ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select id="filterTahun" class="form-select form-select-sm border-secondary fw-bold" style="width: 100px;">
                                    <?php for($t=$tahunIni; $t>=$tahunIni-2; $t--): ?>
                                        <option value="<?= $t ?>" <?= ($t == $tahun_pilih) ? 'selected' : '' ?>><?= $t ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-muted mb-1">PENCARIAN</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                                    <input type="text" id="liveSearch" class="form-control border-start-0 ps-0" placeholder="Ketik nama perpustakaan...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">JENIS / KATEGORI</label>
                                <select id="filterKategori" class="form-select">
                                    <option value="">Semua Kategori</option>
                                    <?php foreach(array_unique(array_column($rawKat, 'kategori')) as $k): ?>
                                        <option value="<?= $k ?>"><?= $k ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted mb-1">SUB JENIS</label>
                                <select id="filterSubjenis" class="form-select">
                                    <option value="">Semua Sub Jenis</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">STATUS PENGISIAN</label>
                                <select id="filterStatus" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="sudah">Sudah Mengisi</option>
                                    <option value="belum">Belum Mengisi</option>
                                </select>
                            </div>
                            <div class="col-md-9 text-end align-self-end">
                                <button class="btn btn-light fw-bold rounded-pill px-3 border" type="button" onclick="resetFilters()">
                                    <i class="bi bi-arrow-counterclockwise me-2"></i> Reset
                                </button>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="small text-muted">
                                <i class="bi bi-check2-square me-1"></i> Pilih data untuk aksi massal
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-danger fw-bold rounded-pill px-3 d-none shadow-sm" id="btnHapusBulk" onclick="deleteBulk()">
                                    <i class="bi bi-trash me-1"></i> Hapus (<span id="countSelected">0</span>)
                                </button>
                                <button class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm" onclick="openModalLib()">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Data
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive rounded-3 border">
                            <table class="table table-hover table-govtech mb-0 align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="3%" class="text-center py-3">
                                            <input type="checkbox" id="checkAll" class="form-check-input" onclick="toggleSelectAll()">
                                        </th>
                                        <th width="5%" class="text-center">No</th>
                                        <th width="35%">Nama Perpustakaan</th>
                                        <th width="15%">Jenis</th>
                                        <th width="20%">Sub Jenis</th>
                                        <th width="15%" class="text-center">Status IPLM</th>
                                        <th width="7%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody" class="bg-white">
                                    <tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-kat">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="bg-light p-4 rounded-4 border h-100">
                                    <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                        <i class="bi bi-plus-circle-fill text-primary"></i> Tambah Jenis Baru
                                    </h6>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="form_type" value="category">
                                        <input type="hidden" name="aksi" value="tambah">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-muted">JENIS UTAMA</label>
                                            <input type="text" list="listKategori" name="kategori" class="form-control" required placeholder="Contoh: Sekolah">
                                            <datalist id="listKategori"><option value="Sekolah"><option value="Umum"><option value="Khusus"></datalist>
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label small fw-bold text-muted">SUB JENIS</label>
                                            <input type="text" name="sub_kategori" class="form-control" required placeholder="Contoh: Perpustakaan SD">
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm">Simpan Kategori</button>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="table-responsive rounded-3 border" style="max-height: 500px;">
                                    <table class="table table-hover table-govtech mb-0 align-middle">
                                        <thead class="sticky-top bg-light">
                                            <tr>
                                                <th class="py-3 ps-4">Jenis Utama</th>
                                                <th class="py-3">Sub Kategori</th>
                                                <th class="text-center py-3">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($rawKat as $row): ?>
                                            <tr>
                                                <td class="ps-4"><span class="badge bg-secondary rounded-pill px-3"><?= htmlspecialchars($row['kategori']) ?></span></td>
                                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['sub_kategori']) ?></td>
                                                <td class="text-center">
                                                    <form method="POST" class="js-confirm" data-confirm-title="Hapus jenis?" data-confirm-text="Jenis ini akan dihapus permanen." data-confirm-button="Ya, hapus" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="form_type" value="category">
                                                        <input type="hidden" name="aksi" value="hapus">
                                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-circle" data-bs-toggle="tooltip" title="Hapus"><i class="bi bi-trash-fill"></i></button>
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

        <!-- Filter Modal -->
        <div class="modal fade" id="filterModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content rounded-4 border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bold mb-0">Filter Periode</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <form action="" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="filter_periode" value="1">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">BULAN</label>
                                <select name="bulan" class="form-select border-secondary fw-bold">
                                    <?php foreach($bulanList as $key => $val): ?>
                                        <option value="<?= $key ?>" <?= ($key == $bulan_pilih) ? 'selected' : '' ?>><?= $val ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted">TAHUN</label>
                                <select name="tahun" class="form-select border-secondary fw-bold">
                                    <?php for($t = date('Y'); $t >= date('Y') - 2; $t--): ?>
                                        <option value="<?= $t ?>" <?= ($t == $tahun_pilih) ? 'selected' : '' ?>><?= $t ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm">Terapkan Filter</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Library (Add/Edit) -->
        <div class="modal fade" id="modalLib" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="modalTitle">Tambah Perpustakaan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <ul class="nav nav-pills nav-fill mb-4 bg-light p-1 rounded-pill" id="modalTab" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active rounded-pill fw-bold small" id="manual-tab" data-bs-toggle="pill" data-bs-target="#tab-manual">Input Manual</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link rounded-pill fw-bold small" id="csv-tab" data-bs-toggle="pill" data-bs-target="#tab-csv">Import CSV</button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- Manual Input -->
                            <div class="tab-pane fade show active" id="tab-manual">
                                <form method="POST" id="formLibrary">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="form_type" value="library">
                                    <input type="hidden" name="aksi" id="formAksi" value="tambah">
                                    <input type="hidden" name="id" id="formId">
                                    
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-muted">NAMA PERPUSTAKAAN</label>
                                        <input type="text" name="nama" id="inputNama" class="form-control" required placeholder="Contoh: Perpustakaan SMAN 1 Gerung">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label small fw-bold text-muted">JENIS</label>
                                            <select name="kategori" id="selectKategori" class="form-select" onchange="updateSubJenisModal()" required>
                                                <option value="">-- Pilih --</option>
                                                <?php foreach(array_unique(array_column($rawKat, 'kategori')) as $k): ?>
                                                    <option value="<?= $k ?>"><?= $k ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label small fw-bold text-muted">SUB JENIS</label>
                                            <select name="subjenis" id="selectSubJenis" class="form-select" required>
                                                <option value="">-- Pilih Kategori --</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm">Simpan Data</button>
                                </form>
                            </div>
                            
                            <!-- CSV Import -->
                            <div class="tab-pane fade" id="tab-csv">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="form_type" value="library">
                                    <input type="hidden" name="aksi" value="import_csv">
                                    
                                    <div class="alert alert-info border-0 rounded-3 small mb-3">
                                        <i class="bi bi-info-circle-fill me-2"></i> Format: <b>Nama, Jenis, Subjenis</b>. Nama akan otomatis dikapitalisasi.
                                    </div>
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label small fw-bold text-muted mb-0">FILE CSV</label>
                                            <a href="#" onclick="downloadTemplateLib(event)" class="text-decoration-none badge bg-success-subtle text-success border border-success"><i class="bi bi-download me-1"></i> Download Template</a>
                                        </div>
                                        <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100 fw-bold rounded-pill shadow-sm">Import CSV</button>
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

        <!-- Modal Status IPLM -->
        <div class="modal fade" id="modalStatusIplm" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Status IPLM</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="text-center mb-4">
                            <div class="avatar-circle bg-primary-subtle text-primary mx-auto mb-3" style="width: 60px; height: 60px; display:flex; align-items:center; justify-content:center; border-radius:50%;">
                                <i class="bi bi-file-earmark-check fs-2"></i>
                            </div>
                            <h6 class="fw-bold mb-1" id="statusLibName">Nama Perpustakaan</h6>
                            <small class="text-muted">Periode: <?= $label_periode ?></small>
                        </div>

                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="status_iplm" id="statusSudah" value="sudah" disabled>
                                <label class="form-check-label fw-bold" for="statusSudah">Sudah Mengisi</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_iplm" id="statusBelum" value="belum">
                                <label class="form-check-label fw-bold" for="statusBelum">Belum Mengisi</label>
                            </div>
                        </div>

                        <div class="alert alert-warning border-0 small mb-4 d-flex align-items-center gap-2" id="statusWarning">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                            <div>Mengubah ke "Belum Mengisi" akan <b>menghapus permanen</b> data jawaban pada periode ini.</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light fw-bold flex-grow-1 rounded-pill" data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-danger fw-bold flex-grow-1 rounded-pill" id="btnSimpanStatus" disabled>Reset Status</button>
                        </div>
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

            function downloadTemplateLib(e) {
                e.preventDefault();
                const headers = ["Nama Perpustakaan","Jenis","Subjenis"];
                const rows = [
                    headers.join(","),
                    "Perpustakaan Desa Sukamaju,Umum,Perpustakaan Desa",
                    "Perpustakaan SMAN 1 Gerung,Sekolah,Perpustakaan SMA"
                ];
                const csvContent = "data:text/csv;charset=utf-8," + rows.join("\n");
                const encodedUri = encodeURI(csvContent);
                const link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "template_perpustakaan.csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
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
            let currentPage = 1;
            let selectedIds = new Set(); // Track selected checkbox IDs across pages
            let isInitialLoad = true; // Flag to prevent scroll on first load
            
            function loadTable(page = 1) {
                currentPage = page;
                const search = document.getElementById('liveSearch').value;
                const kat = document.getElementById('filterKategori').value;
                const sub = document.getElementById('filterSubjenis').value;
                const status = document.getElementById('filterStatus').value;
                const bln = document.getElementById('filterBulan').value;
                const thn = document.getElementById('filterTahun').value;
                const tbody = document.getElementById('tableBody');

                // Show loading state
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><div class="mt-2 small text-muted">Memuat data...</div></td></tr>';

                const url = `perpustakaan.php?ajax_action=load_table&search=${encodeURIComponent(search)}&kategori=${encodeURIComponent(kat)}&subjenis=${encodeURIComponent(sub)}&status_iplm=${encodeURIComponent(status)}&bulan=${bln}&tahun=${thn}&page=${page}&_t=${new Date().getTime()}`;

                fetch(url, { cache: 'no-store' })
                    .then(response => {
                        if (!response.ok) throw new Error("HTTP Status: " + response.status);
                        return response.text();
                    })
                    .then(html => {
                        if(html.trim().length === 0) {
                            html = '<tr><td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>Tidak ada data ditemukan.</td></tr>';
                        }
                        tbody.innerHTML = html;
                        
                        attachEditDeleteEvents();
                        restoreCheckboxStates(); 
                        updateBulkBtn();
                        bindStatusEvents(tbody);
                        
                        // Initialization for tooltips if any
                        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                        tooltipTriggerList.map(function (tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl)
                        });
                        // Scroll to top of table for better UX when changing pages
                        const tableContainer = document.querySelector('.table-responsive');
                        if (tableContainer && typeof isInitialLoad !== 'undefined' && !isInitialLoad) {
                            tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                        if (typeof isInitialLoad !== 'undefined') isInitialLoad = false;
                    })
                    .catch(err => {
                        console.error("Error loading table:", err);
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4"><div class="d-flex flex-column align-items-center"><i class="bi bi-exclamation-triangle fs-1 mb-2"></i><span>Gagal memuat data.</span><small class="text-muted">' + err.message + '</small><button class="btn btn-sm btn-outline-danger mt-2 rounded-pill" onclick="loadTable(' + page + ')">Coba Lagi</button></div></td></tr>';
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
                    const doReset = () => {
                        const fd = new FormData(document.getElementById('formResetStatus'));
                        fd.append('ajax', 1);
                        fetch('perpustakaan.php', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(res => {
                            if(res.status === 'success') {
                                Swal.fire('Berhasil!', res.message, 'success');
                                modalStatusIplm.hide();
                                loadTable(currentPage);
                            } else {
                                Swal.fire('Gagal!', res.message || 'Terjadi kesalahan.', 'error');
                            }
                        });
                    };

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
                            if (result.isConfirmed) doReset();
                        });
                    } else if(confirm('Reset status? Data akan dihapus permanen.')) {
                        doReset();
                    }
                }
            });

            // AJAX for Add/Edit Library
            document.getElementById('formLibrary').addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = this.querySelector('button[type="submit"]');
                const originalText = btn.innerHTML;
                
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';

                const fd = new FormData(this);
                fd.append('ajax', 1);

                fetch('perpustakaan.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;

                    if(res.status === 'success') {
                        Swal.fire('Berhasil!', res.message, 'success');
                        modalLib.hide();
                        loadTable(currentPage);
                    } else {
                        Swal.fire('Gagal!', res.message || 'Terjadi kesalahan.', 'error');
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    Swal.fire('Error!', 'Terjadi kesalahan sistem.', 'error');
                });
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

            // --- BULK DELETE & AJAX DELETE ---

            function restoreCheckboxStates() {
                // Restore checked state for items that were previously selected
                document.querySelectorAll('.check-item').forEach(checkbox => {
                    const id = checkbox.value;
                    if (selectedIds.has(id)) {
                        checkbox.checked = true;
                    }
                });
                
                // Update "Select All" checkbox state
                const allCheckboxes = document.querySelectorAll('.check-item');
                const checkedCheckboxes = document.querySelectorAll('.check-item:checked');
                const checkAll = document.getElementById('checkAll');
                
                if (checkAll && allCheckboxes.length > 0) {
                    checkAll.checked = allCheckboxes.length === checkedCheckboxes.length;
                    checkAll.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
                }
            }

            function toggleSelectAll() {
                const checked = document.getElementById('checkAll').checked;
                document.querySelectorAll('.check-item').forEach(el => {
                    el.checked = checked;
                    const id = el.value;
                    if (checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                });
                updateBulkBtn();
            }

            function updateBulkBtn() {
                const count = selectedIds.size;
                const btn = document.getElementById('btnHapusBulk');
                const span = document.getElementById('countSelected');
                
                if (span) span.innerText = count;
                
                if (btn) {
                    if(count > 0) {
                        btn.classList.remove('d-none');
                    } else {
                        btn.classList.add('d-none');
                    }
                }
            }

            function deleteSingle(id) {
                Swal.fire({
                    title: 'Hapus perpustakaan?',
                    text: 'Data perpustakaan ini akan dihapus permanen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if(result.isConfirmed) {
                        const fd = new FormData();
                        fd.append('csrf_token', '<?= csrf_token() ?>');
                        fd.append('form_type', 'library');
                        fd.append('aksi', 'hapus');
                        fd.append('id', id);
                        fd.append('ajax', 1);

                        fetch('perpustakaan.php', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(res => {
                            if(res.status === 'success') {
                                Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success');
                                loadTable(currentPage); // Reload tanpa reset filter
                            } else {
                                Swal.fire('Gagal!', 'Gagal menghapus data.', 'error');
                            }
                        });
                    }
                });
            }

            function deleteBulk() {
                const ids = Array.from(selectedIds); // Use tracked IDs instead of reading from DOM
                if(ids.length === 0) return;

                Swal.fire({
                    title: 'Hapus ' + ids.length + ' data terpilih?',
                    text: 'Data yang dipilih akan dihapus permanen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus semua',
                    confirmButtonColor: '#d33',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if(result.isConfirmed) {
                        const fd = new FormData();
                        fd.append('csrf_token', '<?= csrf_token() ?>');
                        fd.append('form_type', 'library');
                        fd.append('aksi', 'hapus_bulk');
                        ids.forEach(id => fd.append('ids[]', id));
                        
                        fetch('perpustakaan.php', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(res => {
                            if(res.status === 'success') {
                                Swal.fire('Terhapus!', res.count + ' data berhasil dihapus.', 'success');
                                // Clear selected IDs after successful deletion
                                selectedIds.clear();
                                document.getElementById('checkAll').checked = false;
                                updateBulkBtn();
                                loadTable(currentPage);
                            } else {
                                Swal.fire('Gagal!', res.message || 'Gagal menghapus.', 'error');
                            }
                        });
                    }
                });
            }

            // Handle individual checkbox changes
            function handleCheckboxChange(checkbox) {
                const id = checkbox.value;
                if (checkbox.checked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
                updateBulkBtn();
                
                // Update "Select All" state
                const allCheckboxes = document.querySelectorAll('.check-item');
                const checkedCheckboxes = document.querySelectorAll('.check-item:checked');
                const checkAll = document.getElementById('checkAll');
                
                if (checkAll && allCheckboxes.length > 0) {
                    checkAll.checked = allCheckboxes.length === checkedCheckboxes.length;
                    checkAll.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
                }
            }

            // Check for tab parameter and switch tab
            document.addEventListener('DOMContentLoaded', () => {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('tab') === 'category') {
                    const triggerEl = document.querySelector('#kat-tab');
                    if(triggerEl) {
                        const tabInstance = new bootstrap.Tab(triggerEl);
                        tabInstance.show();
                    }
                }
            });
        </script>
        <script src="../assets/loader.js"></script>
    </body>
    </html>
