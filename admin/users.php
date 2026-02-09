<?php
// web-perpus-v1/admin/users.php
session_start();
require '../config/database.php';
require '../config/admin_auth.php';

$pesan = '';
$tipe = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'hapus') {
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
    } else {
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
            $pesan = 'Gagal menambah akun. Pastikan nama unik.';
            $tipe = 'danger';
        }
    }
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'hapus_log') {
        try {
            $pdo->exec("DELETE FROM password_reset_logs");
            $pesan = 'Log reset password berhasil dihapus.';
        } catch (Exception $e) {
            $pesan = 'Gagal menghapus log.';
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
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL,
        ip_address VARCHAR(64),
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
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
        id SERIAL PRIMARY KEY,
        email VARCHAR(150) NOT NULL,
        status VARCHAR(20) NOT NULL,
        error_message TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
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
            <a href="hasil_kuisioner.php" class="nav-link"><i class="bi bi-table"></i> HASIL KUISIONER</a>
            <a href="atur_pertanyaan.php" class="nav-link"><i class="bi bi-file-text"></i> KUISIONER</a>
            <a href="pengaduan.php" class="nav-link"><i class="bi bi-chat-left-text"></i> PENGADUAN</a>
            <a href="users.php" class="nav-link active"><i class="bi bi-people-fill"></i> ADMIN</a>
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
                    <h2 class="fw-bold m-0 page-title">Kelola Admin</h2>
                    <p class="text-muted m-0 page-subtitle">Tambah akun admin baru.</p>
                </div>
            </div>
        </div>

        <?php if ($pesan): ?>
            <div class="alert alert-<?= $tipe ?>"><?= $pesan ?></div>
        <?php endif; ?>

        <div class="card-clean mb-4">
            <form method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Nama</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Email (Opsional)</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-dark fw-bold">Tambah Admin</button>
                </div>
            </form>
        </div>

        <div class="card-clean">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Dibuat</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="4" class="text-center text-muted">Belum ada admin.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= (int)$u['id'] ?></td>
                                    <td><?= htmlspecialchars($u['nama']) ?></td>
                                    <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                                    <td><?= !empty($u['created_at']) ? date('d M Y H:i', strtotime($u['created_at'])) : '-' ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-danger" type="button" onclick="confirmHapus(<?= (int)$u['id'] ?>)">Hapus</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-clean mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold m-0">Log Reset Password (20 Terbaru)</h6>
                <button class="btn btn-sm btn-outline-danger" type="button" onclick="confirmHapusLog()">Hapus Log</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>IP</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reset_logs)): ?>
                            <tr><td colspan="4" class="text-center text-muted">Belum ada log.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reset_logs as $log): ?>
                                <tr>
                                    <td><?= (int)$log['id'] ?></td>
                                    <td><?= htmlspecialchars($log['nama']) ?></td>
                                    <td><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                                    <td><?= date('d M Y H:i', strtotime($log['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-clean mt-4">
            <h6 class="fw-bold mb-3">Log Email Reset (20 Terbaru)</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Error</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($email_logs)): ?>
                            <tr><td colspan="5" class="text-center text-muted">Belum ada log.</td></tr>
                        <?php else: ?>
                            <?php foreach ($email_logs as $log): ?>
                                <tr>
                                    <td><?= (int)$log['id'] ?></td>
                                    <td><?= htmlspecialchars($log['email']) ?></td>
                                    <td><?= htmlspecialchars($log['status']) ?></td>
                                    <td class="text-muted small"><?= htmlspecialchars($log['error_message'] ?? '-') ?></td>
                                    <td><?= date('d M Y H:i', strtotime($log['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="aksi" value="hapus_log">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
    <script src="../assets/loader.js"></script>
</body>
</html>
