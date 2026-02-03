<?php
// web-perpus-v1/pustakawan/form_pengaduan.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Pengaduan & Saran</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; padding: 40px 20px; }
        .card-form { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .form-control { border-radius: 8px; padding: 12px; }
        .btn-kirim { border-radius: 8px; padding: 12px; font-weight: bold; background-color: #198754; border:none; width: 100%; color: white;}
        .btn-kirim:hover { background-color: #146c43; }
        /* --- START PRELOADER CSS --- */

/* 1. Container Full Screen */
#global-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: #ffffff; /* Latar Putih Bersih */
    z-index: 99999; /* Pastikan di layer paling atas */
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.5s ease, visibility 0.5s ease;
}

/* 2. Wrapper Konten (Spinner + Teks) */
.loader-content {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* 3. Spinner Modern Ganda */
.spinner-brand {
    position: relative;
    width: 60px;
    height: 60px;
    margin-bottom: 15px;
}

.spinner-circle {
    position: absolute;
    width: 100%;
    height: 100%;
    border: 3px solid #e5e7eb; /* Abu-abu muda */
    border-top-color: #2c3e50; /* Warna Utama (Navy) */
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.spinner-circle-inner {
    position: absolute;
    top: 15px;
    left: 15px;
    width: 30px;
    height: 30px;
    border: 3px solid #e5e7eb;
    border-bottom-color: #34495e; /* Warna Aksen (Dark Slate) */
    border-radius: 50%;
    animation: spin-reverse 1.5s linear infinite;
}

/* 4. Teks Loading Berdenyut */
.loading-text {
    font-family: 'Segoe UI', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: 2px;
    color: #2c3e50;
    animation: pulse 1.5s ease-in-out infinite;
}

/* 5. Kelas Utilitas untuk Menyembunyikan Loader */
.loader-hidden {
    opacity: 0;
    visibility: hidden;
}

/* 6. Animasi Keyframes */
@keyframes spin { 
    100% { transform: rotate(360deg); } 
}
@keyframes spin-reverse { 
    100% { transform: rotate(-360deg); } 
}
@keyframes pulse { 
    0%, 100% { opacity: 1; } 
    50% { opacity: 0.5; } 
}

/* --- END PRELOADER CSS --- */
    </style>
</head>
<body>
    <div id="global-loader">
    <div class="loader-content">
        <div class="spinner-brand">
            <div class="spinner-circle"></div>
            <div class="spinner-circle-inner"></div>
        </div>
        <div class="loading-text">MEMUAT...</div>
    </div>
</div>

    <div class="container" style="max-width: 600px;">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Layanan Pengaduan & Saran</h3>
            <p class="text-muted">Masukan Anda sangat berarti bagi kemajuan layanan kami.</p>
        </div>

        <div class="card card-form p-4 bg-white">
            <form action="proses_pengaduan.php" method="POST">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Lengkap (Opsional)</label>
                    <input type="text" name="nama" class="form-control" placeholder="Boleh dikosongkan jika ingin anonim">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Kontak (Email / No. HP)</label>
                    <input type="text" name="kontak" class="form-control" placeholder="Agar kami bisa merespons (Opsional)">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Isi Pengaduan / Saran <span class="text-danger">*</span></label>
                    <textarea name="pesan" class="form-control" rows="5" required placeholder="Tuliskan laporan atau saran Anda di sini secara detail..."></textarea>
                </div>

                <button type="submit" class="btn-kirim">KIRIM LAPORAN</button>
            </form>
        </div>

        <div class="text-center mt-4">
            <a href="../index.php" class="text-decoration-none text-muted">&larr; Kembali ke Menu Utama</a>
        </div>
    </div>
    <script>
        /* --- START PRELOADER JS --- */

window.addEventListener("load", function () {
    const loader = document.getElementById("global-loader");
    
    // Beri sedikit jeda 0.5 detik agar animasi terlihat smooth
    setTimeout(function() {
        // Tambahkan kelas untuk memicu transisi CSS (opacity 0)
        loader.classList.add("loader-hidden");
        
        // Hapus elemen dari DOM setelah transisi selesai (agar tidak menghalangi klik)
        loader.addEventListener("transitionend", function() {
            if (loader.parentNode) {
                loader.parentNode.removeChild(loader);
            }
        });
    }, 500); 
});

/* --- END PRELOADER JS --- */
        </script>

</body>
</html>