<?php
// web-perpus-v1/pustakawan/pilih_perpustakaan.php
require '../config/database.php';

// 1. Tangkap parameter target dari URL (iplm atau tkm)
$target = isset($_GET['target']) ? $_GET['target'] : '../index.php'; 

// 2. Ambil SEMUA data perpustakaan dari Database
$stmt = $pdo->query("SELECT id, nama, jenis FROM libraries ORDER BY nama ASC");
$libraries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pilih Identitas Perpustakaan</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
      body { 
          background-color: #f8f9fa; 
          font-family: 'Poppins', sans-serif;
          display: flex; align-items: center; min-height: 100vh; 
      }
      .card { border-radius: 15px; border: 1px solid #ddd; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
      .select2-container--bootstrap-5 .select2-selection { border-color: #ced4da; }
      .form-control, .form-select, .btn { transition: all 0.3s; }
  </style>
</head>
<body>

<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card p-4">
        <div class="card-body">
          
          <h4 class="text-center fw-bold mb-3">Identitas Perpustakaan</h4>
          
          <p class="text-center text-muted small mb-4">
            <?php if($target == 'iplm'): ?>
                Anda akan mengisi <strong>Kuisioner IPLM</strong>.<br>Lengkapi data di bawah ini.
            <?php elseif($target == 'tkm'): ?>
                Anda akan mengisi <strong>Kuisioner TKM</strong>.<br>Lengkapi data di bawah ini.
            <?php else: ?>
                Silakan pilih perpustakaan untuk masuk.
            <?php endif; ?>
          </p>

          <form id="loginForm" method="GET">
            <input type="hidden" name="target" value="<?= htmlspecialchars($target) ?>">
            
            <input type="hidden" name="nama_perpus_text" id="input_nama_text">

            <div class="mb-3">
              <label class="form-label fw-bold">1. Jenis Perpustakaan</label>
              <select id="select_jenis" name="kategori_utama" class="form-select" required>
                <option value="">-- Pilih Jenis --</option>
                <option value="Umum">Perpustakaan Umum</option>
                <option value="Khusus">Perpustakaan Khusus</option>
                <option value="Sekolah">Perpustakaan Sekolah</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">2. Sub Jenis Perpustakaan</label>
              <select id="select_subjenis" name="kategori_sub" class="form-select" disabled required>
                <option value="">-- Pilih Jenis Terlebih Dahulu --</option>
              </select>
            </div>

            <div class="mb-4">
              <label class="form-label fw-bold">3. Nama Perpustakaan</label>
              <select name="library_id" id="select_nama" class="form-select" disabled required>
                <option value="">-- Pilih Sub Jenis Terlebih Dahulu --</option>
              </select>
              <div class="form-text text-muted">Ketik nama perpustakaan untuk mencari.</div>
            </div>

            <button type="submit" class="btn btn-dark w-100 py-2 fw-bold" id="btnSubmit" disabled>
              LANJUTKAN &rarr;
            </button>
            
            <div class="text-center mt-3">
                <a href="../index.php" class="text-decoration-none text-muted small">Batal / Kembali ke Menu Awal</a>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    const libraries = <?= json_encode($libraries) ?>;

    const strukturJenis = {
        "Umum": ["Komunitas", "Desa"],
        "Khusus": ["Rumah Ibadah", "Pondok Pesantren"],
        "Sekolah": ["TK", "SD", "SMP", "SMA"]
    };

    const dbMapping = {
        "Komunitas": "Perpustakaan Komunitas",
        "Desa": "Perpustakaan Desa",
        "Rumah Ibadah": "Perpustakaan Rumah Ibadah",
        "Pondok Pesantren": "Perpustakaan Pondok Pesantren",
        "TK": "Perpustakaan Sekolah",
        "SD": "Perpustakaan Sekolah",
        "SMP": "Perpustakaan Sekolah",
        "SMA": "Perpustakaan Sekolah"
    };

    $(document).ready(function() {
        
        $('#select_nama').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Pilih Sub Jenis Terlebih Dahulu --',
            allowClear: true,
            width: '100%'
        });

        // 1. Ganti Jenis -> Reset Sub Jenis & Nama
        $('#select_jenis').change(function() {
            const jenis = $(this).val();
            const subDropdown = $('#select_subjenis');
            const namaDropdown = $('#select_nama');

            subDropdown.empty().append('<option value="">-- Pilih Sub Jenis --</option>');
            namaDropdown.empty().append('<option value="">-- Pilih Sub Jenis Terlebih Dahulu --</option>');
            
            subDropdown.prop('disabled', true);
            namaDropdown.prop('disabled', true);
            $('#btnSubmit').prop('disabled', true);

            if (jenis && strukturJenis[jenis]) {
                strukturJenis[jenis].forEach(sub => {
                    subDropdown.append(new Option(sub, sub));
                });
                subDropdown.prop('disabled', false);
            }
            namaDropdown.trigger('change'); 
        });

        // 2. Ganti Sub Jenis -> Filter Nama
        $('#select_subjenis').change(function() {
            const subJenis = $(this).val();
            const namaDropdown = $('#select_nama');
            
            namaDropdown.empty();

            if (subJenis) {
                const targetDbJenis = dbMapping[subJenis];
                let filteredLibs = libraries.filter(lib => lib.jenis === targetDbJenis);

                if (["TK", "SD", "SMP", "SMA"].includes(subJenis)) {
                    const strictFilter = filteredLibs.filter(lib => lib.nama.toUpperCase().includes(subJenis));
                    if (strictFilter.length > 0) {
                        filteredLibs = strictFilter;
                    }
                }

                namaDropdown.append('<option value="">-- Cari Nama Perpustakaan --</option>');
                if (filteredLibs.length > 0) {
                    filteredLibs.forEach(lib => {
                        namaDropdown.append(new Option(lib.nama, lib.id));
                    });
                    namaDropdown.prop('disabled', false);
                    namaDropdown.select2({ theme: 'bootstrap-5', placeholder: '-- Cari Nama Perpustakaan --', allowClear: true, width: '100%' });
                } else {
                    namaDropdown.append('<option value="">Tidak ada data ditemukan</option>');
                    namaDropdown.prop('disabled', true);
                }
            } else {
                namaDropdown.append('<option value="">-- Pilih Sub Jenis Terlebih Dahulu --</option>');
                namaDropdown.prop('disabled', true);
            }
        });

        // 3. Saat Nama Dipilih -> Aktifkan Tombol & SIMPAN TEKS NAMA
        $('#select_nama').change(function() {
            if ($(this).val()) {
                $('#btnSubmit').prop('disabled', false);
                
                // --- UPDATE PENTING DI SINI ---
                // Ambil teks dari opsi yang dipilih dan masukkan ke input hidden
                var namaText = $(this).find("option:selected").text();
                $('#input_nama_text').val(namaText);
                
            } else {
                $('#btnSubmit').prop('disabled', true);
                $('#input_nama_text').val('');
            }
        });

        // 4. Redirect
        $('#loginForm').on('submit', function(e) {
            const target = $('input[name="target"]').val();
            if(target === 'iplm') {
                this.action = 'kuisioner_iplm.php';
            } else if (target === 'tkm') {
                this.action = 'kuisioner_tkm.php';
            } else {
                this.action = '../index.php'; 
            }
        });
    });
</script>

</body>
</html>