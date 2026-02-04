<?php
// web-perpus-v1/admin/login.php
session_start();
require '../config/database.php'; // Hubungkan database jika ingin verifikasi real

$error = '';

// --- LOGIKA LOGIN SEDERHANA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Dinas Kearsipan dan Perpustakaan</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/loader.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 450px;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid #e0e0e0;
            overflow: hidden;
        }

        /* Hiasan Header Card */
        .card-header-custom {
            background-color: #ffffff;
            padding: 30px 30px 10px 30px;
            text-align: center;
            border-bottom: none;
        }

        .logo-wrapper {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .logo-img {
            height: 60px;
            object-fit: contain;
        }

        .card-body-custom {
            padding: 20px 40px 40px 40px;
        }

        .form-label {
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }

        .form-control {
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #000;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.1);
        }

        .btn-login {
            background-color: #000;
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .btn-login:hover {
            background-color: #333;
            transform: translateY(-2px);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
            font-size: 13px;
        }
        
        .back-link:hover { color: #000; }

        .alert-custom {
            font-size: 13px;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../config/loader.php'; ?>

    <div class="login-card">
        <div class="card-header-custom">
            <div class="logo-wrapper">
                <img src="../assets/logo_lobar.png" alt="Lobar" class="logo-img">
                <img src="../assets/logo_disarpus.png" alt="Disarpus" class="logo-img">
            </div>
            <h5 class="fw-bold mb-1">LOGIN ADMIN</h5>
            <p class="text-muted small mb-0">Portal Dinas Kearsipan & Perpustakaan</p>
        </div>

        <div class="card-body-custom">
            
            <?php if($error): ?>
                <div class="alert alert-danger alert-custom text-center">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan nama..." required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password..." required>
                </div>

                <button type="submit" class="btn-login">MASUK DASHBOARD</button>

                <a href="forgot_password.php" class="back-link">Lupa Password?</a>
                <a href="../index.php" class="back-link">&larr; Kembali ke Halaman Depan</a>
            </form>
        </div>
    </div>

    <script src="../assets/loader.js"></script>
</body>
</html>
