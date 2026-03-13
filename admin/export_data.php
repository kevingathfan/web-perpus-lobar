<?php
// web-perpus-v1/admin/eksport_data.php
session_start();
require '../config/database.php';
require '../config/admin_auth.php';

// Load Library PhpSpreadsheet
require '../vendor/autoload.php'; 

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// Set Zona Waktu
date_default_timezone_set('Asia/Makassar');

function upper_str($value) {
    if (!is_string($value)) {
        return $value;
    }
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($value, 'UTF-8');
    }
    return strtoupper($value);
}

// 1. TANGKAP PARAMETER (POST -> SESSION -> GET)
$filter = $_SESSION['hasil_kuisioner_filter'] ?? [];
$jenis = $_POST['jenis'] ?? ($filter['jenis'] ?? ($_GET['jenis'] ?? ''));
$start_bln = $_POST['start_bulan'] ?? ($filter['start_bulan'] ?? ($_GET['start_bulan'] ?? date('m')));
$start_thn = $_POST['start_tahun'] ?? ($filter['start_tahun'] ?? ($_GET['start_tahun'] ?? date('Y')));
$end_bln   = $_POST['end_bulan'] ?? ($filter['end_bulan'] ?? ($_GET['end_bulan'] ?? date('m')));
$start_thn = $_POST['start_tahun'] ?? ($filter['start_tahun'] ?? ($_GET['start_tahun'] ?? date('Y')));
$end_bln   = $_POST['end_bulan'] ?? ($filter['end_bulan'] ?? ($_GET['end_bulan'] ?? date('m')));
$end_thn   = $_POST['end_tahun'] ?? ($filter['end_tahun'] ?? ($_GET['end_tahun'] ?? date('Y')));
$status_filter = $_POST['status_filter'] ?? 'filled'; // filled, unfilled, all

$start_bln = str_pad($start_bln, 2, '0', STR_PAD_LEFT);
$end_bln = str_pad($end_bln, 2, '0', STR_PAD_LEFT);

if (!in_array($jenis, ['iplm', 'tkm'])) {
    die("Error: Jenis laporan tidak valid.");
}

// 2. SETUP JUDUL & NAMA FILE
$timestamp = date('Ymd_His');
$filename = "Rekap_{$jenis}_{$start_bln}{$start_thn}_sd_{$end_bln}{$end_thn}.xlsx"; 
$title_text = upper_str("Rekapitulasi Data " . $jenis);
$periode_text = upper_str("Periode: $start_bln/$start_thn s.d. $end_bln/$end_thn");

// 3. AMBIL PERTANYAAN (HEADER KOLOM DINAMIS)
// Penting: Ambil 'tipe_input' untuk konversi Likert nanti
$stmtSoal = $pdo->prepare("SELECT id, teks_pertanyaan, kategori_bagian, tipe_input FROM master_pertanyaan WHERE jenis_kuesioner = ? ORDER BY kategori_bagian ASC, urutan ASC");
$stmtSoal->execute([strtoupper($jenis)]);
$daftar_soal = $stmtSoal->fetchAll(PDO::FETCH_ASSOC);

// 4. AMBIL DATA RESPONDEN (HEADER)
$start_period = (int)($start_thn . $start_bln);
$end_period   = (int)($end_thn . $end_bln);

// [FIX] Select h.library_id explicitly
$sql = "SELECT h.id as header_id, h.periode_bulan, h.periode_tahun, h.library_id,
               l.nama as nama_perpus, l.jenis as jenis_perpus, l.kategori
        FROM trans_header h
        LEFT JOIN libraries l ON h.library_id = l.id
        WHERE h.jenis_kuesioner = :jenis
        AND (CAST(CONCAT(h.periode_tahun, h.periode_bulan) AS UNSIGNED) >= :start_p)
        AND (CAST(CONCAT(h.periode_tahun, h.periode_bulan) AS UNSIGNED) <= :end_p)
        ORDER BY h.id ASC";

$stmtData = $pdo->prepare($sql);
$stmtData->execute([
    ':jenis'   => strtoupper($jenis),
    ':start_p' => $start_period,
    ':end_p'   => $end_period
]);
$responden = $stmtData->fetchAll(PDO::FETCH_ASSOC);

