<?php
// admin/reset_password.php
session_start();
require '../config/database.php';
require '../config/public_security.php';

$pesan = '';
$success = false;
$token = $_GET['token'] ?? '';
$token = is_string($token) ? trim($token) : '';
if ($token !== '') {
    $parts = preg_split('/\s+/', $token);
    $token = $parts[0] ?? $token;
}
$token = strtolower($token);
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_public_csrf();
    $token = $_POST['token'] ?? '';
    $token = is_string($token) ? trim($token) : '';
    if ($token !== '') {
        $parts = preg_split('/\s+/', $token);
        $token = $parts[0] ?? $token;
    }
    $token = strtolower($token);
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($password === '' || $password !== $confirm) {
        $pesan = 'Password tidak cocok.';
    } else {
        $token_hash = hash('sha256', $token);
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$token_hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->execute([$hash, $row['user_id']]);
            $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?")->execute([$row['id']]);
            $pdo->prepare("DELETE FROM password_resets WHERE expires_at <= NOW() OR used_at IS NOT NULL")->execute();

            // Log reset password
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_logs (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    ip_address VARCHAR(64),
                    created_at TIMESTAMP NOT NULL DEFAULT NOW()
                )");
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $pdo->prepare("INSERT INTO password_reset_logs (user_id, ip_address) VALUES (?, ?)")->execute([$row['user_id'], $ip]);
            } catch (Exception $e) {}

            $pesan = 'Password berhasil direset. Silakan login.';
            $success = true;
        } else {
            $pesan = 'Token tidak valid atau sudah kadaluarsa.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/loader.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { max-width: 420px; width: 100%; border-radius: 14px; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../config/loader.php'; ?>
    <div class="card p-4 shadow-sm">
        <h5 class="fw-bold mb-2">Reset Password</h5>
        <?php if ($pesan): ?><div class="alert alert-info"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= public_csrf_token() ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="mb-3">
                <label class="form-label fw-bold">Password Baru</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Ulangi Password</label>
                <input type="password" name="confirm" class="form-control" required>
            </div>
            <button class="btn btn-dark w-100">Simpan Password</button>
            <a href="login.php" class="d-block text-center mt-3 small text-muted">Kembali ke Login</a>
        </form>
    </div>
    <?php if ($success): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: <?= json_encode($pesan) ?>,
            confirmButtonColor: '#111'
        }).then(() => {
            window.location = 'login.php';
        });
    </script>
    <?php endif; ?>
    <script src="../assets/loader.js"></script>
</body>
</html>
