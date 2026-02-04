<?php
// web-perpus-v1/pustakawan/pilih_perpustakaan.php
require '../config/database.php';

$target = isset($_GET['target']) ? $_GET['target'] : '../index.php'; 

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
  <title>Pilih Identitas Perpustakaan</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/loader.css">
  <link rel="stylesheet" href="../assets/public-responsive.css">
  <style> body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; display: flex; align-items: center; min-height: 100vh; } .card { border-radius: 15px; border: 1px solid #ddd; box-shadow: 0 4px 6px rgba(0,0,0,0.05); } 
  </style>
</head>
<body>
  <?php include __DIR__ . '/../config/loader.php'; ?>

<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card p-4">
        <div class="card-body">
          <h4 class="text-center fw-bold mb-3">Identitas Perpustakaan</h4>
          <p class="text-center text-muted small mb-4">Lengkapi data di bawah ini untuk melanjutkan.</p>

          <form id="loginForm" method="GET">
            <input type="hidden" name="target" value="<?= htmlspecialchars($target) ?>">
            <input type="hidden" name="nama_perpus_text" id="input_nama_text">

            <div class="mb-3">
              <label class="form-label fw-bold">1. Jenis Perpustakaan</label>
              <select id="select_jenis" name="kategori_utama" class="form-select" required>
                <option value="">-- Pilih Jenis --</option>
                <?php foreach(array_keys($strukturJenis) as $jenis): ?>
                    <option value="<?= $jenis ?>"><?= $jenis ?></option>
                <?php endforeach; ?>
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
                <option value=""></option> 
              </select>
              <div class="form-text text-muted">Ketik nama perpustakaan untuk mencari.</div>
            </div>

            <button type="submit" class="btn btn-dark w-100 py-2 fw-bold" id="btnSubmit" disabled>LANJUTKAN &rarr;</button>
            <div class="text-center mt-3"><a href="../index.php" class="text-decoration-none text-muted small">Kembali ke Menu Awal</a></div>
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