// --- LOGIKA FILTER STATUS (IPLM ONLY) ---
if ($jenis === 'iplm' && in_array($status_filter, ['unfilled', 'all'])) {
    // 1. Ambil Semua Perpustakaan
    $stmtLib = $pdo->query("SELECT id, nama, kategori, jenis FROM libraries ORDER BY nama ASC");
    $all_libs = $stmtLib->fetchAll(PDO::FETCH_ASSOC);

    // 2. Generate Periode (YYYY-MM)
    $periods = [];
    $curr_ts = strtotime("$start_thn-$start_bln-01");
    $end_ts  = strtotime("$end_thn-$end_bln-01");
    
    while ($curr_ts <= $end_ts) {
        $p_month = date('m', $curr_ts);
        $p_year  = date('Y', $curr_ts);
        $periods[] = ['m' => $p_month, 'y' => $p_year];
        $curr_ts = strtotime("+1 month", $curr_ts);
    }

    // 3. Index Responden Existing [YYYYMM][lib_id]
    $existing_map = [];
    foreach ($responden as $r) {
        $key = $r['periode_tahun'] . $r['periode_bulan'];
        $existing_map[$key][$r['library_id']] = $r;
    }

    // 4. Construct Final Data
    $final_rows = [];
    foreach ($all_libs as $lib) {
        foreach ($periods as $p) {
            $p_key = $p['y'] . $p['m'];
            $is_filled = isset($existing_map[$p_key][$lib['id']]);
            
            // Logic Filter
            if ($status_filter === 'unfilled' && $is_filled) continue;
            if ($status_filter === 'filled' && !$is_filled) continue;

            if ($is_filled) {
                // Gunakan data yang sudah ada (hanya 1 row per periode per perpus)
                $final_rows[] = $existing_map[$p_key][$lib['id']];
            } else {
                // Buat row kosong
                $final_rows[] = [
                    'header_id' => null, 
                    'periode_bulan' => $p['m'],
                    'periode_tahun' => $p['y'],
                    'library_id' => $lib['id'],
                    'nama_perpus' => $lib['nama'],
                    'jenis_perpus' => $lib['jenis'],
                    'kategori' => $lib['kategori']
                ];
            }
        }
    }
    $responden = $final_rows;
}

// 5. AMBIL JAWABAN (DETAIL)
$all_header_ids = array_column($responden, 'header_id');
// Filter null values (dari unfilled rows)
$list_header_ids = array_filter($all_header_ids, function($v) { return !is_null($v) && $v > 0; });

$jawaban_map = [];

if (!empty($list_header_ids)) {
    $inQuery = implode(',', array_fill(0, count($list_header_ids), '?'));
    $stmtDetail = $pdo->prepare("SELECT header_id, pertanyaan_id, jawaban FROM trans_detail WHERE header_id IN ($inQuery)");
    // array_values untuk re-index karena array_filter mempertahankan keys
    $stmtDetail->execute(array_values($list_header_ids));
    while ($row = $stmtDetail->fetch(PDO::FETCH_ASSOC)) {
        $jawaban_map[$row['header_id']][$row['pertanyaan_id']] = $row['jawaban'];
    }
}

// ==========================================
// 6. MULAI MEMBUAT EXCEL
// ==========================================
$spreadsheet = new Spreadsheet();

// Grouping Data by Category (Only for IPLM, default to single group for TKM or if mixed)
$grouped_responden = [];
if ($jenis === 'iplm') {
    foreach ($responden as $row) {
        $kat = !empty($row['kategori']) ? strtoupper($row['kategori']) : 'LAINNYA';
        $grouped_responden[$kat][] = $row;
    }
    // Sort keys to have consistent order (e.g. SEKOLAH, UMUM)
    ksort($grouped_responden);
} else {
    // TKM or other types -> Single Sheet
    $grouped_responden['DATA'] = $responden;
}

