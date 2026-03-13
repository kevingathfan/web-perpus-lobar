<?php
// web-perpus-v1/pustakawan/pilih_perpustakaan.php
session_start();
require '../config/database.php';

$ctx = $_SESSION['pustakawan_ctx'] ?? [];
if (isset($_GET['target'])) {
    $ctx['target'] = $_GET['target'];
    $_SESSION['pustakawan_ctx'] = $ctx;
    header("Location: pilih_perpustakaan.php");
    exit;
}
$target = $ctx['target'] ?? '../index.php';

// 1. Ambil Data Perpustakaan
$stmt = $pdo->query("SELECT id, nama, jenis FROM libraries ORDER BY nama ASC");
$libraries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. AMBIL MASTER KATEGORI DARI DATABASE
$stmtKat = $pdo->query("SELECT kategori, sub_kategori FROM master_kategori ORDER BY kategori ASC, sub_kategori ASC");
$rawKategori = $stmtKat->fetchAll(PDO::FETCH_ASSOC);

// Format array agar siap diubah jadi JSON untuk JavaScript
$strukturJenis = [];
foreach ($rawKategori as $row) {
    $strukturJenis[$row['kategori']][] = $row['sub_kategori'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pilih Identitas Perpustakaan - Royal GovTech</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/loader.css">
  <link rel="stylesheet" href="../assets/public-responsive.css">
  <style>
    :root {
        --primary: #0F52BA;
        --primary-dark: #0a3d8f;
        --border: #e2e8f0;
        --text-main: #0f172a;
        --text-muted: #64748b;
    }
    body { 
        background-color: #f8fafc; 
        font-family: 'Plus Jakarta Sans', sans-serif; 
        display: flex; 
        align-items: center; 
        min-height: 100vh; 
        position: relative; 
        overflow-x: hidden; 
        margin: 0;
    }
    
    .bg-pattern {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        z-index: -1;
        background-image: 
            radial-gradient(circle at 10% 20%, rgba(15, 82, 186, 0.04) 0%, transparent 40%),
            radial-gradient(circle at 90% 80%, rgba(244, 196, 48, 0.04) 0%, transparent 40%),
            linear-gradient(#e2e8f0 1px, transparent 1px),
            linear-gradient(90deg, #e2e8f0 1px, transparent 1px);
        background-size: 100% 100%, 100% 100%, 40px 40px, 40px 40px;
        background-position: 0 0, 0 0, 0 0, 0 0;
        mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
    }

    .main-card {
        border-radius: 24px;
        border: 1px solid var(--border);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        transition: transform 0.3s ease;
    }

    .brand-logo { height: 40px !important; width: auto !important; }
    .page-title { font-weight: 800; letter-spacing: -1px; color: var(--text-main); }
    
    .form-label { font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); }
    
    .btn-primary-gov {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        border: none;
        padding: 14px;
        border-radius: 50px;
        font-weight: 700;
        color: white;
        box-shadow: 0 4px 12px rgba(15, 82, 186, 0.25);
        transition: all 0.3s ease;
    }
    .btn-primary-gov:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(15, 82, 186, 0.35);
    }
    .btn-primary-gov:disabled { opacity: 0.6; cursor: not-allowed; }

    .select2-container--bootstrap-5 .select2-selection {
        border-radius: 12px;
        padding: 6px 12px;
        border-color: var(--border);
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../config/loader.php'; ?>
  <div class="bg-pattern"></div>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="text-center mb-4">
          <div class="d-flex justify-content-center gap-3 mb-3">
              <img src="../assets/logo_lobar.png" alt="Logo Lobar" class="brand-logo">
              <img src="../assets/logo_disarpus.png" alt="Logo Disarpus" class="brand-logo">
          </div>
          <h3 class="page-title">IDENTITAS<br><span style="color:var(--primary)">PERPUSTAKAAN</span></h3>
          <p class="text-muted small">Silakan pilih data sesuai unit perpustakaan Anda</p>
      </div>

      <div class="main-card p-4 p-md-5">
          <form id="loginForm" method="POST">
            <input type="hidden" name="target" value="<?= htmlspecialchars($target) ?>">
            <input type="hidden" name="nama_perpus_text" id="input_nama_text">

            <div class="mb-4">
              <label class="form-label mb-2">1. Jenis Perpustakaan</label>
              <select id="select_jenis" name="kategori_utama" class="form-select shadow-sm" style="border-radius:12px; height: 50px;" required>
                <option value="">-- Pilih Jenis --</option>
                <?php foreach(array_keys($strukturJenis) as $jenis): ?>
                    <option value="<?= $jenis ?>"><?= $jenis ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-4">
              <label class="form-label mb-2">2. Sub Jenis Perpustakaan</label>
              <select id="select_subjenis" name="kategori_sub" class="form-select shadow-sm" style="border-radius:12px; height: 50px;" disabled required>
                <option value="">-- Pilih Jenis Dahulu --</option>
              </select>
            </div>

            <div class="mb-5">
              <label class="form-label mb-2">3. Nama Perpustakaan</label>
              <select name="library_id" id="select_nama" class="form-select" disabled required>
                <option value=""></option> 
              </select>
            </div>

            <button type="submit" class="btn btn-primary-gov w-100 mb-3" id="btnSubmit" disabled>
                LANJUTKAN KE FORMULIR <i class="bi bi-arrow-right ms-2"></i>
            </button>
            
            <div class="text-center">
                <a href="../index.php" class="text-decoration-none text-muted small fw-bold">
                    <i class="bi bi-chevron-left me-1"></i> Kembali ke Beranda
                </a>
            </div>
          </form>
      </div>
      
      <div class="text-center mt-4">
          <p class="text-muted" style="font-size: 0.75rem;">&copy; <?= date('Y') ?> Dinas Kearsipan & Perpustakaan Kab. Lombok Barat</p>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    const libraries = <?= json_encode($libraries) ?>;
    const strukturJenis = <?= json_encode($strukturJenis) ?>; 

    $(document).ready(function() {
        // Inisialisasi awal Select2 Nama
        $('#select_nama').select2({ 
            theme: 'bootstrap-5', 
            placeholder: '-- Pilih Sub Jenis Terlebih Dahulu --', 
            width: '100%' 
        });

        // 1. Ganti Jenis -> Reset Sub Jenis & Nama
        $('#select_jenis').change(function() {
            const jenis = $(this).val();
            const subDropdown = $('#select_subjenis');
            const namaDropdown = $('#select_nama');

            // Kosongkan Sub Jenis dulu
            subDropdown.empty();

            // RESET NAMA & TOMBOL
            namaDropdown.empty().append('<option value=""></option>');
            namaDropdown.prop('disabled', true);
            namaDropdown.select2({ 
                theme: 'bootstrap-5', 
                placeholder: '-- Pilih Sub Jenis Terlebih Dahulu --', 
                width: '100%' 
            });
            $('#btnSubmit').prop('disabled', true);

            // LOGIKA UTAMA PERBAIKAN:
            if (jenis && strukturJenis[jenis]) {
                // Jika Jenis Dipilih: Isi Sub Jenis
                subDropdown.append('<option value="">-- Pilih Sub Jenis --</option>');
                strukturJenis[jenis].forEach(sub => {
                    subDropdown.append(new Option(sub, sub));
                });
                subDropdown.prop('disabled', false);
            } else {
                // Jika Jenis KEMBALI KE DEFAULT (Kosong): 
                // Kembalikan placeholder Sub Jenis ke "Pilih Jenis Terlebih Dahulu"
                subDropdown.append('<option value="">-- Pilih Jenis Terlebih Dahulu --</option>');
                subDropdown.prop('disabled', true);
            }
        });

        // 2. Ganti Sub Jenis -> Filter Nama
        $('#select_subjenis').change(function() {
            const subJenis = $(this).val();
            const namaDropdown = $('#select_nama');
            namaDropdown.empty();

            if (subJenis) {
                let filteredLibs = libraries.filter(lib => lib.jenis === subJenis);
                namaDropdown.append('<option value=""></option>');
                
                if (filteredLibs.length > 0) {
                    filteredLibs.forEach(lib => {
                        namaDropdown.append(new Option(lib.nama, lib.id));
                    });
                    namaDropdown.prop('disabled', false);
                    // Update Placeholder Jadi "Cari Nama"
                    namaDropdown.select2({ 
                        theme: 'bootstrap-5', 
                        placeholder: '-- Cari Nama Perpustakaan --', 
                        width: '100%' 
                    });
                } else {
                    namaDropdown.prop('disabled', true);
                    namaDropdown.select2({ 
                        theme: 'bootstrap-5', 
                        placeholder: 'Tidak ada data perpustakaan', 
                        width: '100%' 
                    });
                }
            } else {
                // Jika Sub Jenis kembali ke default
                namaDropdown.prop('disabled', true);
                namaDropdown.select2({ 
                    theme: 'bootstrap-5', 
                    placeholder: '-- Pilih Sub Jenis Terlebih Dahulu --', 
                    width: '100%' 
                });
            }
        });

        // 3. Saat Nama Dipilih
        $('#select_nama').change(function() {
            if ($(this).val()) {
                $('#btnSubmit').prop('disabled', false);
                $('#input_nama_text').val($(this).find("option:selected").text());
            } else {
                $('#btnSubmit').prop('disabled', true);
                $('#input_nama_text').val('');
            }
        });

        // 4. Redirect
        $('#loginForm').on('submit', function(e) {
            const target = $('input[name="target"]').val();
            this.action = (target === 'iplm') ? 'kuisioner_iplm.php' : (target === 'tkm' ? 'kuisioner_tkm.php' : '../index.php');
        });
    });

</script>
<script src="../assets/loader.js"></script>
</body>
</html>
