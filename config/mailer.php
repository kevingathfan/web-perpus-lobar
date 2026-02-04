<?php
// config/mailer.php

function smtp_send_mail($config, $to, $subject, $body, &$error = null) {
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

    $fp = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 15);
    if (!$fp) { $error = $errstr; return false; }

    $read = function() use ($fp) { return fgets($fp, 515); };
    $send = function($cmd) use ($fp) { fwrite($fp, $cmd . "\r\n"); };
    $expect = function($code) use ($read, &$error) {
        $codeStr = (string)$code;
        $line = '';
        while (true) {
            $line = $read();
            if ($line === false || $line === '') {
                $error = 'SMTP error';
                return false;
            }
            if (strpos($line, $codeStr) === 0) {
                // multi-line replies continue with "code-"
                if (isset($line[3]) && $line[3] === '-') {
                    continue;
                }
                return true;
            }
        }
    };

    if (!$expect(220)) { fclose($fp); return false; }
    $send('EHLO localhost');
    if (!$expect(250)) { fclose($fp); return false; }

    $send('STARTTLS');
    if (!$expect(220)) { fclose($fp); return false; }
    if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { $error = 'TLS gagal'; fclose($fp); return false; }

    $send('EHLO localhost');
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
