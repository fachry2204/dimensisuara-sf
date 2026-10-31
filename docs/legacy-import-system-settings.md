# Import Data Lama dari System Setting

Tab **System Setting → Import Data Lama** memakai pemetaan `DimensiImporter`, bukan menjalankan SQL yang diunggah. Format yang didukung adalah ekspor phpMyAdmin website Dimensi lama dengan `INSERT INTO` beserta nama kolom. Struktur lain ditolak dan memerlukan adapter pemetaan; jangan mengganti nama tabel secara sembarang untuk melewati pemeriksaan.

## Persiapan hosting

1. Backup database aplikasi baru dan sumber lama. Pertahankan website lama sampai hasil diverifikasi.
2. Deploy kode, lalu jalankan dari direktori aplikasi:

   ```sh
   php artisan migrate --force
   php artisan config:clear
   php artisan queue:restart
   ```

3. Aktifkan worker khusus (PHP CLI harus memiliki akses MySQL dan folder website lama):

   ```sh
   php artisan queue:work legacy-import --queue=legacy-import --timeout=900 --tries=1
   ```

   Alternatif Plesk Scheduled Tasks setiap menit, dengan working directory aplikasi dan executable PHP yang sesuai:

   ```sh
   php artisan queue:work legacy-import --queue=legacy-import --timeout=900 --tries=1 --stop-when-empty --max-time=50
   ```

   `--max-time` diperiksa di antara job; satu WAV tetap dapat memerlukan waktu lebih lama. Hosting perlu mengizinkan proses tersebut. Koneksi antrean khusus menggunakan tabel `jobs`, queue `legacy-import`, dan `retry_after=960` agar job tidak diambil ulang sebelum timeout 900 detik. Antrean lain tidak diubah. SQL besar dibaca dalam memori: sesuaikan memori PHP CLI, bukan hanya batas upload PHP web.

4. Folder sumber harus berada di bawah `LEGACY_IMPORT_ALLOWED_ROOT` (default: direktori induk aplikasi). Untuk dua domain di akun hosting yang sama, atur ke folder akun yang menaungi keduanya. Hindari root seluruh server. Jalankan `php artisan config:clear` setelah mengubah .env. Izin baca/open_basedir hosting tetap berlaku.

## Alur administrator

1. Unggah `.sql` (batas aplikasi 100 MB, dibatasi juga oleh upload PHP hosting), atau salin ke `storage/app/private/legacy-import/inbox` melalui SFTP/File Manager dan isi nama file. SQL tidak ditempatkan dalam direktori public.
2. Isi folder utama website/media lama. Jika referensi SQL adalah `https://lama.example/uploads/covers/a.jpg`, sumber dibaca sebagai `<folder>/uploads/covers/a.jpg`. Jika SQL memakai `/storage/...`, folder yang dipilih harus memiliki folder `storage`. Tidak ada pencarian berdasarkan basename atau pengunduhan URL.
3. Klik **Periksa SQL Lama**. Worker menjalankan importer di transaksi yang kemudian di-rollback dan, jika folder media diisi, menghitung referensi sumber yang dapat ditemukan. Tidak ada file media disalin saat trial. Counter auto-increment MySQL dapat bertambah.
4. Tinjau statistik. Setelah backup tersedia, centang pernyataan backup dan klik **Import ke Database**. Impor berjalan dalam satu transaksi. SQL tidak diubah setelah pemeriksaan; checksum diverifikasi ulang. Konflik dan hak admin mengikuti aturan importer.
5. Setelah database berhasil, klik **Salin Dokumen, Gambar, Audio & IPL**. Satu job memproses satu pengguna/rilis/lagu, kemudian menjadwalkan job berikutnya. Klik **Perbarui Progres** untuk melihat hasil; halaman dapat ditutup tanpa menghentikan worker.
6. Tinjau kendala per ID pemetaan/field. Perbaiki folder atau sumber yang hilang, lalu ulangi media. File yang sudah ada dengan checksum cocok tidak diduplikasi. Media tujuan berbeda tidak ditimpa. Data lama dan file aslinya tidak dihapus.

