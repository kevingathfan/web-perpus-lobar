<?php
// config/public_security.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['public_csrf_token'])) {
    $_SESSION['public_csrf_token'] = bin2hex(random_bytes(32));
}

function public_csrf_token() {
    return $_SESSION['public_csrf_token'] ?? '';
}

function verify_public_csrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['public_csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
}

function rate_limit_check($key, $max_attempts, $window_seconds) {
    $now = time();
    $safe_key = hash('sha256', $key);
    $file = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'rate_' . $safe_key . '.json';
    $data = ['count' => 0, 'reset' => $now + $window_seconds];
    $fp = @fopen($file, 'c+');
    if ($fp) {
        if (flock($fp, LOCK_EX)) {
            $raw = stream_get_contents($fp);
            if ($raw !== false && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) $data = array_merge($data, $decoded);
            }
            if ($now > (int)$data['reset']) {
                $data = ['count' => 0, 'reset' => $now + $window_seconds];
            }
            $data['count']++;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($data));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    } else {
        if ($now > (int)$data['reset']) {
            $data = ['count' => 0, 'reset' => $now + $window_seconds];
        }
        $data['count']++;
        @file_put_contents($file, json_encode($data));
    }
    $remaining = $max_attempts - $data['count'];
    $retry_after = max(0, (int)$data['reset'] - $now);
    $allowed = $data['count'] <= $max_attempts;
    return ['allowed' => $allowed, 'remaining' => $remaining, 'retry_after' => $retry_after];
}
