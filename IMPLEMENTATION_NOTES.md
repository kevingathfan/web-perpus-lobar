# Implementasi: Pemisahan Logika Urutan & Gaya Penomoran Global

## Ringkasan Perubahan

Telah diimplementasikan dua fitur utama untuk meningkatkan sistem penomoran kategori/bagian kuesioner:

### 1. **Pemisahan Logika Urutan (Domain Isolation)**

#### Masalah Sebelumnya:
- Penomoran kategori tidak terpisah per jenis kuesioner
- Jika IPLM memiliki 6 bagian, TKM akan melanjutkan dari nomor 7 (bukan dimulai dari 1)
- Query `SELECT MAX(position)` tidak di-filter berdasarkan `jenis_kuesioner`

#### Solusi Implementasi:
- **File:** `admin/atur_pertanyaan.php` (baris ~237)
- **Perubahan Query:**
  ```php
  // SEBELUM: SELECT COALESCE(MAX(position),0) FROM kategori_bagian
  // SESUDAH: SELECT COALESCE(MAX(position),0) FROM kategori_bagian WHERE jenis_kuesioner = ?
  
  $stmtPos = $pdo->prepare("SELECT COALESCE(MAX(position),0) FROM kategori_bagian WHERE jenis_kuesioner = ?");
  $stmtPos->execute([$jb]);
  $pos = (int)$stmtPos->fetchColumn() + 1;
  ```

#### Hasil:
- ✅ IPLM memiliki penomoran terpisah: 1, 2, 3, 4, 5, 6
- ✅ TKM memiliki penomoran terpisah: 1, 2, 3, 4, 5, 6
- ✅ Tidak ada benturan nomor antar jenis kuesioner

---

### 2. **Pengaturan Gaya Penomoran Global (Global Styling)**

#### Masalah Sebelumnya:
- Gaya penomoran disimpan per bagian (`numbering_style` kolom di `kategori_bagian`)
- Perubahan gaya harus dilakukan satu per satu untuk setiap bagian
- UI form tambah bagian memerlukan input style yang rumit

#### Solusi Implementasi:

##### A. Penghapusan UI Style per Bagian
- **File:** `admin/atur_pertanyaan.php` (baris ~879-925)
- Field `numbering_style` dihapus dari form "Tambah Bagian" untuk IPLM dan TKM
- Form edit bagian sekarang hanya menanyakan nama (tidak lagi style)

##### B. Tambahan Kontrol Global di Tab IPLM
- **File:** `admin/atur_pertanyaan.php` (setelah form auto-fill)
- Card baru "Gaya Penomoran Bagian" dengan dropdown:
  ```html
  <option value="numeric">Numerik (1, 2, 3, ...)</option>
  <option value="roman">Romawi (I, II, III, ...)</option>
  <option value="none">Tanpa Nomor</option>
  ```

##### C. Tambahan Kontrol Global di Tab TKM
- **File:** `admin/atur_pertanyaan.php` (di pane tab-tkm)
- Card identik untuk TKM dengan kontrol gaya penomoran terpisah

##### D. Backend Handler untuk Gaya Global
- **File:** `admin/atur_pertanyaan.php` (baris ~274-296)
- Handler POST `set_numbering_style` yang menyimpan setting ke tabel `settings`:
  ```php
  $setting_key = strtolower($jenis) . '_numbering_style'; // "iplm_numbering_style" atau "tkm_numbering_style"
  // Simpan ke tabel settings
  ```

##### E. Load Setting Global Saat Page Load
- **File:** `admin/atur_pertanyaan.php` (baris ~541-551)
- Query `SELECT setting_key, setting_value FROM settings` untuk memuat:
  - `iplm_numbering_style`
  - `tkm_numbering_style`

##### F. Update Logika Render Table
- **File:** `admin/atur_pertanyaan.php` (fungsi `renderTable`)
- Signature berubah dari:
  ```php
  function renderTable($dataset, $tab, $page, $total_pages, $other_page, $maxMap = [])
  ```
  Menjadi:
  ```php
  function renderTable($dataset, $tab, $page, $total_pages, $other_page, $maxMap = [], $globalNumberingStyle = 'numeric')
  ```
