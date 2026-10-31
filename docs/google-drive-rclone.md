# Google Drive untuk file rilis

Menu **System Setting → Setting Google Drive** menggunakan rclone yang sudah terpasang di VPS. Konfigurasi aplikasi/riwayat ada di database, sedangkan OAuth Google Drive tetap dikelola oleh rclone.conf di VPS. Tidak diperlukan plugin Google Drive atau tautan file publik.

## Persiapan VPS

1. Deploy kode lalu jalankan `php artisan migrate --force`, `php artisan config:clear`, dan `php artisan queue:restart`.
2. Pastikan `rclone config` sudah memiliki remote **tipe drive**. Jalankan konfigurasi sebagai pengguna yang menjalankan aplikasi atau berikan akses ke konfigurasi khusus untuk PHP/worker; konfigurasi milik root tidak otomatis terlihat oleh pengguna PHP. Jangan menaruh rclone.conf di public atau Git. Pengguna aplikasi perlu membaca dan memperbarui file konfigurasi untuk refresh OAuth. PHP web dan worker harus memiliki akses ke binary/config yang sama; `proc_open` tidak boleh dinonaktifkan.
3. Isi remote tanpa titik dua, folder tujuan relatif (nama folder, bukan URL berbagi/ID folder), binary misalnya `/usr/bin/rclone`, dan path absolut rclone.conf. Untuk Shared Drive/folder root tertentu, atur di remote rclone tersebut.
4. Simpan dengan pemindahan otomatis nonaktif. Klik **Tes Koneksi**. Tes memeriksa tipe remote, akses Drive, dan menyalin file acak 32 byte ke folder tujuan, memeriksa ukuran/MD5, lalu menghapus hanya file probe tersebut. Tidak ada file katalog yang dihapus dalam tes. Aktifkan otomatis dan simpan setelah tes berhasil.
5. Jalankan worker khusus melalui Supervisor/systemd, dengan working directory aplikasi:

   ```sh
   php artisan queue:work drive-archive --queue=drive-archive --timeout=900 --tries=1
   ```

   Koneksi antrean `drive-archive` memakai tabel jobs dan retry_after 960 detik, lebih panjang daripada batas job 900 detik. Worker antrean default tidak mengambil antrean ini. Setelah pembaruan kode, restart worker.

6. Aktifkan Laravel Scheduler sekali per menit sebagai pengguna aplikasi:

   ```cron
   * * * * * cd /path/aplikasi && php artisan schedule:run >> /dev/null 2>&1
   ```

   Scheduler memeriksa file eligible setiap 5 menit, maksimal 500 kandidat tiap pemindaian. Tombol **Pindahkan File Released Sekarang** menjadwalkan pemindaian yang sama, bukan memindahkan WAV di request browser.

## Cakupan

- Audio Track `full`/`trimmed`, serta Release `cover`/`thumbnail`/`ipl`, pada disk lokal aplikasi. Status release harus `released`.
- Audio yang terhubung ke beberapa rilis hanya dipindahkan bila semuanya released. File lokal hilang, rilis pending/rejected/draft, identitas pelanggan, logo aplikasi, serta kontrak tidak dipindahkan.
- Saat Drive aktif, proses backup FTP lama dilewati. File yang sebelumnya sudah dipindahkan ke FTP tetap dilayani lewat FTP; pemindahan FTP → Drive tidak termasuk pemindahan lokal ini.
- WAV/FLAC tidak dikonversi. Penyalinan memakai `rclone copyto --checksum --immutable`. Aplikasi memeriksa ukuran dan MD5 remote melalui `lsjson --stat --hash --hash-type MD5`, serta memeriksa ulang sumber sebelum mengubah referensi database.
- Pointer file di database disimpan lebih dulu sebagai disk `rclone` beserta remote/path lengkap, lalu file lokal dihapus. Jika worker berhenti di antaranya, status `cleanup_pending` diproses ulang setelah verifikasi remote. File lokal berbeda atau masih dipakai record lain dipertahankan.
- Path remote unik berdasarkan ID file dan MD5, sehingga judul sama tidak bertabrakan. Tidak ada operasi sync, purge, atau pemindahan seluruh folder hosting. Kegagalan disimpan di drive_transfers dan dicoba kembali pada pemindaian berikutnya.
- Setelah ada arsip terverifikasi, nama remote dan path konfigurasi dikunci di UI. Folder tujuan boleh berubah untuk arsip baru; setiap file menyimpan lokasi lamanya sendiri. Jangan menghapus atau mengubah identitas akun pada remote yang dipakai arsip.

## Akses file dan operasional

Endpoint file tetap memeriksa pemilik/hak unduh admin sebelum menjalankan rclone. Streaming menggunakan `rclone cat --offset --count` dan mendukung HTTP single-byte Range untuk seek audio. Data dialirkan tanpa salinan WAV permanen di hosting; memori tidak menampung seluruh file. Kuota/latensi Google Drive serta bandwidth dan proses PHP VPS tetap berlaku. Drive tidak menggantikan CDN.

Cover landing page tetap tersedia melalui endpoint cover yang hanya melayani rilis publik/released sesuai tanggal. Thumbnail dan cover di aplikasi tetap memakai endpoint berizin.

Menonaktifkan otomatis hanya menghentikan pemindahan berikutnya; file yang sudah di Drive tetap dibaca dari Drive. File di Drive dipertahankan ketika record aplikasi dihapus, agar penghapusan record tidak diam-diam menghapus arsip eksternal. Peninjauan/pembersihan arsip Drive dilakukan terpisah.

Riwayat menampilkan 30 transfer terbaru, status, ukuran, serta kendala yang sudah disaring dari output rclone. Tidak ada token OAuth yang ditampilkan atau disimpan dari UI. Pengujian aplikasi memakai database SQLite dan rclone mock; koneksi/transfer asli harus diuji melalui tombol pada VPS sebelum diaktifkan.

Dokumentasi rclone: [copyto](https://rclone.org/commands/rclone_copyto/), [lsjson](https://rclone.org/commands/rclone_lsjson/), [cat dan byte offset](https://rclone.org/commands/rclone_cat/).
