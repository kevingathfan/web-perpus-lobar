<?php
// web-perpus-v1/admin/users.php
session_start();
require '../config/database.php';
require '../config/admin_auth.php';

// Set Timezone
date_default_timezone_set('Asia/Makassar');

$pesan = '';
$tipe = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['aksi'])) {
        if ($_POST['aksi'] === 'hapus') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                    if ($count <= 1) {
                        $pesan = 'Tidak bisa menghapus akun terakhir.';
                        $tipe = 'danger';
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$id]);
                        $pesan = 'Akun admin berhasil dihapus.';
                    }
                } catch (Exception $e) {
                    $pesan = 'Gagal menghapus akun.';
                    $tipe = 'danger';
                }
            }
        } elseif ($_POST['aksi'] === 'hapus_log') {
            try {
                $pdo->exec("DELETE FROM password_reset_logs");
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'success', 'message' => 'Log berhasil dihapus']);
                    exit;
                }
                $pesan = 'Log reset password berhasil dihapus.';
            } catch (Exception $e) {
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                    exit;
                }
                $pesan = 'Gagal menghapus log.';
                $tipe = 'danger';
            }
        } elseif ($_POST['aksi'] === 'hapus_email_log') {
            try {
                $pdo->exec("DELETE FROM password_reset_email_logs");
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'success', 'message' => 'Log email berhasil dihapus']);
                    exit;
                }
                $pesan = 'Log pengiriman email berhasil dihapus.';
            } catch (Exception $e) {
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                    exit;
                }
                $pesan = 'Gagal menghapus log email.';
                $tipe = 'danger';
            }
        }
    } else {
        // Add User Logic
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($nama === '' || $password === '') {
            $pesan = 'Nama dan password wajib diisi.';
            $tipe = 'danger';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$nama, $email !== '' ? $email : null, $hash]);
                $pesan = 'Akun admin berhasil ditambahkan.';
            } catch (Exception $e) {
                if ($e instanceof PDOException) {
                    if ($e->errorInfo[1] == 1062) { // Duplicate entry
                        if (strpos($e->getMessage(), 'email') !== false) {
                            $pesan = 'Email sudah terdaftar. Gunakan email lain.';
                        } else {
                            $pesan = 'Nama sudah digunakan. Pastikan nama unik.';
                        }
                    } elseif ($e->errorInfo[1] == 1048) { // Column cannot be null
                        $pesan = 'Gagal: Email tidak valid atau tidak boleh kosong.';
                    } else {
                        $pesan = 'Gagal menambah akun: ' . $e->getMessage();
                    }
                } else {
                    $pesan = 'Terjadi kesalahan: ' . $e->getMessage();
                }
                $tipe = 'danger';
            }
        }
    }
}

