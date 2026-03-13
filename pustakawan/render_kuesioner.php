<?php
// web-perpus-v1/pustakawan/render_kuesioner.php

function render_dynamic_form($pdo, $jenis_kuesioner, $library_id, $defaults = []) {
    require_once __DIR__ . '/../config/public_security.php';
    
    // 1. AMBIL DATA
    $stmt = $pdo->prepare("SELECT m.* FROM master_pertanyaan m "
        . "LEFT JOIN kategori_bagian kb ON kb.jenis_kuesioner = m.jenis_kuesioner AND kb.name = m.kategori_bagian "
        . "WHERE m.jenis_kuesioner = ? "
        . "ORDER BY COALESCE(kb.position, 9999) ASC, m.kategori_bagian ASC, CAST(m.urutan AS UNSIGNED) ASC, m.id ASC");
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

    // --- AUTO-FILL SETTING IPLM (BERDASARKAN ID PERTANYAAN) ---
    $auto_ids = ['jenis' => null, 'subjenis' => null, 'nama' => null];
    if ($jenis_kuesioner === 'IPLM') {
        try {
            $stmtAuto = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('iplm_autofill_jenis_id','iplm_autofill_subjenis_id','iplm_autofill_nama_id')");
            $stmtAuto->execute();
            $autoRows = $stmtAuto->fetchAll(PDO::FETCH_KEY_PAIR);
            $auto_ids['jenis'] = !empty($autoRows['iplm_autofill_jenis_id']) ? (int)$autoRows['iplm_autofill_jenis_id'] : null;
            $auto_ids['subjenis'] = !empty($autoRows['iplm_autofill_subjenis_id']) ? (int)$autoRows['iplm_autofill_subjenis_id'] : null;
            $auto_ids['nama'] = !empty($autoRows['iplm_autofill_nama_id']) ? (int)$autoRows['iplm_autofill_nama_id'] : null;
        } catch (Exception $e) {}
    }

    // 2. GROUPING
    $pertanyaan = [];
    $kategori_order = []; // Track kategori untuk maintain order
    foreach ($raw_data as $row) {
        $bagian = $row['kategori_bagian'];
        if (!isset($pertanyaan[$bagian])) {
            $pertanyaan[$bagian] = [];
            $kategori_order[] = $bagian; // Track order
        }
        $pertanyaan[$bagian][] = $row;
    }
    
    // Reorder pertanyaan berdasarkan kategori_order
    $pertanyaan_ordered = [];
    foreach ($kategori_order as $bagian) {
        $pertanyaan_ordered[$bagian] = $pertanyaan[$bagian];
    }

    // --- ASSETS ---
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">';

    // --- CSS MODERN PROFESSIONAL (Royal GovTech) ---
    echo "
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        :root {
            --c-primary: #0F52BA;    /* Royal Blue */
            --c-primary-dark: #0a3d8f;
            --c-accent: #334155;     /* Slate 700 */
            --c-bg: #f8fafc;         /* Slate 50 */
            --c-card: #ffffff;
            --c-border: #e2e8f0;
            --c-focus: rgba(15, 82, 186, 0.15);
        }

        body { 
            background: transparent;
            color: var(--c-accent); 
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 16px;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        /* Branding Header */
        .form-branding {
            text-align: center;
            margin-bottom: 3rem;
        }
        .brand-logo-small { height: 40px; margin: 0 8px; }

        /* Container Section */
        .section-card {
            background: var(--c-card);
            border-radius: 20px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -2px rgba(0,0,0,0.02);
            border: 1px solid var(--c-border);
            margin-bottom: 2.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .section-card:hover {
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.08);
        }

        /* Header yang Elegan */
        .section-header {
            background: #fff;
            padding: 1.5rem 2.5rem;
            border-bottom: 1px solid var(--c-border);
            display: flex; align-items: center; gap: 16px;
            background: linear-gradient(to right, #ffffff, #f8fafc);
        }
        .section-title {
            font-size: 1.1rem; font-weight: 800; 
            letter-spacing: -0.5px; margin: 0;
            color: #0f172a;
            text-transform: uppercase;
        }

        /* Item Pertanyaan */
        .q-item {
            padding: 2.5rem;
            border-bottom: 1px solid var(--c-border);
            transition: background-color 0.2s ease;
        }
        .q-item:last-child { border-bottom: none; }
        .q-item:hover { background-color: #fcfdfe; }

        /* Label Soal */
        .q-label {
            font-size: 1.15rem; font-weight: 700; color: #0f172a;
            margin-bottom: 1rem; display: block;
            line-height: 1.6;
            letter-spacing: -0.3px;
        }
        .q-num {
            display: inline-block; min-width: 35px; 
            color: var(--c-primary); font-weight: 800;
        }
        .req-star { color: #dc2626; font-size: 0.8rem; vertical-align: top; margin-left: 2px; }

        /* Keterangan */
        .q-hint {
            font-size: 0.85rem; color: #475569;
            background: #f1f5f9; padding: 12px 18px;
            border-radius: 10px; margin-bottom: 1.5rem;
            display: inline-flex; align-items: center;
            border: 1px solid #e2e8f0;
            font-weight: 500;
        }

        /* Input Styles */
        .form-control, .form-select {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            font-size: 1rem;
            transition: all 0.2s ease-in-out;
            color: #1e293b;
            background-color: #ffffff;
            font-weight: 500;
        }
        .form-control:focus, .form-select:focus, .form-check-input:focus, .btn:focus {
            border-color: #cbd5e1 !important;
            box-shadow: none !important;
            outline: none !important;
        }
        .form-control[readonly] {
            background-color: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
            border-color: #e2e8f0;
        }

        /* Custom Radio / Likert Cards */
        .opt-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }
        
        .btn-opt {
            display: flex; align-items: center; justify-content: center;
            width: 100%; height: 100%; min-height: 60px;
            padding: 12px 20px;
            background: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            color: #475569;
            font-weight: 700; font-size: 0.95rem;
            cursor: pointer; text-align: center;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .btn-opt:hover {
            border-color: #cbd5e1;
            background-color: #f8fafc;
            color: #1e293b;
        }

        .btn-check:checked + .btn-opt {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            color: var(--c-primary);
            border-color: var(--c-primary);
            box-shadow: 0 4px 6px -1px rgba(15, 82, 186, 0.1);
            transform: translateY(-2px);
        }

        /* Tombol Submit Floating */
        .submit-container {
            margin-top: 4rem; padding-bottom: 5rem;
            text-align: center;
        }
        .btn-submit-modern {
            background: linear-gradient(135deg, var(--c-primary) 0%, var(--c-primary-dark) 100%);
            color: #fff;
            padding: 18px 60px; border-radius: 50px;
            font-weight: 800; letter-spacing: 0.5px;
            border: none; font-size: 1.1rem;
            box-shadow: 0 10px 20px rgba(15, 82, 186, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .btn-submit-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 30px rgba(15, 82, 186, 0.4);
        }

        /* Question Navigator (Minimap) */
        .q-navigator {
            position: fixed;
            top: 20px;
            right: 15px;
            width: 240px; /* Dipersempit agar tidak menutupi kuesioner */
            background: white;
            border-radius: 12px;
            border: 1px solid var(--c-border);
            padding: 0;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 40px);
            overflow: hidden;
        }
        .nav-header {
            font-size: 0.6rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            background: #fff;
            border-bottom: 1px solid #f8fafc;
        }
        .nav-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 4px;
            overflow-y: scroll; 
            overflow-x: hidden;
            padding: 10px 10px 15px 15px;
            height: 250px; /* Menampilkan sekitar 10 baris dengan ukuran dot baru */
        }
        /* Scrollbar Styling yang lebih interaktif */
        .nav-grid::-webkit-scrollbar {
            width: 4px;
        }
        .nav-grid::-webkit-scrollbar-track {
            background: #f8fafc;
        }
        .nav-grid::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        .nav-grid::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        .nav-dot {
            width: 18px; /* Dot lebih kecil agar hemat ruang */
            height: 18px;
            border-radius: 4px;
            background: #f1f5f9;
            border: 1px solid var(--c-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.55rem;
            font-weight: 700;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .nav-dot:hover {
            border-color: var(--c-primary);
            color: var(--c-primary);
            transform: scale(1.1);
        }
        .nav-dot.filled {
            background: #dcfce7;
            border-color: #22c55e;
            color: #15803d;
        }
        .nav-dot.active {
            box-shadow: 0 0 0 3px rgba(15, 82, 186, 0.2);
            background: var(--c-primary);
            color: white;
            border-color: var(--c-primary);
        }
        .nav-header {
            font-size: 0.65rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }

        @media (max-width: 1300px) {
            .q-navigator { display: none; } /* Hide on smaller screens to avoid overlap */
        }

        @media (max-width: 768px) {
            .section-header { padding: 1.25rem 1.5rem; }
            .q-item { padding: 1.5rem; }
            .opt-grid { grid-template-columns: 1fr; }
            .btn-submit-modern { width: 100%; }
        }
    </style>
    ";

    // --- FORM START ---
    echo '<div class="form-branding">
            <div class="mb-3">
                <img src="../assets/logo_lobar.png" class="brand-logo-small">
                <img src="../assets/logo_disarpus.png" class="brand-logo-small">
            </div>
          </div>';
    echo '<div class="d-flex justify-content-end mb-4">
            <button type="button" onclick="handleClearForm()" class="btn btn-sm btn-outline-danger shadow-sm px-3" style="border-radius: 50px; font-weight: 700;">
                <i class="bi bi-trash3-fill me-1"></i> Kosongkan Formulir
            </button>
          </div>';
    
    echo '<form id="formKuesioner" method="POST" action="../proses_simpan.php" class="needs-validation" novalidate>';
    echo '<input type="hidden" name="csrf_token" value="'.public_csrf_token().'">';
    echo '<input type="hidden" name="jenis_kuesioner" value="'.$jenis_kuesioner.'">';
    echo '<input type="hidden" name="library_id" value="'.$library_id.'">';

    echo '<script>
    function handleClearForm() {
        Swal.fire({
            title: "Kosongkan Formulir?",
            text: "Seluruh jawaban yang telah Anda isi akan dihapus permanen.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Ya, Kosongkan",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                if(window.clearSurveyDraft) window.clearSurveyDraft();
                document.getElementById("formKuesioner").reset();
                // Untuk radio buttons/likert yang dicustom dengan CSS, butuh uncheck manual jika reset tidak trigger
                document.querySelectorAll(".btn-check").forEach(radio => radio.checked = false);
                window.location.reload(); // Reload untuk memastikan state bersih total
            }
        });
    }
    </script>';

    // --- LOOP KATEGORI ---
    // --- LOOP KATEGORI & COLLECT ID ---
    $nomor_soal = 1;
    $navigator_data = [];
    foreach ($pertanyaan_ordered as $kategori => $items) {
        foreach ($items as $p) {
            $navigator_data[] = [
                'num' => $nomor_soal++,
                'id' => $p['id']
            ];
        }
    }

    // Render Navigator
    echo '<div class="q-navigator">
            <div class="nav-header">
                <span>Peta Navigasi Soal</span>
            </div>
            <div class="nav-grid">';
            foreach($navigator_data as $nav) {
                echo '<a href="javascript:void(0)" onclick="jumpTo('.$nav['id'].')" class="nav-dot" id="nav_dot_'.$nav['id'].'">'.$nav['num'].'</a>';
            }
    echo '  </div>
          </div>';

    $nomor_soal = 1; // Reset for actual form render
    foreach ($pertanyaan_ordered as $kategori => $items) {
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
            if ($val === '' && $jenis_kuesioner === 'IPLM') {
                if ($auto_ids['jenis'] && (int)$id === (int)$auto_ids['jenis']) {
                    $val = $defaults['core_jenis'] ?? '';
                } elseif ($auto_ids['subjenis'] && (int)$id === (int)$auto_ids['subjenis']) {
                    $val = $defaults['core_subjenis'] ?? '';
                } elseif ($auto_ids['nama'] && (int)$id === (int)$auto_ids['nama']) {
                    $val = $defaults['core_nama'] ?? '';
                }
            }
            if ($val === '' && $jenis_kuesioner === 'IPLM') {
                $label_raw = strtolower(trim($p['teks_pertanyaan']));
                if (strpos($label_raw, 'sub jenis') !== false || strpos($label_raw, 'subjenis') !== false) {
                    $val = $defaults['core_subjenis'] ?? '';
                } elseif (strpos($label_raw, 'jenis perpustakaan') !== false) {
                    $val = $defaults['core_jenis'] ?? '';
                } elseif (strpos($label_raw, 'nama perpustakaan') !== false) {
                    $val = $defaults['core_nama'] ?? '';
                }
            }
            $readonly = ($val !== '') ? 'readonly' : '';

            // Parsing Opsi (Jika ada, pisahkan koma)
            $opsi_custom = [];
            if (!empty($p['pilihan_opsi'])) {
                $opsi_custom = array_map('trim', explode(',', $p['pilihan_opsi']));
            }

            echo '<div class="q-item" id="q_wrapper_'.$id.'">';
            
            // 1. Label
            echo '<label class="q-label" for="inp_'.$id.'">';
            echo '<span class="q-num">'.$nomor_soal.'.</span> ' . $label;
            if (empty($readonly)) echo '<span class="req-star" title="Wajib diisi">*</span>';
            echo '</label>';
            $nomor_soal++; // Increment nomor soal global

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
                    $opt_safe = htmlspecialchars($opt, ENT_QUOTES);
                    echo '<option value="'.$opt_safe.'">'.$opt_safe.'</option>';
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
                    $val_safe = htmlspecialchars((string)$val_opt, ENT_QUOTES);
                    $label_safe = htmlspecialchars((string)$label_opt, ENT_QUOTES);
                    $id_safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)$val_opt);
                    echo '
                    <div>
                        <input type="radio" class="btn-check" name="jawaban['.$id.']" id="opt_'.$id.'_'.$id_safe.'" value="'.$val_safe.'" required>
                        <label class="btn-opt" for="opt_'.$id.'_'.$id_safe.'">
                            '.$label_safe.'
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
    document.addEventListener('DOMContentLoaded', function() {
        // --- Auto-Scroll to Next Question ---
        const form = document.getElementById('formKuesioner');
        
        form.addEventListener('change', function(e) {
            if (e.target.matches('input[type=\"radio\"]') || e.target.tagName === 'SELECT') {
                scrollToNext(e.target);
            }
        });

        form.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                if (e.target.matches('input[type=\"text\"], input[type=\"number\"]')) {
                    e.preventDefault();
                    scrollToNext(e.target, true);
                }
            }
        });

        function scrollToNext(currentElement, focusNext = false) {
            const currentItem = currentElement.closest('.q-item');
            if (!currentItem) return;

            let nextItem = currentItem.nextElementSibling;
            if (!nextItem) {
                const currentCard = currentItem.closest('.section-card');
                if (currentCard) {
                    let nextCard = currentCard.nextElementSibling;
                    while (nextCard && !nextCard.classList.contains('section-card')) {
                        nextCard = nextCard.nextElementSibling;
                    }
                    if (nextCard) {
                        nextItem = nextCard.querySelector('.q-item');
                    } else {
                        nextItem = document.querySelector('.submit-container');
                    }
                }
            }

            if (nextItem) {
                const scrollDelay = focusNext ? 0 : 400;
                setTimeout(() => {
                    nextItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (focusNext) {
                        const nextInput = nextItem.querySelector('input:not([type=\"radio\"]):not([type=\"hidden\"]), select, textarea');
                        if (nextInput) {
                            nextInput.focus({ preventScroll: true });
                        }
                    }
                }, scrollDelay);
            }
        }

        // --- Perisistence Logic (localStorage) ---
        const gJenis = document.querySelector('input[name=\"jenis_kuesioner\"]');
        const gLib = document.querySelector('input[name=\"library_id\"]');
        if (!gJenis || !gLib) return;

        const storageKey = 'survey_draft_' + gJenis.value + '_' + gLib.value;
        
        // Restore from storage
        const savedData = JSON.parse(localStorage.getItem(storageKey) || '{}');
        Object.keys(savedData).forEach(name => {
            const input = form.querySelector(\"[name=\\\"\" + name + \"\\\"]\");
            if (!input) return;

            if (input.type === 'radio') {
                const targetRadio = form.querySelector(\"[name=\\\"\" + name + \"\\\"][value=\\\"\" + savedData[name] + \"\\\"]\");
                if (targetRadio) targetRadio.checked = true;
            } else {
                input.value = savedData[name];
            }
        });

        // Save on change
        form.addEventListener('input', function(e) {
            if (e.target.name && e.target.name.startsWith('jawaban[')) {
                const currentDraft = JSON.parse(localStorage.getItem(storageKey) || '{}');
                currentDraft[e.target.name] = e.target.value;
                localStorage.setItem(storageKey, JSON.stringify(currentDraft));
            }
        });

        // Clear storage on submit
        window.clearSurveyDraft = () => localStorage.removeItem(storageKey);

        // --- Question Navigator Functions ---
        window.jumpTo = (id) => {
            const el = document.getElementById('q_wrapper_' + id);
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        };

        const updateNavStatus = () => {
            let filledCount = 0;
            const total = ' . count($navigator_data) . ';
            
            // Periksa setiap input jawaban
            document.querySelectorAll(\".q-item\").forEach(item => {
                const id = item.id.replace(\"q_wrapper_\", \"\");
                const dot = document.getElementById(\"nav_dot_\" + id);
                if (!dot) return;

                // Cek apakah ada input di dalamnya yang terisi
                const inputs = item.querySelectorAll(\"input, select, textarea\");
                let isFilled = false;

                inputs.forEach(inp => {
                    if (inp.type === \"radio\") {
                        if (inp.checked) isFilled = true;
                    } else {
                        if (inp.value.trim() !== \"\") isFilled = true;
                    }
                });

                if (isFilled) {
                    dot.classList.add(\"filled\");
                    filledCount++;
                } else {
                    dot.classList.remove(\"filled\");
                }
            });
        };

        // Trigger update on load & change
        setTimeout(updateNavStatus, 500);
        form.addEventListener(\"input\", updateNavStatus);
        form.addEventListener(\"change\", updateNavStatus);
    });

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
                if (window.clearSurveyDraft) window.clearSurveyDraft();
                form.submit();
            }
        });
    }
    </script>
    ";
}
?>
