<?php
// web-perpus-v1/pustakawan/render_kuesioner.php

function render_dynamic_form($pdo, $jenis_kuesioner, $library_id, $defaults = []) {
    
    // 1. AMBIL DATA
    $stmt = $pdo->prepare("SELECT * FROM master_pertanyaan WHERE jenis_kuesioner = ? ORDER BY kategori_bagian ASC, urutan ASC");
    $stmt->execute([$jenis_kuesioner]);
    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$raw_data) {
        echo '<div class="alert alert-light border text-center py-5 shadow-sm" style="border-radius: 12px;">
                <div class="mb-3"><i class="bi bi-clipboard-x text-muted" style="font-size: 3rem;"></i></div>
                <h5 class="fw-bold text-dark">Data Kuesioner Belum Tersedia</h5>
                <p class="text-muted mb-0">Silakan hubungi administrator.</p>
              </div>';
        return;
    }

    // 2. GROUPING
    $pertanyaan = [];
    foreach ($raw_data as $row) {
        $bagian = $row['kategori_bagian'];
        if (!isset($pertanyaan[$bagian])) $pertanyaan[$bagian] = [];
        $pertanyaan[$bagian][] = $row;
    }

    // --- ASSETS ---
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">';

    // --- CSS MODERN PROFESSIONAL ---
    echo "
    <style>
        :root {
            --c-primary: #2c3e50;    /* Navy Formal */
            --c-accent: #34495e;     /* Slate */
            --c-bg: #f8f9fa;         /* Light Grey Background */
            --c-card: #ffffff;
            --c-border: #e9ecef;
            --c-focus: rgba(44, 62, 80, 0.15);
        }

        body { background-color: var(--c-bg); color: var(--c-primary); }

        /* Container Section */
        .section-card {
            background: var(--c-card);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); /* Soft shadow */
            border: 1px solid var(--c-border);
            margin-bottom: 2.5rem;
            overflow: hidden;
        }

        /* Header yang Elegan */
        .section-header {
            background: #fff;
            padding: 1.5rem 2rem;
            border-bottom: 2px solid var(--c-primary); /* Aksen garis tegas */
            display: flex; align-items: center; gap: 12px;
        }
        .section-title {
            font-size: 1.1rem; font-weight: 700; 
            text-transform: uppercase; letter-spacing: 0.8px; margin: 0;
            color: var(--c-primary);
        }

        /* Item Pertanyaan */
        .q-item {
            padding: 2rem;
            border-bottom: 1px solid var(--c-border);
            transition: background-color 0.3s;
        }
        .q-item:last-child { border-bottom: none; }
        .q-item:hover { background-color: #fafbfc; } /* Efek hover halus */

        /* Label Soal */
        .q-label {
            font-size: 1rem; font-weight: 600; color: #1a1a1a;
            margin-bottom: 0.5rem; display: block;
            line-height: 1.5;
        }
        .q-num {
            display: inline-block; min-width: 30px; 
            color: var(--c-accent); font-weight: 700;
        }
        .req-star { color: #dc3545; font-size: 0.8rem; vertical-align: top; margin-left: 2px; }

        /* Keterangan */
        .q-hint {
            font-size: 0.85rem; color: #6c757d;
            background: #f1f3f5; padding: 8px 12px;
            border-radius: 6px; margin-bottom: 1rem;
            display: inline-block;
        }

        /* Input Styles */
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #ced4da;
            font-size: 0.95rem;
            transition: all 0.2s ease-in-out;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--c-primary);
            box-shadow: 0 0 0 4px var(--c-focus); /* Fokus ring modern */
        }
        .form-control[readonly] {
            background-color: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
        }

        /* Custom Radio / Likert Cards */
        .opt-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
        }
        
        .btn-opt {
            display: block; width: 100%;
            padding: 12px 16px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            color: #495057;
            font-weight: 500; font-size: 0.9rem;
            cursor: pointer; text-align: center;
            transition: all 0.2s;
            position: relative; overflow: hidden;
        }
        
        /* State Hover */
        .btn-opt:hover {
            border-color: var(--c-accent);
            background-color: #f8f9fa;
        }

        /* State Checked (Terpilih) */
        .btn-check:checked + .btn-opt {
            background-color: var(--c-primary);
            color: #fff;
            border-color: var(--c-primary);
            box-shadow: 0 4px 6px rgba(44, 62, 80, 0.2);
            transform: translateY(-1px);
        }

        /* Tombol Submit Floating */
        .submit-container {
            margin-top: 3rem; padding: 2rem;
            background: transparent; text-align: center;
        }
        .btn-submit-modern {
            background: var(--c-primary); color: #fff;
            padding: 14px 40px; border-radius: 50px;
            font-weight: 600; letter-spacing: 1px;
            border: none; font-size: 1rem;
            box-shadow: 0 10px 20px rgba(44, 62, 80, 0.15);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-submit-modern:hover {
            background: #1a252f;
            transform: translateY(-2px);
            box-shadow: 0 15px 25px rgba(44, 62, 80, 0.25);
        }
    </style>
    ";

    // --- FORM START ---
    echo '<form id="formKuesioner" method="POST" action="../proses_simpan.php" class="needs-validation" novalidate>';
    echo '<input type="hidden" name="jenis_kuesioner" value="'.$jenis_kuesioner.'">';
    echo '<input type="hidden" name="library_id" value="'.$library_id.'">';

    // --- LOOP KATEGORI ---
    foreach ($pertanyaan as $kategori => $items) {
        echo '<div class="section-card">';
        
        // Header Bagian
        echo '<div class="section-header">
                <i class="bi bi-bookmark-check-fill fs-4 text-secondary"></i>
                <h2 class="section-title">'.$kategori.'</h2>
              </div>';
        
        echo '<div>'; // Wrapper Konten

        foreach ($items as $index => $p) {
            $id = $p['id'];
            $label = htmlspecialchars($p['teks_pertanyaan']);
            $tipe = $p['tipe_input'];
            $keterangan = htmlspecialchars($p['keterangan'] ?? '');
            
            // Auto-fill Logic
            $val = isset($defaults[$label]) ? $defaults[$label] : '';
            $readonly = ($val !== '') ? 'readonly' : '';

            // Parsing Opsi (Jika ada, pisahkan koma)
            $opsi_custom = [];
            if (!empty($p['pilihan_opsi'])) {
                $opsi_custom = array_map('trim', explode(',', $p['pilihan_opsi']));
            }

            echo '<div class="q-item">';
            
            // 1. Label
            echo '<label class="q-label" for="inp_'.$id.'">';
            echo '<span class="q-num">'.($index+1).'.</span> ' . $label;
            if (empty($readonly)) echo '<span class="req-star" title="Wajib diisi">*</span>';
            echo '</label>';

            // 2. Keterangan
            if (!empty($keterangan)) {
                echo '<div style="padding-left: 34px;"><div class="q-hint"><i class="bi bi-info-circle me-2"></i>'.$keterangan.'</div></div>';
            }

            // Wrapper Input (Indentasi Rapi)
            echo '<div style="padding-left: 34px;">';

            // --- RENDER TIPE INPUT ---
            
            // A. Text / Number
            if ($tipe == 'text' || $tipe == 'number') {
                echo '<input type="'.$tipe.'" id="inp_'.$id.'" name="jawaban['.$id.']" class="form-control" 
                       value="'.$val.'" '.$readonly.' required placeholder="Jawaban Anda...">';
            }
            
            // B. Textarea
            elseif ($tipe == 'textarea') {
                echo '<textarea id="inp_'.$id.'" name="jawaban['.$id.']" class="form-control" rows="3" required placeholder="Tuliskan jawaban lengkap...">'.$val.'</textarea>';
            }
            
            // C. Dropdown (Select)
            elseif ($tipe == 'select') {
                echo '<select id="inp_'.$id.'" name="jawaban['.$id.']" class="form-select" required>';
                echo '<option value="" selected disabled>-- Pilih Jawaban --</option>';
                
                // Gunakan Opsi Custom dari DB jika ada, jika tidak pakai Default Ya/Tidak
                $list_opsi = !empty($opsi_custom) ? $opsi_custom : ['Ya', 'Tidak'];
                
                foreach ($list_opsi as $opt) {
                    echo '<option value="'.$opt.'">'.$opt.'</option>';
                }
                echo '</select>';
            }
            
            // D. Radio Button & Likert (Tampilan Kartu/Grid)
            elseif ($tipe == 'radio' || $tipe == 'likert') {
                
                // Tentukan opsi
                if ($tipe == 'likert') {
                    $list_opsi = [
                        '1' => 'Sangat Tidak Setuju',
                        '2' => 'Tidak Setuju',
                        '3' => 'Setuju',
                        '4' => 'Sangat Setuju'
                    ];
                } else {
                    // Untuk Radio biasa, array key & value sama
                    $list_opsi = [];
                    foreach($opsi_custom as $oc) $list_opsi[$oc] = $oc;
                }

                echo '<div class="opt-grid">';
                foreach ($list_opsi as $val_opt => $label_opt) {
                    echo '
                    <div>
                        <input type="radio" class="btn-check" name="jawaban['.$id.']" id="opt_'.$id.'_'.$val_opt.'" value="'.$val_opt.'" required>
                        <label class="btn-opt" for="opt_'.$id.'_'.$val_opt.'">
                            '.$label_opt.'
                        </label>
                    </div>';
                }
                echo '</div>';
            }

            echo '</div>'; // End wrapper input
            echo '</div>'; // End q-item
        }
        echo '</div>'; // End wrapper content
        echo '</div>'; // End section card
    }

    // --- FOOTER ---
    echo '
    <div class="submit-container">
        <button type="button" onclick="konfirmasiKirim()" class="btn-submit-modern">
            <i class="bi bi-send-check me-2"></i> SIMPAN JAWABAN
        </button>
        <div class="mt-3 text-muted small">
            <i class="bi bi-shield-lock"></i> Data Anda tersimpan aman dan rahasia.
        </div>
    </div>
    
    </form>';

    // --- JS LOGIC ---
    echo "
    <script>
    function konfirmasiKirim() {
        const form = document.getElementById('formKuesioner');
        
        // 1. Validasi Native HTML5
        if (!form.checkValidity()) {
            form.reportValidity();
            // Scroll halus ke error pertama
            const invalid = form.querySelector(':invalid');
            if(invalid) {
                invalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Tambahkan efek visual pada card parent
                const parent = invalid.closest('.q-item');
                if(parent) {
                    parent.style.backgroundColor = '#fff0f0';
                    setTimeout(() => parent.style.backgroundColor = '', 2000);
                }
                invalid.focus();
            }
            return;
        }

        // 2. SweetAlert Formal
        Swal.fire({
            title: 'Konfirmasi Kirim',
            text: 'Pastikan seluruh jawaban sudah sesuai. Lanjutkan?',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#2c3e50',
            cancelButtonColor: '#95a5a6',
            confirmButtonText: 'Ya, Kirim Data',
            cancelButtonText: 'Periksa Lagi'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                form.submit();
            }
        });
    }
    </script>
    ";
}
?>