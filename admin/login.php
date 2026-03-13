<?php
// web-perpus-v1/admin/login.php
session_start();
require '../config/database.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// --- LOGIKA LOGIN SEDERHANA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
    $max_attempts = 5;
    $lock_minutes = 5;
    $now = time();

    if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
    if (!isset($_SESSION['login_lock_until'])) $_SESSION['login_lock_until'] = 0;

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ip_key = hash('sha256', $ip);
    $rate_file = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'login_rate_' . $ip_key . '.json';
    $rate_data = ['attempts' => 0, 'lock_until' => 0];
    if (is_file($rate_file)) {
        $raw = @file_get_contents($rate_file);
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) $rate_data = array_merge($rate_data, $decoded);
    }

    $session_locked = $now < (int)$_SESSION['login_lock_until'];
    $ip_locked = $now < (int)$rate_data['lock_until'];

    if ($session_locked || $ip_locked) {
        $lock_until = max((int)$_SESSION['login_lock_until'], (int)$rate_data['lock_until']);
        $sisa = $lock_until - $now;
        $error = "Terlalu banyak percobaan. Coba lagi dalam " . ceil($sisa / 60) . " menit.";
    } else {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Verifikasi dari tabel users (login via nama)
    $stmt = $pdo->prepare("SELECT id, nama, password FROM users WHERE nama = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = $user['nama'];
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_lock_until'] = 0;
        $rate_data['attempts'] = 0;
        $rate_data['lock_until'] = 0;
        @file_put_contents($rate_file, json_encode($rate_data));
        
        // Arahkan ke dashboard admin (Buat file dashboard.php nanti)
        header('Location: dashboard.php'); 
        exit;
    } else {
        $_SESSION['login_attempts']++;
        $rate_data['attempts']++;
        if ($_SESSION['login_attempts'] >= $max_attempts) {
            $_SESSION['login_lock_until'] = $now + ($lock_minutes * 60);
            $error = "Terlalu banyak percobaan. Coba lagi dalam $lock_minutes menit.";
        } else {
            $sisa = $max_attempts - $_SESSION['login_attempts'];
            $error = "Username atau Password salah! Sisa percobaan: $sisa";
        }
        if ($rate_data['attempts'] >= $max_attempts) {
            $rate_data['lock_until'] = $now + ($lock_minutes * 60);
        }
        @file_put_contents($rate_file, json_encode($rate_data));
    }
    }
    } // Close CSRF verification
} // Close POST method check
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Dinas Kearsipan dan Perpustakaan</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/govtech.css">
    <link rel="stylesheet" href="../assets/loader.css">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <?php include __DIR__ . '/../config/loader.php'; ?>

    <div class="card-clean p-4 p-md-5 shadow-lg" style="max-width: 450px; width: 100%; background: #fff;">
        <div class="text-center mb-4">
            <div class="d-flex justify-content-center gap-3 mb-4">
                <img src="../assets/logo_lobar.png" alt="Lobar" style="height: 60px;">
                <img src="../assets/logo_disarpus.png" alt="Disarpus" style="height: 60px;">
            </div>
            <h4 class="fw-extrabold mb-1 text-dark">LOGIN ADMIN</h4>
            <p class="text-muted small mb-0">Portal Dinas Kearsipan & Perpustakaan</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger border-0 bg-danger-subtle text-danger small mb-4 rounded-3 text-center">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">USERNAME / NAMA</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-person text-muted"></i></span>
                    <input type="text" name="username" class="form-control border-start-0 ps-0" placeholder="Masukkan nama..." required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">PASSWORD</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control border-start-0 ps-0" placeholder="Masukkan password..." required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold rounded-pill shadow-sm mb-4">
                MASUK DASHBOARD <i class="bi bi-arrow-right ms-2"></i>
            </button>

            <div class="text-center border-top pt-3">
                <a href="../index.php" class="text-decoration-none small text-muted d-inline-flex align-items-center hover-scale">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Halaman Depan
                </a>
            </div>
        </form>
    </div>

    <script src="../assets/loader.js"></script>
</body>
</html>
