<?php
// web-perpus-v1/pustakawan/pilih_perpustakaan.php
require '../config/database.php';

// 1. Tangkap parameter target dari URL (iplm atau tkm)
$target = isset($_GET['target']) ? $_GET['target'] : 'dashboard'; 

// 2. Ambil SEMUA data perpustakaan untuk difilter nanti oleh Javascript
$stmt = $pdo->query("SELECT id, nama, jenis FROM libraries ORDER BY nama ASC");
$libraries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Tetapkan 5 Jenis Perpustakaan sesuai permintaan
$jenis_list = [
    'Perpustakaan Sekolah',
    'Perpustakaan Desa',
    'Perpustakaan Komunitas',
    'Perpustakaan Rumah Ibadah',
    'Perpustakaan Pondok Pesantren'
];
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

  <style>
      body { background-color: #f8f9fa; display: flex; align-items: center; min-height: 100vh; }
      .card { border-radius: 15px; border: 1px solid #ddd; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
      /* Custom Select2 Styles agar sesuai Bootstrap 5 */
      .select2-container--bootstrap-5 .select2-selection { border-color: #ced4da; }
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
                Anda akan mengisi <strong>Kuisioner IPLM</strong>.<br>Lengkapi data di bawah ini untuk melanjutkan.
            <?php elseif($target == 'tkm'): ?>
                Anda akan mengisi <strong>Kuisioner TKM</strong>.<br>Lengkapi data di bawah ini untuk melanjutkan.
            <?php else: ?>
                Silakan pilih perpustakaan untuk masuk ke Dashboard.
            <?php endif; ?>
          </p>

          <form id="loginForm" method="GET">
            <input type="hidden" name="target" value="<?= htmlspecialchars($target) ?>">

            <div class="mb-3">
              <label class="form-label fw-bold">1. Jenis Perpustakaan</label>
              <select id="select_jenis" class="form-select" required>
                <option value="">-- Pilih Jenis --</option>
                <?php foreach($jenis_list as $jenis): ?>
                  <option value="<?= htmlspecialchars($jenis) ?>"><?= htmlspecialchars($jenis) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-4">
              <label class="form-label fw-bold">2. Nama Perpustakaan</label>
              <select name="library_id" id="select_nama" class="form-select" disabled required>
                <option value="">-- Pilih Jenis Terlebih Dahulu --</option>
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
    // 1. Simpan data libraries dari PHP ke Variable JavaScript
    const libraries = <?= json_encode($libraries) ?>;

    $(document).ready(function() {
        
        // Inisialisasi Select2 pada dropdown Nama (Supaya ada fitur Search)
        $('#select_nama').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Pilih Jenis Terlebih Dahulu --',
            allowClear: true,
            width: '100%'
        });

        // 2. Logika saat Jenis Perpustakaan dipilih
        $('#select_jenis').change(function() {
            const selectedJenis = $(this).val();
            const namaDropdown = $('#select_nama');
            const submitBtn = $('#btnSubmit');

            // Kosongkan dropdown nama
            namaDropdown.empty();

            if (selectedJenis) {
                // Filter data libraries berdasarkan jenis yang dipilih
                // Pastikan data di database kolom 'jenis' SAMA PERSIS dengan 5 pilihan tadi
                const filteredLibs = libraries.filter(lib => lib.jenis === selectedJenis);

                // Masukkan opsi default untuk Select2 placeholder
                namaDropdown.append('<option value="">-- Cari Nama Perpustakaan --</option>');

                // Masukkan data hasil filter ke dropdown
                if (filteredLibs.length > 0) {
                    filteredLibs.forEach(lib => {
                        namaDropdown.append(new Option(lib.nama, lib.id));
                    });
                    
                    // Aktifkan dropdown
                    namaDropdown.prop('disabled', false);
                    // Update placeholder Select2
                    namaDropdown.select2({
                         theme: 'bootstrap-5',
                         placeholder: '-- Cari Nama Perpustakaan --',
                         allowClear: true,
                         width: '100%'
                    });
                    
                    submitBtn.prop('disabled', false); // Aktifkan tombol submit
                } else {
                    namaDropdown.append('<option value="">Tidak ada data untuk jenis ini</option>');
                    namaDropdown.prop('disabled', true);
                    submitBtn.prop('disabled', true);
                }

            } else {
                // Jika jenis di-reset ke kosong
                namaDropdown.append('<option value="">-- Pilih Jenis Terlebih Dahulu --</option>');
                namaDropdown.prop('disabled', true);
                submitBtn.prop('disabled', true);
                
                // Reset placeholder Select2
                namaDropdown.select2({
                     theme: 'bootstrap-5',
                     placeholder: '-- Pilih Jenis Terlebih Dahulu --',
                     width: '100%'
                });
            }

            // Trigger update Select2 agar tampilan berubah
            namaDropdown.trigger('change');
        });

        // 3. Logika Pengarahan Halaman (Redirect)
        $('#loginForm').on('submit', function(e) {
            const target = $('input[name="target"]').val();
            
            if(target === 'iplm') {
                this.action = 'kuisioner_iplm.php';
            } else if (target === 'tkm') {
                this.action = 'kuisioner_tkm.php';
            } else {
                this.action = 'dashboard.php';
            }
        });
    });
</script>

</body>
</html>