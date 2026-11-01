# Audit penyimpanan data — 1 November 2026

## Cakupan dan hasil

Audit dilakukan terhadap kode aplikasi dan database MySQL lokal proyek ini. Database hosting `new.dimensisuara.id` belum diakses atau diubah. Data rilis, lagu, media, serta impor pada database lokal kosong; pengujian alur tersebut menggunakan fixture terisolasi di SQLite, bukan salinan database hosting.

Permintaan yang diterapkan: data aplikasi tidak menggunakan file JSON maupun kolom JSON; nilai terstruktur dipindahkan ke tabel relasi. JSON tetap digunakan untuk permintaan/respons HTTP, pembacaan SQL lama, dan protokol internal Laravel di memori, bukan sebagai kolom penyimpanan aplikasi.

Hasil perintah `php artisan system:audit-storage --integrity` setelah migrasi lokal:

- Kolom JSON/JSONB asli: **0**.
- Kolom payload lama yang dipetakan migrasi: sudah dihapus.
- Relasi induk/anak dan tautan bisnis yang diperiksa: **0 data yatim**.
- Session, cache, dan antrean: **database**.
- Log aktivitas: database melalui kanal `db` dalam stack; log diagnostik teks juga dipertahankan.
- Antrean gagal: `database-uuids`, menggunakan adaptor relasional.
- Nilai sensitif, termasuk pengaturan layanan, payload notifikasi, antrean, dan arsip impor, tetap dienkripsi.

Pemeriksaan ini bukan jaminan bahwa seluruh alur hosting telah diuji dengan data produksi. Migrasi lama pada database lokal masih banyak yang tercatat pending meskipun tabelnya ada. Riwayat itu tidak diubah sembarang atau dianggap sudah dijalankan.

## Tabel relasi

| Data induk | Tabel anak |
| --- | --- |
| `releases` | `release_artist_values` |
| `tracks` | `track_credit_values` |
| `publishings` | `publishing_writer_values` |
| `publishing_signees` | `signee_publisher_values` |
| `contracts` | `contract_field_values` |
| `announcements` | `announcement_content_values` |
| `broadcasts` | `broadcast_recipient_values` |
| `legacy_import_records` | `legacy_source_values` |
| `legacy_import_runs` | `legacy_import_stat_values` |
| `logs` | `activity_log_context_values` |
| `status_notification_deliveries` | `notification_payload_values` |
| `notifications` | `user_notification_values` |
| `system_settings` | `system_setting_values` |
| `jobs` | `queue_job_values` |
| `failed_jobs` | `failed_job_values` |
| `job_batches` | `batch_failure_values` |

Setiap tabel anak memiliki foreign key ke induk dan relasi `parent_id` untuk struktur bertingkat. `field`, kunci, posisi, tipe nilai, dan nilai skalar disimpan terpisah. Struktur array/objek serta urutan kredit dipertahankan; tidak ada kolom JSON pada tabel anak. Format ini juga menampung struktur dokumen pengumuman, template, dan arsip impor tanpa membatasi atribut yang sebelumnya tersedia.

Model menyediakan `relationalFieldRows()` untuk relasi Eloquent. Adaptor mempertahankan bentuk data yang dipakai formulir, notifikasi, dan tampilan lama; baris penyimpanan internal tidak ikut ditampilkan dalam respons model.

Proses impor SQL dan pencatatan log yang melewati Eloquent sudah disesuaikan. Pencarian artis pada daftar rilis juga mencari melalui tabel relasi. Antrean, retry pekerjaan gagal, dan pencatatan kegagalan batch memakai adaptor baru tanpa mengubah file vendor.

## File yang tetap berupa file

WAV, gambar, PDF, dokumen identitas, upload sementara, dan salinan backup tetap berada pada storage/Google Drive. Identitas, pemilik, dan lokasinya tercatat di database. Berkas biner tidak dipindahkan ke kolom database, sesuai penggunaan Google Drive yang sudah diminta.

`composer.json`, `package.json`, lockfile, terjemahan, dan referensi seeder tetap diperlukan untuk instalasi/build atau data referensi. Berkas tersebut bukan tempat penyimpanan data pengguna yang berubah saat aplikasi berjalan. Preferensi tema browser tetap disimpan sebagai satu nilai `light`/`dark` di localStorage.

## Migrasi dan cadangan lokal

Sebelum perubahan skema dibuat cadangan SQL penuh di luar web root:

`C:/Users/Administrator/.codex/backups/dimensisuara/before-relational-20261101-141457.sql`

Cadangan berisi 62 tabel / 179.973 baris, ukuran 14.656.266 byte. SHA-256:

`8e3b8be2f8c84df73a22f765f95d244303325f0a0a1e4eb2413d38c6ef1bcf93`

Cadangan dibuat untuk pemulihan pada database terpisah/kosong, bukan untuk dijalankan sembarang pada database yang sudah dimigrasikan.