## Melengkapi dokumen dari impor sebelumnya

Setelah deploy, restart worker lalu buka riwayat impor yang sudah selesai dan klik **Periksa dan Ulangi Media**. Isi folder utama website lama yang memuat path dokumen pada SQL. Pemetaan pengguna yang dahulu belum terhubung ke fase media dipulihkan dari SQL asli yang checksumnya cocok dan baris pengguna yang sama persis dengan arsip terenkripsi. Pertahankan SQL asli di inbox sampai pelengkapan selesai. Pengguna dan rilisan tidak dibuat ulang. File yang hilang atau berbeda dilaporkan per field; file tujuan berbeda tidak ditimpa.

## Cakupan dan integritas

- Pelanggan/profil, metadata rilis, lagu, kredit dan relasi memakai pemetaan SQL yang sudah tersedia. Admin sumber tidak otomatis menjadi admin aplikasi baru; email yang bertabrakan dengan `users` maupun `user_admin` dilewati.
- `cover_art` (fallback `cover_url`) → cover + salinan thumbnail. Thumbnail menggunakan proporsi gambar asli; dapat dioptimalkan kemudian.
- `audio_file` → audio full; `audio_clip` → trimmed. WAV/FLAC tidak dikonversi. Audio MP3/M4A/OGG didukung bila tipe MIME sesuai.
- Dokumen pengguna: `ktp_doc_path` → KTP; `npwp_doc_path` → NPWP perusahaan atau personal menurut jenis akun; `nib_doc_path` → NIB; `kemenkumham_doc_path` → SK Kementerian; `signature_doc_path` → tanda tangan. Mendukung gambar JPG/PNG/WebP dan PDF. Pemilik file adalah profil baru hasil pemetaan pengguna, bukan ID pengguna lama.
- `contract_doc_path` → dokumen profil berlabel Kontrak dari website lama (PDF, gambar, atau DOCX valid). Tidak menghasilkan kontrak baru atau mengubah status kontrak.
- `ipl_file` track → IPL milik rilis terkait (PDF). Apabila beberapa IPL berbeda menunjuk rilis yang sama, konflik dilaporkan.
- File disimpan privat pada disk lokal aplikasi, dengan kepemilikan `filestorage` sesuai ID baru, ukuran, MIME, dan checksum SHA-256 dalam hasil migrasi. File tujuan per pemilik terpisah agar penghapusan satu rilis tidak merusak rilis lain.
- Path traversal, file di luar root, symlink yang keluar root, dan tipe media yang tidak cocok ditolak. URL hanya menyumbangkan path; tidak ada permintaan ke host eksternal.
- Riwayat, progres, statistik, relasi pemetaan dan hasil file disimpan di database (`legacy_import_runs`, `legacy_import_run_records`, `legacy_import_media_results`). SQL adalah arsip input privat. Arsip sumber terenkripsi tetap memakai `legacy_import_records` dan `APP_KEY` aplikasi.
- Pengulangan mendukung baris sumber yang identik. Baris lama yang sudah diimpor lalu berubah ditolak; ini bukan alat sinkronisasi delta otomatis.
- Saldo/transaksi/publishing catalogue dan rekaman `release_uploads` belum dipetakan ke fitur aktif. Riwayat sumber tertentu tetap diarsipkan oleh importer. File tanpa referensi pada baris katalog tidak dapat dipasangkan otomatis.
- Status selesai berarti referensi dari baris yang berhasil dipetakan sudah diproses. Tetap tinjau statistik konflik/lewati dan uji akses cover/audio sebagai pemilik sebelum cutover.

Sesudah seluruh hasil diverifikasi, hapus arsip SQL privat yang tidak lagi diperlukan melalui File Manager. Jangan menghapus `legacy_import_records`: tabel tersebut dibutuhkan untuk pemetaan dan pencegahan duplikasi.
