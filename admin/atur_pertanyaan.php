<?php
// admin/atur_pertanyaan.php
session_start();
require '../config/database.php';

// --- 1. PROSES CRUD ---
$pesan = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['aksi']) && $_POST['aksi'] == 'tambah') {
            $sql = "INSERT INTO master_pertanyaan (jenis_kuesioner, kategori_bagian, teks_pertanyaan, tipe_input, urutan) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['jenis'], $_POST['bagian'], $_POST['soal'], $_POST['tipe'], $_POST['urutan']]);
            $pesan = "Berhasil menambah pertanyaan baru!";
        } elseif (isset($_POST['aksi']) && $_POST['aksi'] == 'edit') {
            $sql = "UPDATE master_pertanyaan SET jenis_kuesioner=?, kategori_bagian=?, teks_pertanyaan=?, tipe_input=?, urutan=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['jenis'], $_POST['bagian'], $_POST['soal'], $_POST['tipe'], $_POST['urutan'], $_POST['id']]);
            $pesan = "Data pertanyaan berhasil diperbarui!";
        } elseif (isset($_POST['aksi']) && $_POST['aksi'] == 'hapus') {
            $stmt = $pdo->prepare("DELETE FROM master_pertanyaan WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $pesan = "Pertanyaan berhasil dihapus.";
        }
        $_SESSION['flash_message'] = $pesan;
        header("Location: atur_pertanyaan.php"); exit;
    } catch (Exception $e) { $pesan = "Error: " . $e->getMessage(); }
}

if (isset($_SESSION['flash_message'])) { $pesan = $_SESSION['flash_message']; unset($_SESSION['flash_message']); }

