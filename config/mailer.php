<?php
// config/mailer.php

function smtp_send_mail($config, $to, $subject, $body, &$error = null) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    $host = $config['smtp_host'] ?? '';
    $port = (int)($config['smtp_port'] ?? 587);
    $user = $config['smtp_user'] ?? '';
    $pass = $config['smtp_pass'] ?? '';
    $fromName = $config['from_name'] ?? 'Admin';
    $fromEmail = $config['from_email'] ?? $user;

    if (!$host || !$user || !$pass || !$fromEmail) {
        $error = 'Konfigurasi SMTP tidak lengkap.';
        return false;
    }

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $fp = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$fp) { $error = "Connection failed: $errstr ($errno)"; return false; }

    // Set non-blocking to handle timeouts better if needed, but blocking is easier for simple SMTP
    stream_set_timeout($fp, 10);

    $read = function() use ($fp) {
        $lines = "";
        while (!feof($fp)) {
            $str = fgets($fp, 515);
            if ($str === false) break;
            $lines .= $str;
            if (substr($str, 3, 1) === ' ') break;
        }
        return $lines;
    };
    
    $send = function($cmd) use ($fp) { fwrite($fp, $cmd . "\r\n"); };
    
    $expect = function($code) use ($read, &$error) {
        $response = $read();
        if (!$response) { $error = 'Empty response from server'; return false; }
        // Check ONLY the last line code
        $lines = explode("\n", trim($response));
        $lastLine = end($lines);
        if (strpos($lastLine, (string)$code) !== 0) {
            $error = "Expected $code, got: " . $lastLine;
            return false;
        }
        return true;
    };

    // Initial greeting
    if (!$expect(220)) { fclose($fp); return false; }

    $send('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    if (!$expect(250)) { fclose($fp); return false; }

    $send('STARTTLS');
    if (!$expect(220)) { fclose($fp); return false; }

    if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        $error = 'TLS negotiation failed';
        fclose($fp);
        return false;
    }

    $send('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    if (!$expect(250)) { fclose($fp); return false; }

    $send('AUTH LOGIN');
    if (!$expect(334)) { fclose($fp); return false; }
    
    $send(base64_encode($user));
    if (!$expect(334)) { fclose($fp); return false; }
    
    $send(base64_encode($pass));
    if (!$expect(235)) { fclose($fp); return false; }

    $send("MAIL FROM:<{$fromEmail}>");
    if (!$expect(250)) { fclose($fp); return false; }
    
    $send("RCPT TO:<{$to}>");
    if (!$expect(250)) { fclose($fp); return false; }
    
    $send("DATA");
    if (!$expect(354)) { fclose($fp); return false; }

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "To: <{$to}>\r\n";
    $headers .= "Subject: {$encodedSubject}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";

    $message = $headers . "\r\n" . $body . "\r\n.";
    fwrite($fp, $message . "\r\n");
    
    if (!$expect(250)) { fclose($fp); return false; }

    $send("QUIT");
    fclose($fp);
    return true;
}
