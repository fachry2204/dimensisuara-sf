# Notifikasi status pengguna dan rilis

**System Setting → Notifikasi Email / Notifikasi WhatsApp** mengatur pesan kepada pemilik akun untuk perubahan status baru. Akses pengaturan, preview dan pengiriman ulang memerlukan izin server `manage-automation`.

## Aktivasi

1. Deploy kode. Jalankan migrasi khusus fitur ini agar tidak mengulang migrasi awal pada database hasil pemindahan:

   ```sh
   php artisan migrate --path=database/migrations/2026_10_31_120000_create_status_notification_deliveries.php --force
   php artisan view:clear
   php artisan queue:restart
   ```

   Pada Plesk Artisan, masukkan hanya bagian sesudah `php artisan`. Migrasi ini membutuhkan tabel database aplikasi dan koneksi antrean database (`jobs`) yang sudah tersedia.

2. Atur SMTP dan token device Fonnte di **Pengaturan Umum**. Koneksi tetap memakai pengaturan yang sudah ada. Default notifikasi status email aktif per kanal, WhatsApp nonaktif; draft nonaktif per template pada kedua kanal. Email tanpa SMTP aktif dicatat sebagai dilewati, bukan dianggap terkirim.
3. Pilih kanal dan status, edit judul/subjek, isi pesan, teks tombol/label tautan, footer, dan aksen email. Footer serta aksen berlaku untuk semua status pada kanal tersebut. Sakelar status berlaku hanya untuk status terpilih. Simpan sebelum membuka status lain. Semua pengaturan disimpan terenkripsi melalui `system_settings`, tanpa file konfigurasi JSON.
4. Gunakan **Pratinjau Perubahan** untuk melihat input yang belum disimpan. Preview memakai data contoh dan tidak mengirim email/WhatsApp. Email memakai layout tabel dengan status, rincian rilis, tombol tindak lanjut, serta tautan alternatif. WhatsApp memakai teks terstruktur yang menyesuaikan tampilan aplikasi penerima.
5. Worker antrean default memproses pengiriman:

   ```sh
   php artisan queue:work --queue=default --timeout=60 --tries=1
   ```

   Koneksi `status-notifications` memakai tabel jobs dan queue default, retry_after 120 detik. Worker database default aplikasi dapat mengambil job ini. Laravel Scheduler yang sudah ada menjalankan worker stop-when-empty setiap menit. Proses impor dan Google Drive tetap memakai antrean khusus masing-masing.

## Pemicu

- Status profil: `pending`, `verified`, `blocked`.
- Verifikasi email: perubahan `email_verified_at` dari belum terisi menjadi terisi, terpisah dari persetujuan profil.
- Status rilis: `draft`, `pending`, `revision`, `review`, `uploading`, `released`, `rejected`.
- Observer Eloquent mencatat event setelah model dibuat atau status berubah. Perubahan metadata tanpa perubahan status tidak memicu pesan baru. Transisi kembali ke status lama merupakan event baru yang sah.
- Update status dari alur registrasi dan layanan/admin yang memakai model Eloquent terhubung otomatis. Import SQL menggunakan Query Builder dan tidak memicu notifikasi massal; update SQL langsung juga tidak memicu observer.
- Notifikasi admin atas pengajuan rilis serta notifikasi publishing/pembayaran/verifikasi tautan keamanan tidak termasuk editor status ini. Notifikasi database/bell untuk pengajuan/persetujuan/penolakan rilis tetap ada. Pengiriman email/WhatsApp pelanggan dari tiga notifikasi rilis lama dinonaktifkan setelah tabel fitur baru tersedia untuk menghindari kirim ganda; sebelum migrasi, jalur lama tetap digunakan.

## Placeholder

`{nama}`, `{aplikasi}`, `{status}`, `{status_sebelumnya}`, `{judul_rilis}`, `{artis}`, `{upc}`, `{tanggal_rilis}`, `{alasan}`, `{tautan}`.

Isi berupa teks, bukan HTML/Blade yang dieksekusi. Semua teks di email di-escape; baris baru dipertahankan. Warna hanya format hex. Tautan ditentukan aplikasi menuju akun/rilis terkait. Nomor rekening, dokumen identitas, dan password tidak dimasukkan ke konteks pesan. Token rilis tidak mempunyai data rilis pada event akun; gunakan template sesuai jenis status.

## Riwayat dan kegagalan

`status_notification_deliveries` menyimpan event, kanal, ID user/rilis, status pengiriman, percobaan, dan waktu. Penerima serta snapshot pesan disimpan terenkripsi menggunakan APP_KEY; perubahan template tidak menulis ulang pesan yang sudah dijadwalkan. Job dikirim setelah transaksi status commit; rollback tidak meninggalkan event pengiriman. Penerima ditentukan dari pemilik profil, bukan admin yang menekan tombol.

Status `sent` ditampilkan sebagai **Diterima penyedia**, bukan bukti telah dibaca. Job yang sama tidak mengirim ulang baris sent/processing. Kegagalan jaringan tidak dicoba ulang otomatis karena penyedia mungkin telah menerima pesan sebelum timeout. Setelah memeriksa penyedia, administrator dapat mencentang pernyataan pemeriksaan dan menjadwalkan ulang baris failed/skipped; baris pending/processing/sent tidak dapat dijadwalkan ulang dari UI. Riwayat UI tidak menampilkan token, pesan pribadi penuh atau output mentah penyedia.

Mengirim ulang memakai snapshot penerima dan pesan awal. Perbaikan alamat/nomor pada profil memengaruhi event baru; periksa penerima awal sebelum retry. Menonaktifkan kanal/status juga diperiksa kembali oleh worker, sehingga job tertunda dapat dilewati. Kegagalan pengiriman tidak mengubah kembali status bisnis pengguna/rilis.

Pengujian memakai SQLite, Mail fake dan HTTP fake. Tidak ada pengiriman nyata selama pengembangan. Uji koneksi SMTP/Fonnte pada hosting sebelum mengandalkan pengiriman operasional.
