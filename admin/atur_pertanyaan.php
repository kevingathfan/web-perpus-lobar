<?php
// web-perpus-v1/admin/atur_pertanyaan.php
session_start();
require '../config/database.php';

// --- 1. PROSES CRUD ---
$pesan = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Tangkap Data
        $jenis = $_POST['jenis'];
        $bagian = $_POST['bagian'];
        $soal = $_POST['soal'];
        $keterangan = $_POST['keterangan'];
        $tipe = $_POST['tipe'];
        $urutan = $_POST['urutan'];
        // Tangkap Pilihan Opsi (hanya jika dropdown/radio)
        $pilihan_opsi = isset($_POST['pilihan_opsi']) ? $_POST['pilihan_opsi'] : '';

        if (isset($_POST['aksi']) && $_POST['aksi'] == 'tambah') {
            $sql = "INSERT INTO master_pertanyaan (jenis_kuesioner, kategori_bagian, teks_pertanyaan, keterangan, tipe_input, pilihan_opsi, urutan) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$jenis, $bagian, $soal, $keterangan, $tipe, $pilihan_opsi, $urutan]);
            $pesan = "Berhasil menambah pertanyaan baru!";
        } 
        elseif (isset($_POST['aksi']) && $_POST['aksi'] == 'edit') {
            $sql = "UPDATE master_pertanyaan SET jenis_kuesioner=?, kategori_bagian=?, teks_pertanyaan=?, keterangan=?, tipe_input=?, pilihan_opsi=?, urutan=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$jenis, $bagian, $soal, $keterangan, $tipe, $pilihan_opsi, $urutan, $_POST['id']]);
            $pesan = "Data pertanyaan berhasil diperbarui!";
        } 
        elseif (isset($_POST['aksi']) && $_POST['aksi'] == 'hapus') {
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
        .sidebar { min-height: 100vh; width: 260px; background-color: #ffffff; border-right: 1px solid #e0e0e0; position: fixed; top: 0; left: 0; padding: 40px 20px; z-index: 100; }
        .sidebar-header { font-weight: 800; font-size: 24px; margin-bottom: 50px; color: #000; text-align: center; letter-spacing: 1px; }
        .nav-link { color: #666; font-weight: 600; font-size: 15px; padding: 12px 20px; margin-bottom: 8px; border-radius: 8px; transition: all 0.3s; display: flex; align-items: center; gap: 10px; }
        .nav-link:hover, .nav-link.active { background-color: #000; color: #fff; }
        .main-content { margin-left: 260px; padding: 40px 50px; }
        .card-custom { background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 16px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
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
            <a href="pengaduan.php" class="nav-link"><i class="bi bi-chat-left-text"></i> PENGADUAN</a>
            <div class="mt-5 pt-5 border-top"><a href="../index.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> KELUAR</a></div>
        </div>
    </nav>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div><h2 class="fw-bold m-0">Manajemen Pertanyaan</h2><p class="text-muted m-0">Kelola soal kuesioner IPLM & TKM</p></div>
            <button class="btn btn-dark rounded-pill px-4 fw-bold" onclick="bukaModalTambah()"><i class="bi bi-plus-lg me-2"></i> Tambah Soal</button>
        </div>

        <?php if($pesan): ?><div class="alert alert-success alert-dismissible fade show"><?= $pesan ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

        <div class="card card-custom p-4">
            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-iplm">IPLM (Data Statistik)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tkm">TKM (Survei Perilaku)</button></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-iplm"><?php renderTable($data_iplm); ?></div>
                <div class="tab-pane fade" id="tab-tkm"><?php renderTable($data_tkm); ?></div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="modalForm" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="modalTitle">Form Pertanyaan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="aksi" id="form_aksi" value="tambah">
                        <input type="hidden" name="id" id="form_id">
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Jenis Kuesioner</label>
                                <select name="jenis" id="form_jenis" class="form-select" required>
                                    <option value="IPLM">IPLM</option>
                                    <option value="TKM">TKM</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Kategori / Bagian</label>
                                <input type="text" name="bagian" id="form_bagian" class="form-control" required placeholder="Contoh: IDENTITAS RESPONDEN">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Teks Pertanyaan</label>
                                <textarea name="soal" id="form_soal" class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Keterangan / Penjelasan (Opsional)</label>
                                <textarea name="keterangan" id="form_keterangan" class="form-control" rows="2" placeholder="Muncul kecil di bawah pertanyaan (miring)"></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tipe Input</label>
                                <select name="tipe" id="form_tipe" class="form-select" onchange="toggleOpsiInput()">
                                    <option value="text">Teks Pendek (Nama, Alamat)</option>
                                    <option value="number">Angka (Umur, Jumlah)</option>
                                    <option value="textarea">Teks Panjang (Saran)</option>
                                    <option value="likert">Skala Likert (Setuju - Tidak Setuju)</option>
                                    <option value="select">Dropdown (Pilihan Ganda)</option>
                                    <option value="radio">Radio Button (Pilihan Ganda)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nomor Urut</label>
                                <input type="number" name="urutan" id="form_urutan" class="form-control" value="1">
                            </div>

                            <div class="col-12" id="box_opsi" style="display:none;">
                                <div class="p-3 bg-light border rounded">
                                    <label class="form-label fw-bold text-primary">Opsi Jawaban (Pisahkan dengan koma)</label>
                                    <input type="text" name="pilihan_opsi" id="form_pilihan_opsi" class="form-control border-primary" placeholder="Contoh: < 20 Tahun, 21-30 Tahun, 31-40 Tahun, > 40 Tahun">
                                    <small class="text-muted">Masukkan pilihan jawaban dipisahkan tanda koma (,)</small>
                                </div>
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

    <?php function renderTable($dataset) { ?>
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
                <thead>
                    <tr class="table-light">
                        <th width="5%" class="text-center">No</th>
                        <th width="20%">Kategori</th>
                        <th>Pertanyaan</th>
                        <th width="15%">Tipe</th>
                        <th width="10%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($dataset)): ?><tr><td colspan="5" class="text-center py-4">Data kosong</td></tr><?php else: ?>
                    <?php foreach($dataset as $row): ?>
                        <tr>
                            <td class="text-center fw-bold"><?= $row['urutan'] ?></td>
                            <td><small class="fw-bold text-muted"><?= htmlspecialchars($row['kategori_bagian']) ?></small></td>
                            <td>
                                <div><?= htmlspecialchars($row['teks_pertanyaan']) ?></div>
                                <?php if($row['keterangan']): ?><small class="text-muted fst-italic">Ket: <?= htmlspecialchars($row['keterangan']) ?></small><?php endif; ?>
                                <?php if(($row['tipe_input'] == 'select' || $row['tipe_input'] == 'radio') && $row['pilihan_opsi']): ?>
                                    <div class="mt-1"><span class="badge bg-info text-dark">Opsi: <?= htmlspecialchars($row['pilihan_opsi']) ?></span></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= strtoupper($row['tipe_input']) ?></span></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-warning me-1" onclick='editData(<?= json_encode($row) ?>)'><i class="bi bi-pencil-square"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                                    <input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    <?php } ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalForm = new bootstrap.Modal(document.getElementById('modalForm'));

        function toggleOpsiInput() {
            const tipe = document.getElementById('form_tipe').value;
            const box = document.getElementById('box_opsi');
            // Tampilkan kotak opsi jika tipe adalah Select atau Radio
            if(tipe === 'select' || tipe === 'radio') {
                box.style.display = 'block';
            } else {
                box.style.display = 'none';
            }
        }

        function bukaModalTambah() {
            document.getElementById('modalTitle').innerText = 'Tambah Pertanyaan Baru';
            document.getElementById('form_aksi').value = 'tambah';
            document.getElementById('form_id').value = '';
            
            // Reset Form
            document.getElementById('form_jenis').value = 'IPLM';
            document.getElementById('form_bagian').value = '';
            document.getElementById('form_soal').value = '';
            document.getElementById('form_keterangan').value = '';
            document.getElementById('form_tipe').value = 'text';
            document.getElementById('form_pilihan_opsi').value = '';
            document.getElementById('form_urutan').value = '';
            
            toggleOpsiInput();
            modalForm.show();
        }

        function editData(data) {
            document.getElementById('modalTitle').innerText = 'Edit Pertanyaan';
            document.getElementById('form_aksi').value = 'edit';
            document.getElementById('form_id').value = data.id;

            document.getElementById('form_jenis').value = data.jenis_kuesioner;
            document.getElementById('form_bagian').value = data.kategori_bagian;
            document.getElementById('form_soal').value = data.teks_pertanyaan;
            document.getElementById('form_keterangan').value = data.keterangan || '';
            document.getElementById('form_tipe').value = data.tipe_input;
            document.getElementById('form_pilihan_opsi').value = data.pilihan_opsi || '';
            document.getElementById('form_urutan').value = data.urutan;

            toggleOpsiInput(); // Cek apakah field opsi perlu ditampilkan
            modalForm.show();
        }
    </script>
</body>
</html>