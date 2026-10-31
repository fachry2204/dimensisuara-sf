# Migrasi website Dimensi Suara lama

Migrasi pertama 30 Oktober 2026 dari `dimensisuara.sql` ke MySQL
`cmssoundfresh` telah diuji pada salinan `cmssoundfresh_legacy_trial_20261030`.

## Hasil

- 27 client baru dan profilnya.
- 318 rilisan beserta pemiliknya.
- 366 track beserta hubungan rilisan dan urutan track.
- 2 akun dengan email yang sudah ada dilewati; keduanya tidak memiliki rilisan lama.
- 4 akun admin lama tidak diaktifkan sebagai client atau diberi hak admin baru.
- Pembagian termasuk 69,99% disimpan persis. Kolom aggregator/publishing rate
  diperluas dari integer menjadi DECIMAL(5,2); nilai lama tetap sama.
- Bcrypt `$2b$` dinormalisasi menjadi `$2y$` tanpa mengganti hash atau password.
  Kolom `password_hash` dan `password` pada dump yang diperiksa bernilai sama.
- Status Approved/Active menjadi verified; Blocked/Inactive menjadi blocked;
  status client lainnya menjadi pending. Persetujuan profil tidak dianggap sebagai
  bukti verifikasi email: `email_verified_at` akun baru tetap kosong.
- Live/Released menjadi released, Processing menjadi uploading, Request Edit
  menjadi revision. Pending/Rejected dipertahankan sesuai status aplikasi baru.
- EP disimpan sebagai album; jenis sumber lengkap tetap disimpan pada arsip.
- Bahasa Indonesia/Indonesian menjadi id; United States menjadi en dan Argentina
  menjadi es mengikuti nilai negara yang digunakan sebagai bahasa di aplikasi
  lama. Nilai sumber tetap ada untuk diperiksa bila perlu.
- Nama genre, subgenre, versi, dan peran kredit dipetakan berdasarkan nama,
  bukan menyamakan ID antar database. Nilai yang belum ada ditambahkan.
- Data yang tidak mempunyai kolom setara (misalnya p/c line, tujuan distribusi,
  PIC perusahaan dan referensi dokumen) tetap ada pada arsip terenkripsi,
  tetapi belum menjadi fitur yang ditampilkan pada formulir aplikasi baru.
- Pembagian publishing tersimpan pada profil; fitur publishing tidak otomatis
  diaktifkan karena katalog publishing belum termasuk migrasi ini.

Terdapat satu UPC berulang dan lima ISRC berulang di sumber (enam kemunculan
tambahan ISRC). Record sumber tetap terpisah menurut ID aslinya: tidak digabung
diam-diam dan tidak hilang. Benturan dengan katalog yang sudah ada di tujuan
justru dilewati untuk pemeriksaan.

## Media yang belum tersedia

Dump SQL tidak menyertakan berkas. Terdapat referensi 265 cover, 244 audio master,
244 audio clip serta dokumen client. Riwayat `release_uploads` juga diarsipkan
(446 baris), termasuk status upload; riwayat tersebut belum berarti berkas ada
di penyimpanan baru. Tidak ada placeholder filestorage yang dibuat untuk berkas
yang tidak tersedia. Pemindahan media menunggu folder uploads website lama.

## Penyimpanan dan keamanan operasional

`legacy_import_records` menyimpan source, entity, legacy_id, target_id, status,
SHA-256 dump, dan payload asli terenkripsi menggunakan APP_KEY aplikasi.
Tidak ada penyimpanan data migrasi pada file JSON. Simpan APP_KEY bersama
prosedur backup yang aman karena diperlukan untuk membuka arsip.

Parser hanya membaca nilai INSERT literal untuk tabel yang diizinkan. SQL
CREATE/DROP/UPDATE dari dump tidak dieksekusi. Import memakai query builder
dengan parameter, tanpa model events, pengiriman email, WhatsApp, atau upload
ke layanan eksternal. Proses dijalankan dalam satu transaksi dan GET_LOCK
mencegah dua import berjalan bersamaan. Unik source/entity/legacy_id menjaga
idempotensi. Perubahan row sumber yang sudah pernah diarsipkan akan ditolak.

Cadangan lengkap sebelum migrasi:
`storage/app/private/legacy-migration/20261030/cmssoundfresh-before-legacy.sql`.
Cadangan ini berisi data sensitif, berada di luar public, dan tidak di-commit.
Jangan merestore seluruh backup ke database aktif tanpa mempertimbangkan
perubahan setelah migrasi; gunakan salinan terpisah untuk pemulihan selektif.

## Menjalankan ulang

Cadangkan database sebelum perubahan. Terapkan migration
`2026_10_30_150000_create_legacy_import_records_table.php` ke tujuan.

```powershell
php artisan legacy:import-dimensi 'C:\path\dimensisuara.sql' --database=database_uji
php artisan legacy:import-dimensi 'C:\path\dimensisuara.sql' --database=database_uji --apply
```

Tanpa `--apply`, seluruh trial di-rollback, tetapi sequence auto-increment MySQL
dapat maju. Gunakan database salinan untuk trial. Tanpa `--database`, tujuan
adalah koneksi default aplikasi. Akun bentrok email tidak ditautkan otomatis;
rekonsiliasi harus memeriksa kepemilikan sebelum mengubah mapping.

Verifikasi dilakukan pada seluruh arsip terhadap row dump, data tujuan yang
sudah ada, jumlah data, kepemilikan, bcrypt, presisi persentase, jumlah serta
urutan track dan tidak adanya track tanpa rilisan. Uji pengulangan berhasil.
`tests/Feature/LegacyImportTest.php` meliputi parser, rollback, benturan email,
pemisahan admin, idempotensi, perubahan source, bcrypt dan pemetaan kredit.