// --- 2. AMBIL DATA ---
$stmt = $pdo->query("SELECT * FROM master_pertanyaan ORDER BY urutan ASC");
$all_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$data_iplm = array_filter($all_data, function($row) { return $row['jenis_kuesioner'] == 'IPLM'; });
$data_tkm  = array_filter($all_data, function($row) { return $row['jenis_kuesioner'] == 'TKM'; });
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Pertanyaan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        
        /* SIDEBAR (Sama persis dengan Dashboard) */
        .sidebar { min-height: 100vh; width: 260px; background-color: #ffffff; border-right: 1px solid #e0e0e0; position: fixed; top: 0; left: 0; padding: 40px 20px; z-index: 100; }
        .sidebar-header { font-weight: 800; font-size: 24px; margin-bottom: 50px; color: #000; text-align: center; letter-spacing: 1px; }
        .nav-link { color: #666; font-weight: 600; font-size: 15px; padding: 12px 20px; margin-bottom: 8px; border-radius: 8px; transition: all 0.3s; display: flex; align-items: center; gap: 10px; }
        .nav-link:hover, .nav-link.active { background-color: #000; color: #fff; }
        
        .main-content { margin-left: 260px; padding: 40px 50px; }
        .page-header h1 { font-weight: 800; font-size: 32px; color: #000; margin: 0; }
        .page-header p { font-size: 16px; color: #666; margin-top: 5px; }

        .card-custom { background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 16px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
        .table th { background-color: #f1f3f5; font-weight: 600; font-size: 14px; }
        .table td { font-size: 14px; vertical-align: middle; }
        .nav-tabs .nav-link { color: #666; font-weight: 600; border: none; border-bottom: 3px solid transparent; }
        .nav-tabs .nav-link.active { color: #000; border-bottom: 3px solid #000; background: transparent; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="sidebar-header">DISARPUS</div>
        <div class="nav flex-column">
            <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-fill"></i> DASHBOARD</a>
            <a href="perpustakaan.php" class="nav-link"><i class="bi bi-building"></i> PERPUSTAKAAN</a>
            <a href="atur_pertanyaan.php" class="nav-link active"><i class="bi bi-file-text"></i> KUISIONER</a>
            <a href="#" class="nav-link"><i class="bi bi-chat-left-text"></i> PENGADUAN</a>
            
            <div class="mt-5 pt-5 border-top">
                <a href="../index.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> KELUAR</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="page-header mb-5 d-flex justify-content-between align-items-center">
            <div>
                <h1>Atur Kuisioner</h1>
                <p>Tambah, edit, atau hapus pertanyaan untuk IPLM & TKM</p>
            </div>
            <button class="btn btn-dark fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-lg me-2"></i> Tambah Soal
            </button>
        </div>

        <?php if($pesan): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= $pesan ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card card-custom p-4">
            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                <li class="nav-item"><button class="nav-link active" id="iplm-tab" data-bs-toggle="tab" data-bs-target="#tab-iplm">IPLM (Data Statistik)</button></li>
                <li class="nav-item"><button class="nav-link" id="tkm-tab" data-bs-toggle="tab" data-bs-target="#tab-tkm">TKM (Survei Perilaku)</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-iplm"><?php renderTable($data_iplm); ?></div>
                <div class="tab-pane fade" id="tab-tkm"><?php renderTable($data_tkm); ?></div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">Tambah Pertanyaan Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="aksi" value="tambah">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Jenis Kuesioner</label>
                                <select name="jenis" class="form-select" required>
                                    <option value="IPLM">IPLM</option>
                                    <option value="TKM">TKM</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Bagian / Kategori</label>
                                <input type="text" name="bagian" class="form-control" placeholder="Contoh: III. KOLEKSI" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Teks Pertanyaan</label>
                                <textarea name="soal" class="form-control" rows="2" placeholder="Tulis pertanyaan disini..." required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tipe Input</label>
                                <select name="tipe" class="form-select">
                                    <option value="number">Angka (Untuk Data IPLM)</option>
                                    <option value="likert">Skala Likert 1-4 (Untuk Survei TKM)</option>
                                    <option value="text">Teks Pendek</option>
                                    <option value="textarea">Teks Panjang</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nomor Urut</label>
                                <input type="number" name="urutan" class="form-control" value="1">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary fw-bold">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEdit" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title fw-bold text-dark">Edit Pertanyaan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="aksi" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Jenis Kuesioner</label>
                                <select name="jenis" id="edit_jenis" class="form-select" required>
                                    <option value="IPLM">IPLM</option>
                                    <option value="TKM">TKM</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Bagian / Kategori</label>
                                <input type="text" name="bagian" id="edit_bagian" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Teks Pertanyaan</label>
                                <textarea name="soal" id="edit_soal" class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tipe Input</label>
                                <select name="tipe" id="edit_tipe" class="form-select">
                                    <option value="number">Angka (IPLM)</option>
                                    <option value="likert">Skala Likert (TKM)</option>
                                    <option value="text">Teks Pendek</option>
                                    <option value="textarea">Teks Panjang</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nomor Urut</label>
                                <input type="number" name="urutan" id="edit_urutan" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning fw-bold">Update Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php function renderTable($dataset) { ?>
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">Urut</th>
                        <th width="20%">Bagian</th>
                        <th>Pertanyaan</th>
                        <th width="10%">Tipe</th>
                        <th width="12%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($dataset)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data pertanyaan.</td></tr>
                    <?php else: ?>
                        <?php foreach($dataset as $row): ?>
                        <tr>
                            <td class="text-center fw-bold"><?= $row['urutan'] ?></td>
                            <td><small class="fw-bold text-muted"><?= htmlspecialchars($row['kategori_bagian']) ?></small></td>
                            <td><?= htmlspecialchars($row['teks_pertanyaan']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= $row['tipe_input'] ?></span></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-warning btn-edit" 
                                            data-id="<?= $row['id'] ?>" data-jenis="<?= $row['jenis_kuesioner'] ?>"
                                            data-bagian="<?= htmlspecialchars($row['kategori_bagian']) ?>"
                                            data-soal="<?= htmlspecialchars($row['teks_pertanyaan']) ?>"
                                            data-tipe="<?= $row['tipe_input'] ?>" data-urutan="<?= $row['urutan'] ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form method="POST" onsubmit="return confirm('Yakin ingin menghapus soal ini?')" style="display:inline;">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php } ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.btn-edit');
            const modalEdit = new bootstrap.Modal(document.getElementById('modalEdit'));
            editButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('edit_id').value = this.getAttribute('data-id');
                    document.getElementById('edit_jenis').value = this.getAttribute('data-jenis');
                    document.getElementById('edit_bagian').value = this.getAttribute('data-bagian');
                    document.getElementById('edit_soal').value = this.getAttribute('data-soal');
                    document.getElementById('edit_tipe').value = this.getAttribute('data-tipe');
                    document.getElementById('edit_urutan').value = this.getAttribute('data-urutan');
                    modalEdit.show();
                });
            });
        });
    </script>
</body>
</html>