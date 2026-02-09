# Web Perpus Lobar

Website monitoring dan layanan perpustakaan untuk Dinas Kearsipan dan Perpustakaan Lombok Barat.

**Ringkas Fitur**
- Portal publik dengan informasi layanan dan akses cepat ke area admin.
- Dashboard admin untuk statistik, pengelolaan admin, pengaduan, dan data kuesioner.
- Area pustakawan untuk pengisian kuesioner dan pemilihan perpustakaan.
- Ekspor data (Excel) menggunakan PhpSpreadsheet.
- Reset password admin via email (SMTP).

**Struktur Direktori**
- `admin/` Halaman dan fitur untuk admin.
- `pustakawan/` Halaman dan fitur untuk pustakawan.
- `assets/` Aset CSS/JS dan gambar.
- `config/` Konfigurasi aplikasi.
- `done_web_perpus_db.sql` Dump database utama.

**Persyaratan**
- PHP 8.x dengan ekstensi `pdo_pgsql` (PDO PostgreSQL).
- PostgreSQL 13+.
- Composer.
- Web server (Apache/Nginx). Direkomendasikan: Laragon (Windows).

**Instalasi Lokal (Laragon)**
1. Pastikan folder proyek berada di `C:\laragon\www\web-perpus-lobar`.
2. Install dependency:

```bash
composer install
```

3. Buat database PostgreSQL:
- Nama database default: `monitoring_perpus_db`.
- Bisa dibuat via pgAdmin atau psql.

4. Import dump database:
- Gunakan file `done_web_perpus_db.sql`.
- Di pgAdmin: klik kanan database -> Restore -> pilih file.

5. Atur kredensial database di `config/database.php`:
- `host`, `port`, `dbname`, `username`, `password`.

6. Atur SMTP dan URL aplikasi di `mail.php`:
- `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass`, `from_name`, `from_email`, `app_url`.
- `app_url` harus sesuai dengan URL lokal Anda, contoh: `http://localhost/web-perpus-lobar`.

7. Jalankan web server Laragon dan akses:
- Publik: `http://localhost/web-perpus-lobar/`
- Admin: `http://localhost/web-perpus-lobar/admin/login.php`
- Pustakawan: `http://localhost/web-perpus-lobar/pustakawan/`

**Akun Admin**
- Data admin tersimpan di tabel `users`.
- Jika menggunakan dump `done_web_perpus_db.sql`, akun admin sudah ada tetapi password terenkripsi.
- Jika belum bisa login, buat akun baru melalui halaman `admin/users.php` (menu Kelola Admin) setelah login, atau update langsung di database dengan password hash.
- Fitur Lupa Password membutuhkan SMTP yang sudah benar di `mail.php`.

**Catatan Database**
- Konfigurasi koneksi ada di `config/database.php`.
- Nama database default: `monitoring_perpus_db`.

**Troubleshooting**
- Error koneksi database: periksa `config/database.php` dan status PostgreSQL.
- Halaman kosong/500: cek log web server dan pastikan ekstensi `pdo_pgsql` aktif.
- Lupa password tidak mengirim email: pastikan SMTP di `mail.php` valid dan `app_url` benar.

**Lisensi**
Internal project.
