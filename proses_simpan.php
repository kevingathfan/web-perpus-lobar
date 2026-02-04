<?php
// web-perpus-v1/proses_simpan.php
require 'config/database.php';
require 'config/public_security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_public_csrf();
    // [PERBAIKAN] Cek apakah library_id kosong? Jika ya, ubah jadi NULL
    $library_id = !empty($_POST['library_id']) ? $_POST['library_id'] : null;
    
    $jenis = $_POST['jenis_kuesioner'];
    $jawaban = $_POST['jawaban']; // Array [id_soal => isi_jawaban]
    $periode_bulan = date('m');
    $periode_tahun = date('Y');

    $normalize_kontak = function ($value) {
        $v = trim((string)$value);
        $v = strtolower($v);
        if ($v === '') return '';
        if (strpos($v, '@') !== false) return $v;
        $digits = preg_replace('/\D+/', '', $v);
        return $digits !== '' ? $digits : $v;
    };
    $renderErrorPopup = function ($title, $message) {
        echo "<!DOCTYPE html><html lang='id'><head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Peringatan</title>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
              </head><body>
                <script>
                    Swal.fire({
                        icon: 'warning',
                        title: " . json_encode($title) . ",
                        text: " . json_encode($message) . ",
                        confirmButtonColor: '#111'
                    }).then(() => {
                        window.location = 'index.php';
                    });
                </script>
              </body></html>";
        exit;
    };

    try {
        // --- RATE LIMIT (KUISIONER) ---
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'kuesioner:' . $ip;
        $rl = rate_limit_check($key, 20, 3600);
        if (!$rl['allowed']) {
            $renderErrorPopup(
                'Terlalu Banyak Permintaan',
                'Anda terlalu sering mengirim kuisioner. Coba lagi dalam ' . ceil($rl['retry_after'] / 60) . ' menit.'
            );
        }
        // --- VALIDASI DUPLIKASI (KHUSUS IPLM) ---
        if ($jenis === 'IPLM') {
            // 1) Perpustakaan yang sama tidak bisa mengisi 2x dalam bulan yang sama
            if (!empty($library_id)) {
                $stmtCekPerpus = $pdo->prepare("SELECT 1 FROM trans_header WHERE jenis_kuesioner = 'IPLM' AND library_id = ? AND periode_bulan = ? AND periode_tahun = ? LIMIT 1");
                $stmtCekPerpus->execute([$library_id, $periode_bulan, $periode_tahun]);
                if ($stmtCekPerpus->fetchColumn()) {
                    $renderErrorPopup(
                        'Pengisian Ditolak',
                        "Perpustakaan ini sudah mengisi IPLM pada periode {$periode_bulan}/{$periode_tahun}."
                    );
                }
            }

            // 2) Orang yang sama (kontak sama) tidak boleh mengisi perpus berbeda pada bulan yang sama
            $stmtKontakSetting = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'iplm_kontak_pertanyaan_id' LIMIT 1");
            $stmtKontakSetting->execute();
            $kontak_setting_id = $stmtKontakSetting->fetchColumn();

            $kontak_ids = [];
            if (!empty($kontak_setting_id)) {
                $kontak_ids = [(int)$kontak_setting_id];
            } else {
                $stmtKontakId = $pdo->prepare("
                    SELECT id FROM master_pertanyaan 
                    WHERE jenis_kuesioner = 'IPLM' 
                    AND (
                        teks_pertanyaan ILIKE '%kontak pengisi kuesioner%' OR
                        teks_pertanyaan ILIKE '%whatsapp aktif%' OR
                        teks_pertanyaan ILIKE '%kontak%' OR
                        teks_pertanyaan ILIKE '%no hp%' OR
                        teks_pertanyaan ILIKE '%no. hp%' OR
                        teks_pertanyaan ILIKE '%telepon%' OR
                        teks_pertanyaan ILIKE '%whatsapp%' OR
                        teks_pertanyaan ILIKE '%email%'
                    )
                ");
                $stmtKontakId->execute();
                $kontak_ids = $stmtKontakId->fetchAll(PDO::FETCH_COLUMN);

                if (count($kontak_ids) === 1) {
                    $stmtUpdateKontak = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'iplm_kontak_pertanyaan_id'");
                    $stmtUpdateKontak->execute([$kontak_ids[0]]);
                    if ($stmtUpdateKontak->rowCount() === 0) {
                        $stmtInsertKontak = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('iplm_kontak_pertanyaan_id', ?)");
                        $stmtInsertKontak->execute([$kontak_ids[0]]);
                    }
                }
            }

            $kontak_input = '';
            foreach ($kontak_ids as $kid) {
                if (isset($jawaban[$kid]) && trim($jawaban[$kid]) !== '') {
                    $kontak_input = $jawaban[$kid];
                    break;
                }
            }
            $kontak_norm = $normalize_kontak($kontak_input);

            if (!empty($kontak_ids) && $kontak_norm !== '') {
                $placeholders = implode(',', array_fill(0, count($kontak_ids), '?'));
                $is_digits = ctype_digit($kontak_norm);

                if ($is_digits) {
                    $sqlCekKontak = "
                        SELECT 1
                        FROM trans_header h
                        JOIN trans_detail d ON d.header_id = h.id
                        WHERE h.jenis_kuesioner = 'IPLM'
                          AND h.periode_bulan = ?
                          AND h.periode_tahun = ?
                          AND h.library_id IS DISTINCT FROM ?
                          AND d.pertanyaan_id IN ($placeholders)
                          AND regexp_replace(d.jawaban, '[^0-9]', '', 'g') = ?
                        LIMIT 1
                    ";
                    $params = array_merge([$periode_bulan, $periode_tahun, $library_id], $kontak_ids, [$kontak_norm]);
                } else {
                    $sqlCekKontak = "
                        SELECT 1
                        FROM trans_header h
                        JOIN trans_detail d ON d.header_id = h.id
                        WHERE h.jenis_kuesioner = 'IPLM'
                          AND h.periode_bulan = ?
                          AND h.periode_tahun = ?
                          AND h.library_id IS DISTINCT FROM ?
                          AND d.pertanyaan_id IN ($placeholders)
                          AND LOWER(TRIM(d.jawaban)) = ?
                        LIMIT 1
                    ";
                    $params = array_merge([$periode_bulan, $periode_tahun, $library_id], $kontak_ids, [$kontak_norm]);
                }

                $stmtCekKontak = $pdo->prepare($sqlCekKontak);
                $stmtCekKontak->execute($params);
                if ($stmtCekKontak->fetchColumn()) {
                    $renderErrorPopup(
                        'Pengisian Ditolak',
                        "Kontak ini sudah pernah digunakan untuk mengisi IPLM di perpustakaan lain pada periode {$periode_bulan}/{$periode_tahun}."
                    );
                }
            }
        }

        $pdo->beginTransaction();

        // 1. Simpan Header
        $stmtHeader = $pdo->prepare("INSERT INTO trans_header (library_id, jenis_kuesioner, periode_bulan, periode_tahun) VALUES (?, ?, ?, ?) RETURNING id");
        
        // Eksekusi dengan $library_id yang sudah divalidasi (bisa angka atau NULL)
        $stmtHeader->execute([$library_id, $jenis, $periode_bulan, $periode_tahun]);
        
        $header_id = $stmtHeader->fetchColumn(); 

        // 2. Simpan Detail
        $stmtDetail = $pdo->prepare("INSERT INTO trans_detail (header_id, pertanyaan_id, jawaban) VALUES (?, ?, ?)");

        foreach ($jawaban as $soal_id => $isi) {
            $stmtDetail->execute([$header_id, $soal_id, $isi]);
        }

        $pdo->commit();
        
        // Redirect kembali ke index dengan pesan sukses
        echo "<!DOCTYPE html><html lang='id'><head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Berhasil</title>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
              </head><body>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Terima kasih!',
                        text: " . json_encode("Data $jenis berhasil disimpan.") . ",
                        confirmButtonColor: '#111'
                    }).then(() => {
                        window.location='index.php';
                    });
                </script>
              </body></html>";

    } catch (Exception $e) {
        $pdo->rollBack();
        // Tampilkan error jika masih ada masalah lain
        die("Error System: " . $e->getMessage());
    }
}
?>
