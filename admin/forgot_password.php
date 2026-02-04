<?php
// admin/forgot_password.php
session_start();
require '../config/database.php';
require '../config/public_security.php';
require '../config/mailer.php';

$config = require __DIR__ . '/../mail.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_email_logs (
        id SERIAL PRIMARY KEY,
        email VARCHAR(150) NOT NULL,
        status VARCHAR(20) NOT NULL,
        error_message TEXT NULL,
        token_hash VARCHAR(255) NULL,
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
    )");
    // pastikan kolom tambahan ada jika tabel sudah terlanjur dibuat
    $pdo->exec("ALTER TABLE password_reset_email_logs ADD COLUMN IF NOT EXISTS token_hash VARCHAR(255)");
    $pdo->exec("ALTER TABLE password_reset_email_logs ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP");
} catch (Exception $e) {}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL,
        token_hash VARCHAR(255) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        used_at TIMESTAMP NULL,
        created_at TIMESTAMP NOT NULL DEFAULT NOW()
    )");
} catch (Exception $e) {}

$pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_public_csrf();
    $email = trim($_POST['email'] ?? '');

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rl = rate_limit_check('reset:' . $ip, 5, 3600);
    if (!$rl['allowed']) {
        $pesan = 'Terlalu banyak permintaan. Coba lagi nanti.';
    } else {
        $stmt = $pdo->prepare("SELECT id, nama, email FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $token);
            // Simpan dengan waktu dari DB agar tidak bentrok timezone
            $ins = $pdo->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at)
                                  VALUES (?, ?, (NOW() + INTERVAL '1 hour')) RETURNING expires_at");
            $ins->execute([$user['id'], $token_hash]);
            $expires = $ins->fetchColumn();

            // Hapus token lama/expired agar tabel tetap bersih
            $pdo->prepare("DELETE FROM password_resets WHERE expires_at <= NOW() OR used_at IS NOT NULL")->execute();

            $app_url = rtrim($config['app_url'] ?? '', '/');
            $link = $app_url . "/admin/reset_password.php?token=" . rawurlencode($token);
            $body = "Halo {$user['nama']},\n\n"
                  . "Klik link berikut untuk reset password:\n"
                  . "{$link}\n\n"
                  . "Link ini berlaku 1 jam.\n";

            $err = null;
            $ok = smtp_send_mail($config, $user['email'], 'Reset Password Admin', $body, $err);
            $stmtLog = $pdo->prepare("INSERT INTO password_reset_email_logs (email, status, error_message, token_hash, expires_at) VALUES (?, ?, ?, ?, ?)");
            $stmtLog->execute([$user['email'], $ok ? 'sent' : 'failed', $ok ? null : $err, $token_hash, $expires]);
        }

        $pesan = 'Jika email terdaftar, link reset sudah dikirim.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/loader.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { max-width: 420px; width: 100%; border-radius: 14px; }
    </style>
    </head>
<body>
    <?php include __DIR__ . '/../config/loader.php'; ?>
    <div class="card p-4 shadow-sm">
        <h5 class="fw-bold mb-2">Lupa Password</h5>
        <p class="text-muted small">Masukkan email admin untuk menerima link reset.</p>
        <?php if ($pesan): ?><div class="alert alert-info"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= public_csrf_token() ?>">
            <div class="mb-3">
                <label class="form-label fw-bold">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <button class="btn btn-dark w-100">Kirim Link Reset</button>
            <a href="login.php" class="d-block text-center mt-3 small text-muted">Kembali ke Login</a>
        </form>
    </div>
    <script src="../assets/loader.js"></script>
</body>
</html>