// List users
$users = [];
try {
    $stmt = $pdo->query("SELECT id, nama, email, created_at FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$reset_logs = [];
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        ip_address VARCHAR(64),
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $stmt = $pdo->query("SELECT l.id, u.nama, l.ip_address, l.created_at
                         FROM password_reset_logs l
                         JOIN users u ON u.id = l.user_id
                         ORDER BY l.created_at DESC
                         LIMIT 20");
    $reset_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$email_logs = [];
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_email_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL,
        status VARCHAR(20) NOT NULL,
        error_message TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $stmt = $pdo->query("SELECT id, email, status, error_message, created_at
                         FROM password_reset_email_logs
                         ORDER BY created_at DESC
                         LIMIT 20");
    $email_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Admin - DISARPUS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/loader.css">
    <link rel="stylesheet" href="../assets/admin-responsive.css">
    <link rel="stylesheet" href="../assets/govtech.css">
    <link rel="stylesheet" href="../assets/admin-readability.css">
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
            <a href="users.php" class="nav-link active">
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
        <div class="d-flex justify-content-between align-items-center mb-4 page-header">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-dark btn-sm d-lg-none" onclick="toggleSidebar(true)"><i class="bi bi-list"></i></button>
                <div>
                    <h2 class="fw-bold m-0 page-title">Kelola Admin</h2>
                    <p class="text-muted m-0 page-subtitle">Manajemen akun dan log aktivitas sistem.</p>
                </div>
            </div>
        </div>

        <?php if ($pesan): ?>
            <div class="alert alert-<?= $tipe ?> alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                <i class="bi bi-<?= $tipe === 'success' ? 'check-circle' : 'exclamation-circle' ?>-fill me-2"></i>
                <?= $pesan ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left Column: Add New Admin -->
            <div class="col-lg-4">
                <div class="card-clean h-100">
                    <div class="p-4 border-bottom bg-light bg-opacity-50">
                        <div class="d-flex align-items-center gap-2 text-primary">
                            <i class="bi bi-person-plus-fill fs-5"></i>
                            <h6 class="fw-bold mb-0">Tambah Admin Baru</h6>
                        </div>
                    </div>
                    <div class="p-4">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">NAMA LENGKAP</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-person"></i></span>
                                    <input type="text" name="nama" class="form-control border-start-0 ps-0" placeholder="Contoh: Admin Perpustakaan" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">EMAIL (OPSIONAL)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="admin@example.com">
                                </div>
                                <div class="form-text small">Diperlukan untuk fitur reset password.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted">PASSWORD</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-key"></i></span>
                                    <input type="password" name="password" class="form-control border-start-0 ps-0" placeholder="******" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Akun
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: User List -->
            <div class="col-lg-8">
                <div class="card-clean h-100">
                    <div class="p-4 border-bottom bg-light bg-opacity-50 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-people-fill fs-5"></i>
                            <h6 class="fw-bold mb-0">Daftar Admin Aktif</h6>
                        </div>
                        <span class="badge bg-primary rounded-pill"><?= count($users) ?> Akun</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3" width="50">#</th>
                                    <th class="py-3">Admin</th>
                                    <th class="py-3">Dibuat Pada</th>
                                    <th class="pe-4 py-3 text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-5">Belum ada data admin.</td></tr>
                                <?php else: ?>
                                    <?php $no=1; foreach ($users as $u): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-muted"><?= $no++ ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-size: 1.1rem;">
                                                        <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark"><?= htmlspecialchars($u['nama']) ?></div>
                                                        <div class="small text-muted"><?= htmlspecialchars($u['email'] ?? '-') ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-muted small">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?= !empty($u['created_at']) ? date('d M Y', strtotime($u['created_at'])) : '-' ?>
                                                <div class="text-xs text-muted ms-3"><?= !empty($u['created_at']) ? date('H:i', strtotime($u['created_at'])) : '' ?></div>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <button class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="confirmHapus(<?= (int)$u['id'] ?>)">
                                                    <i class="bi bi-trash3-fill me-1"></i> Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Full Width: System Logs with Tabs -->
            <div class="col-12">
                <div class="card-clean">
                    <div class="card-header bg-transparent border-bottom px-4 pt-4 pb-0">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active fw-bold text-dark" data-bs-toggle="tab" data-bs-target="#tab-reset-logs">
                                    <i class="bi bi-shield-lock me-2"></i>Log Reset Password
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link fw-bold text-dark" data-bs-toggle="tab" data-bs-target="#tab-email-logs">
                                    <i class="bi bi-envelope me-2"></i>Log Pengiriman Email
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-0">
                        <div class="tab-content">
                            <!-- Reset Logs Tab -->
                            <div class="tab-pane fade show active" id="tab-reset-logs">
                                <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light bg-opacity-25">
                                    <small class="text-muted fst-italic">Menampilkan 20 riwayat permintaan reset password terakhir.</small>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmHapusLog()">
                                        <i class="bi bi-trash me-1"></i> Bersihkan Log
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4">User</th>
                                                <th>IP Address</th>
                                                <th>Waktu Request</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($reset_logs)): ?>
                                                <tr><td colspan="3" class="text-center text-muted py-4">Belum ada log aktivitas.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($reset_logs as $log): ?>
                                                    <tr>
                                                        <td class="ps-4">
                                                            <span class="fw-bold text-dark"><?= htmlspecialchars($log['nama']) ?></span>
                                                        </td>
                                                        <td class="font-monospace text-muted small"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                                                        <td class="small"><?= date('d M Y H:i:s', strtotime($log['created_at'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Email Logs Tab -->
                            <div class="tab-pane fade" id="tab-email-logs">
                                <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light bg-opacity-25">
                                    <small class="text-muted fst-italic">Menampilkan 20 riwayat pengiriman email terakhir.</small>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmHapusEmailLog()">
                                        <i class="bi bi-trash me-1"></i> Bersihkan Log
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4">Email Penerima</th>
                                                <th>Status Pengiriman</th>
                                                <th>Pesan Sistem</th>
                                                <th>Waktu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($email_logs)): ?>
                                                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada log email.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($email_logs as $log): ?>
                                                    <tr>
                                                        <td class="ps-4 font-monospace small"><?= htmlspecialchars($log['email']) ?></td>
                                                        <td>
                                                            <?php if(strtolower($log['status']) == 'sent'): ?>
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle">TERKIRIM</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">GAGAL</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="small text-muted text-break" style="max-width: 300px;"><?= htmlspecialchars($log['error_message'] ?? '-') ?></td>
                                                        <td class="small"><?= date('d M Y H:i:s', strtotime($log['created_at'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function toggleSidebar(open) {
            document.body.classList.toggle('sidebar-open', open);
        }
        document.querySelectorAll('.sidebar .nav-link').forEach((link) => {
            link.addEventListener('click', () => toggleSidebar(false));
        });

        function confirmHapus(id) {
            Swal.fire({
                title: 'Hapus akun admin?',
                text: 'Akun akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function confirmHapusLog() {
            Swal.fire({
                title: 'Hapus semua log?',
                text: 'Log reset password akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('csrf_token', '<?= csrf_token() ?>');
                    formData.append('aksi', 'hapus_log');
                    formData.append('ajax', '1');

                    fetch('users.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Berhasil!', data.message, 'success');
                            const tbody = document.querySelector('#tab-reset-logs tbody');
                            if (tbody) {
                                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Belum ada log aktivitas.</td></tr>';
                            }
                        } else {
                            Swal.fire('Gagal!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Terjadi kesalahan sistem.', 'error');
                    });
                }
            });
        }

        function confirmHapusEmailLog() {
            Swal.fire({
                title: 'Hapus log email?',
                text: 'Riwayat pengiriman email akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('csrf_token', '<?= csrf_token() ?>');
                    formData.append('aksi', 'hapus_email_log');
                    formData.append('ajax', '1');

                    fetch('users.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Berhasil!', data.message, 'success');
                            const tbody = document.querySelector('#tab-email-logs tbody');
                            if (tbody) {
                                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Belum ada log email.</td></tr>';
                            }
                        } else {
                            Swal.fire('Gagal!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Terjadi kesalahan sistem.', 'error');
                    });
                }
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/loader.js"></script>
</body>
</html>