$sheetIndex = 0;
foreach ($grouped_responden as $sheetTitle => $sheetData) {
    // Skip empty if we have multiple groups, unless it is the only one
    if (empty($sheetData) && count($grouped_responden) > 1) continue;

    // Use default sheet for first, create new for subsequent
    if ($sheetIndex === 0) {
        $sheet = $spreadsheet->getActiveSheet();
    } else {
        $sheet = $spreadsheet->createSheet($sheetIndex);
    }
    
    // Set Title (Limit 31 chars)
    $safeTitle = substr($sheetTitle, 0, 31);
    // Replace invalid chars for sheet title
    $safeTitle = str_replace(['*', ':', '/', '\\', '?', '[', ']'], '', $safeTitle);
    $sheet->setTitle($safeTitle ?: 'Sheet'.$sheetIndex);

    // --- A. Header Judul ---
    $sheet->setCellValue('A1', $title_text . " - " . $sheetTitle);
    $sheet->setCellValue('A2', $periode_text);
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A2')->getFont()->setItalic(true);

    // --- B. Setup Baris Header Tabel ---
    $row_head_1 = 4; // Baris Kategori
    $row_head_2 = 5; // Baris Pertanyaan

    // Kolom Identitas Dasar
    $sheet->setCellValue('A'.$row_head_1, upper_str('NO')); $sheet->mergeCells("A$row_head_1:A$row_head_2");
    $sheet->setCellValue('B'.$row_head_1, upper_str('PERIODE')); $sheet->mergeCells("B$row_head_1:B$row_head_2");
    
    $col = 'C'; 
    if ($jenis == 'iplm') {
        $sheet->setCellValue($col.$row_head_1, upper_str('NAMA PERPUSTAKAAN')); $sheet->mergeCells("{$col}{$row_head_1}:{$col}{$row_head_2}"); 
        $sheet->getColumnDimension($col)->setWidth(30); 
        $col++;
        
        $sheet->setCellValue($col.$row_head_1, upper_str('KATEGORI')); $sheet->mergeCells("{$col}{$row_head_1}:{$col}{$row_head_2}"); $col++;
        $sheet->setCellValue($col.$row_head_1, upper_str('JENIS')); $sheet->mergeCells("{$col}{$row_head_1}:{$col}{$row_head_2}"); $col++;
    }

    // --- C. Kolom Dinamis (Pertanyaan) ---
    $grouped_soal = [];
    foreach ($daftar_soal as $s) {
        $bag = $s['kategori_bagian'] ?: 'LAINNYA';
        $grouped_soal[$bag][] = $s;
    }

    $colors = ['FFFFE0B2', 'FFC8E6C9', 'FFBBDEFB', 'FFF8BBD0', 'FFE1BEE7']; 
    $color_idx = 0;

    foreach ($grouped_soal as $kategori => $items) {
        $jml_soal = count($items);
        
        // Header Atas (Kategori)
        $start_col = $col;
        for ($i = 1; $i < $jml_soal; $i++) $col++; 
        $end_col = $col;
        
        $sheet->setCellValue($start_col.$row_head_1, upper_str($kategori));
        if($start_col != $end_col) {
            $sheet->mergeCells("$start_col$row_head_1:$end_col$row_head_1");
        }
        
        // Warna Background Kategori
        $bg_color = $colors[$color_idx % count($colors)];
        $sheet->getStyle("$start_col$row_head_1:$end_col$row_head_1")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg_color);
        
        // Header Bawah (Pertanyaan)
        $curr = $start_col;
        foreach ($items as $item) {
            $sheet->setCellValue($curr.$row_head_2, upper_str($item['teks_pertanyaan']));
            $sheet->getColumnDimension($curr)->setWidth(20);
            $curr++;
        }
        
        $col++;
        $color_idx++;
    }
    $last_col = $sheet->getHighestColumn();

    // Style Header Utama
    $header_style = [
        'font' => ['bold' => true],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true
        ],
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];
    $sheet->getStyle("A$row_head_1:$last_col$row_head_2")->applyFromArray($header_style);

    // --- D. Isi Data (Rows) ---
    $row_num = $row_head_2 + 1;
    $no = 1;

    // Mapping Likert (Angka -> Huruf)
    $likert_map = [
        '1' => upper_str('Sangat Tidak Setuju'),
        '2' => upper_str('Tidak Setuju'),
        '3' => upper_str('Setuju'),
        '4' => upper_str('Sangat Setuju')
    ];

    if (empty($sheetData)) {
        $sheet->setCellValue('A'.$row_num, upper_str('Tidak ada data pada periode ini.'));
        $sheet->mergeCells("A$row_num:$last_col$row_num");
        $sheet->getStyle("A$row_num")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    } else {
        foreach ($sheetData as $row) {
            $sheet->setCellValue('A'.$row_num, $no++);
            $sheet->setCellValue('B'.$row_num, $row['periode_bulan'] . '/' . $row['periode_tahun']);
            
            $col = 'C'; 
            if ($jenis == 'iplm') {
                $sheet->setCellValue($col++.$row_num, upper_str($row['nama_perpus'] ?? '-'));
                $sheet->setCellValue($col++.$row_num, upper_str($row['kategori'] ?? '-'));
                $sheet->setCellValue($col++.$row_num, upper_str($row['jenis_perpus'] ?? '-'));
            }

            // Isi Jawaban
            foreach ($daftar_soal as $s) {
                $id_soal = $s['id'];
                $val = isset($jawaban_map[$row['header_id']][$id_soal]) ? $jawaban_map[$row['header_id']][$id_soal] : '-';
                
                // Konversi Angka Likert ke Huruf
                if ($s['tipe_input'] == 'likert' && isset($likert_map[$val])) {
                    $val = $likert_map[$val];
                }
                
                $sheet->setCellValueExplicit($col++.$row_num, upper_str((string)$val), DataType::TYPE_STRING);
            }
            $row_num++;
        }
    }

    // Beri Border ke Seluruh Data
    $last_row = $row_num - 1;
    if ($last_row >= $row_head_1) {
        $sheet->getStyle("A$row_head_1:$last_col$last_row")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A$row_head_1:B$last_row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); 
    }

    $sheetIndex++;
}

// Set First Sheet active
$spreadsheet->setActiveSheetIndex(0);

// 7. OUTPUT DOWNLOAD FILE
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