- Logika render sekarang menggunakan `$globalNumberingStyle` untuk semua kategori:
  ```php
  $style = $globalNumberingStyle; // Gunakan global style bukan per bagian
  if ($style === 'roman' && $p > 0) $prefix = int_to_roman($p) . '. ';
  elseif ($style === 'numeric' && $p > 0) $prefix = $p . '. ';
  else $prefix = '';
  ```

#### Hasil:
- ✅ Satu kontrol untuk mengatur gaya semua bagian di IPLM
- ✅ Satu kontrol untuk mengatur gaya semua bagian di TKM
- ✅ Perubahan style langsung berlaku ke semua kategori di bawahnya
- ✅ Pilihan style: Numerik, Romawi, atau Tanpa Nomor

---

## Perubahan di Database

### Tabel `kategori_bagian`
- Kolom `numbering_style` masih ada (untuk backward compatibility) tapi tidak digunakan
- Dapat dihapus di masa depan jika tidak perlu backward compatibility

### Tabel `settings`
- Dua setting baru ditambahkan (otomatis saat pertama kali submitted):
  - `iplm_numbering_style` (default: 'numeric')
  - `tkm_numbering_style` (default: 'numeric')

---

## Modifikasi Query Existing

### Query Pembacaan Kategori (Tetap sama):
```php
SELECT id, jenis_kuesioner, name, position FROM kategori_bagian 
ORDER BY jenis_kuesioner, position ASC
```

### Query Pembacaan Kelompok by Jenis:
```php
// IPLM
$kategori_iplm = $kategori_bagian WHERE jenis_kuesioner = 'IPLM'

// TKM  
$kategori_tkm = $kategori_bagian WHERE jenis_kuesioner = 'TKM'
```

---

## Testing Checklist

- [ ] Tambah kategori IPLM bagian baru → posisi harus increment dari IPLM (bukan melanjut dari TKM)
- [ ] Tambah kategori TKM bagian baru → posisi harus dimulai dari 1 (terpisah dari IPLM)
- [ ] Ubah gaya IPLM ke "Romawi" → semua bagian IPLM menampilkan I, II, III, dst
- [ ] Ubah gaya TKM ke "Tanpa Nomor" → semua bagian TKM tidak menampilkan nomor
- [ ] Edit nama bagian → hanya nama yang diupdate, gaya mengikuti setting global
- [ ] Hapus bagian → kategori hilang, gaya global tidak berubah
- [ ] Buka tab IPLM → dropdown menunjukkan gaya IPLM saat ini
- [ ] Buka tab TKM → dropdown menunjukkan gaya TKM saat ini
- [ ] Import CSV → posisi kategori tetap terpisah per jenis

---

## File yang Dimodifikasi

```
admin/atur_pertanyaan.php
├── Baris ~237: Fix query add_bagian dengan filter jenis_kuesioner
├── Baris ~247: Hapus numbering_style dari form add_bagian
├── Baris ~247: Update edit_bagian handler
├── Baris ~274-296: Tambah handler set_numbering_style
├── Baris ~541-551: Load setting global numbering_style
├── Baris ~720-744: Tambah kontrol global di tab IPLM
├── Baris ~809-832: Tambah kontrol global di tab TKM
├── Baris ~879-925: Hapus style field dari form modal IPLM & TKM
├── Fungsi renderTable: Update signature dan logika render
└── Fungsi openEditBagian (JS): Hapus parameter style
```

---

## Catatan Implementasi

1. **Backward Compatibility:** Kolom `numbering_style` di `kategori_bagian` masih ada tapi diabaikan. Setting global disimpan di tabel `settings`.

2. **CSS Styling:** Tidak ada perubahan CSS, semua kontrol menggunakan Bootstrap 5.3 yang sudah ada.

3. **JavaScript:** Minimal changes, hanya update function `openEditBagian()` untuk tidak lagi menanyakan style.

4. **Default Value:** Jika setting global belum ada, default adalah 'numeric'.

---

## Future Enhancements (Opsional)

1. Migrasi data: Hapus kolom `numbering_style` dari `kategori_bagian` jika tidak perlu backward compatibility
2. Preview style: Tambah preview bagaimana tampilan dengan style yang dipilih sebelum disimpan
3. History: Log perubahan setting gaya penomoran untuk audit trail
4. Per-bagian override: Jika diperlukan, tambah checkbox "Override global style" untuk bagian tertentu
