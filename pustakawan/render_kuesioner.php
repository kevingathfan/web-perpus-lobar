<?php
// web-perpus-v1/pustakawan/render_kuesioner.php

function render_dynamic_form($pdo, $jenis_kuesioner, $library_id, $defaults = []) {
    
    // 1. AMBIL DATA DARI DB
    $stmt = $pdo->prepare("SELECT * FROM master_pertanyaan WHERE jenis_kuesioner = ? AND is_active = 1 ORDER BY kategori_bagian ASC, urutan ASC");
    $stmt->execute([$jenis_kuesioner]);
    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$raw_data) {
        echo '<div class="alert alert-warning">Belum ada pertanyaan. Silakan hubungi Admin.</div>';
        return;
    }

    // 2. GROUPING PERTANYAAN
    $pertanyaan = [];
    foreach ($raw_data as $row) {
        $bagian = $row['kategori_bagian'];
        if (!isset($pertanyaan[$bagian])) $pertanyaan[$bagian] = [];
        $pertanyaan[$bagian][] = $row;
    }

    // --- INCLUDE SWEETALERT (Untuk Pop-up) ---
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

    // --- FORM PEMBUKA ---
    // Tambahkan ID="formKuesioner" untuk diakses JavaScript
    echo '<form id="formKuesioner" method="POST" action="../proses_simpan.php">';
    echo '<input type="hidden" name="jenis_kuesioner" value="'.$jenis_kuesioner.'">';
    echo '<input type="hidden" name="library_id" value="'.$library_id.'">';

    foreach ($pertanyaan as $bagian => $items) {
        echo '<div class="mb-4 border p-4 rounded bg-white shadow-sm">';
        echo '<h5 class="fw-bold border-bottom pb-2 mb-3 text-uppercase">' . htmlspecialchars($bagian) . '</h5>';
        echo '<div class="row">';

        foreach ($items as $p) {
            $id_soal = isset($p['id']) ? $p['id'] : 0;
            $name = "jawaban[" . $id_soal . "]";
            $teks_soal = $p['teks_pertanyaan'];
            
            // Cek Kode Kunci (untuk auto-fill yang lebih aman)
            $kode_kunci = isset($p['kode_kunci']) ? $p['kode_kunci'] : '';
            
            // --- LOGIKA OTOMATIS & READONLY ---
            $val = '';
            $attr = '';
            $style = '';

            // Prioritas 1: Cek Kode Kunci (Misal: 'core_nama')
            if ($kode_kunci && isset($defaults[$kode_kunci])) {
                $val = htmlspecialchars($defaults[$kode_kunci]);
                $attr = 'readonly';
                $style = 'background-color: #e9ecef; cursor: not-allowed; font-weight:bold;';
            } 
            // Prioritas 2: Cek Teks Pertanyaan (Fallback lama)
            elseif (isset($defaults[$teks_soal])) {
                $val = htmlspecialchars($defaults[$teks_soal]);
                $attr = 'readonly';
                $style = 'background-color: #e9ecef; cursor: not-allowed; font-weight:bold;';
            }
            // ----------------------------------

            echo '<div class="col-md-12 mb-3">';
            
            if ($p['tipe_input'] == 'likert') {
                echo '<label class="form-label fw-bold mb-1">' . htmlspecialchars($teks_soal) . '</label>';
                echo '<div class="d-flex gap-3 mt-1 p-2 bg-light rounded border">';
                echo '<label><input type="radio" name="'.$name.'" value="1" required> Sangat Tidak Setuju</label>';
                echo '<label><input type="radio" name="'.$name.'" value="2"> Tidak Setuju</label>';
                echo '<label><input type="radio" name="'.$name.'" value="3"> Setuju</label>';
                echo '<label><input type="radio" name="'.$name.'" value="4"> Sangat Setuju</label>';
                echo '</div>';
            } 
            else {
                echo '<label class="form-label">' . htmlspecialchars($teks_soal) . '</label>';
                
                if ($p['tipe_input'] == 'textarea') {
                    echo '<textarea name="'.$name.'" class="form-control" rows="2" '.$attr.' style="'.$style.'">'.$val.'</textarea>';
                } else {
                    $type = ($p['tipe_input'] == 'number') ? 'number' : 'text';
                    $min_attr = ($type == 'number') ? 'min="0"' : '';
                    echo '<input type="'.$type.'" name="'.$name.'" class="form-control" value="'.$val.'" '.$min_attr.' '.$attr.' style="'.$style.'" required>';
                }
            }
            echo '</div>';
        }
        echo '</div></div>';
    }

    // --- TOMBOL KIRIM DENGAN KONFIRMASI ---
    // Ubah type="button" dan tambahkan onclick="konfirmasiKirim()"
    echo '<button type="button" onclick="konfirmasiKirim()" class="btn btn-dark w-100 py-3 fw-bold mt-3">KIRIM DATA</button>';
    echo '</form>';

    // --- SCRIPT JAVASCRIPT KONFIRMASI ---
    echo "
    <script>
    function konfirmasiKirim() {
        const form = document.getElementById('formKuesioner');
        
        // 1. Cek Validasi HTML5 (Required fields)
        if (!form.checkValidity()) {
            form.reportValidity(); // Tampilkan error bawaan browser (misal: 'Please fill out this field')
            return;
        }

        // 2. Tampilkan Pop-up SweetAlert
        Swal.fire({
            title: 'Apakah Anda Yakin?',
            text: 'Pastikan data yang Anda isi sudah benar. Data akan tersimpan untuk periode bulan ini.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#212529', // Warna Dark (Bootstrap)
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Kirim Data!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // 3. Jika User klik Ya, Submit Form
                Swal.fire({
                    title: 'Mengirim...',
                    text: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading() }
                });
                form.submit();
            }
        });
    }
    </script>
    ";
}
?>