Migrasi baru: `2026_11_01_130000_normalize_application_json_fields.php`. Migrasi melakukan backfill dan verifikasi nilai sebelum menghapus kolom lama. JSON atau enkripsi lama yang tidak valid menghentikan proses. Foreign key juga diperiksa kembali agar migrasi dapat dilanjutkan bila DDL MySQL sebelumnya terputus. ID notifikasi numerik lama diubah menjadi string untuk mendukung UUID Laravel, dengan nilai ID lama tetap dipertahankan.

Metode rollback mengembalikan nilai lama sebelum menghapus tabel anak. Perubahan ID notifikasi menjadi string sengaja tidak dipersempit lagi, karena notifikasi UUID yang sudah dibuat harus tetap valid.

## Penerapan di hosting

1. Buat cadangan database hosting dan pastikan cadangan dapat digunakan. Cadangan lokal di atas bukan cadangan hosting.
2. Hentikan worker melalui Plesk/Supervisor dan aktifkan maintenance. Worker lama harus benar-benar dihentikan sebelum perubahan skema.
3. Perbarui kode aplikasi. Jangan menjalankan seluruh migrasi lama pada database yang riwayat migrasinya belum selaras.
4. Perbarui pengaturan berikut pada `.env` hosting:

```dotenv
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
QUEUE_FAILED_DRIVER=database-uuids
LOG_CHANNEL=stack
LOG_STACK=single,db
```

Antrean `database`, `legacy-import`, `drive-archive`, dan `status-notifications` harus menggunakan database aplikasi yang sama dan driver database. Perintah audit menandai konfigurasi yang tidak memenuhi ketentuan tersebut.

5. Bersihkan konfigurasi, lalu jalankan migrasi baru secara spesifik:

```bash
php artisan config:clear
php artisan migrate --path=database/migrations/2026_11_01_130000_normalize_application_json_fields.php --force
php artisan migrate --path=database/migrations/2026_11_01_140000_repair_status_notification_storage.php --force
php artisan system:audit-storage --integrity
```

Jika fitur Whitelabel dari perubahan sebelumnya belum dimigrasikan, jalankan migrasinya secara spesifik juga:

```bash
php artisan migrate --path=database/migrations/2026_11_01_120000_add_whitelabel_to_user_profiles.php --force
```

6. Selesaikan temuan audit sebelum membuka aplikasi. Jalankan kembali worker dengan kode dan konfigurasi baru, lalu nonaktifkan maintenance. Perubahan backend session membuat sesi login lama berbasis file perlu login ulang.

Jangan menggunakan `migrate:fresh`, `db:wipe`, atau menjalankan seeder lama yang membersihkan tabel untuk penerapan ini.

## Validasi

181 pengujian terpilih lulus pada database/fixture terisolasi. Cakupannya meliputi migrasi relasional, pemeliharaan tipe/urutan data, rollback, enkripsi, kredit artis, impor SQL lama, notifikasi status, pengaturan sistem, driver antrean, retry pekerjaan gagal, kegagalan batch, kontrak, landing page, penghitungan katalog, periode analitik, izin akun, serta penghapusan rilis dan media.

Pengujian yang memakai `RefreshDatabase` terhadap koneksi utama tidak dijalankan untuk menghindari perubahan destruktif pada database lokal. Audit integritas MySQL menggunakan pembacaan data dan metadata skema saja.

Penerapan pada hosting belum dilakukan; publikasi kode ke GitHub terpisah dari migrasi database hosting.

## Perbaikan tabel notifikasi belum tersedia

Jika audit menampilkan `status_notification_deliveries | notification_payload_values | Parent missing`, tabel pengiriman notifikasi belum tersedia saat normalisasi dijalankan. Ini adalah temuan skema; hasil `Native JSON columns: 0` dan `Foreign-key orphans: 0` tidak berarti tabel tersebut sudah ada.

Setelah memperbarui kode dan membuat cadangan hosting, jalankan migrasi perbaikan secara spesifik:

```bash
php artisan migrate --path=database/migrations/2026_11_01_140000_repair_status_notification_storage.php --force
php artisan system:audit-storage --integrity
```

Migrasi membuat tabel notifikasi jika belum ada, lalu memakai jalur normalisasi yang sama untuk membuat relasi dan memverifikasi backfill sebelum menghapus kolom payload lama. Data yang sudah dinormalisasi dipertahankan. Rollback perbaikan ini tidak menghapus riwayat notifikasi. Tetap gunakan prosedur maintenance dan penghentian worker di atas. Jangan menjalankan migrasi pembuat tabel notifikasi lama secara terpisah setelah perbaikan, karena tabelnya sudah tersedia.

Validasi perbaikan: 15 pengujian penyimpanan relasional / 258 assertion lulus di SQLite terisolasi, termasuk tabel notifikasi yang belum ada, payload lama yang sudah berisi data, pengulangan perbaikan, serta pemeliharaan data rilis yang sudah dinormalisasi. Hosting belum diubah dari lingkungan ini.
