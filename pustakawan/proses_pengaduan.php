<?php
// web-perpus-v1/pustakawan/proses_pengaduan.php
require '../config/database.php';
require '../config/public_security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_public_csrf();

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rl = rate_limit_check('pengaduan:' . $ip, 5, 3600);
    if (!$rl['allowed']) {
        $msg = 'Anda terlalu sering mengirim pengaduan. Coba lagi dalam ' . ceil($rl['retry_after'] / 60) . ' menit.';
        echo "<!DOCTYPE html><html lang='id'><head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Peringatan</title>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
              </head><body>
                <script>
                    Swal.fire({
                        icon: 'warning',
                        title: 'Terlalu Banyak Permintaan',
                        text: " . json_encode($msg) . ",
                        confirmButtonColor: '#111'
                    }).then(() => {
                        window.location='form_pengaduan.php';
                    });
                </script>
              </body></html>";
        exit;
    }

    $nama = !empty($_POST['nama']) ? $_POST['nama'] : 'Anonim';
    $kontak = !empty($_POST['kontak']) ? $_POST['kontak'] : '-';
    $pesan_raw = $_POST['pesan'] ?? '';

    $badwords = require __DIR__ . '/../config/profanity.php';
    $filtered = false;

    $filterBadWords = function ($text, $badwords, &$flag) {
        $clean = $text;
        foreach ($badwords as $word) {
            $w = trim($word);
            if ($w === '') continue;
            $pattern = '/\b' . preg_quote($w, '/') . '\b/i';
            if (preg_match($pattern, $clean)) {
                $flag = true;
                $clean = preg_replace($pattern, '***', $clean);
            }
        }
        return $clean;
    };

    $pesan = $filterBadWords($pesan_raw, $badwords, $filtered);

    try {
        $sql = "INSERT INTO pengaduan (nama, kontak, pesan, tanggal) VALUES (?, ?, ?, CURRENT_DATE)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $kontak, $pesan]);

        $popupText = $filtered
            ? 'Terima kasih! Laporan/Saran Anda telah kami terima. Beberapa kata tidak pantas telah disensor.'
            : 'Terima kasih! Laporan/Saran Anda telah kami terima.';
        echo "<!DOCTYPE html><html lang='id'><head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Pengaduan</title>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
              </head><body>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Terima kasih!',
                        text: " . json_encode($popupText) . ",
                        confirmButtonColor: '#111'
                    }).then(() => {
                        window.location='../index.php';
                    });
                </script>
              </body></html>";

    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}
?>
