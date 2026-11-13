# Status upload dan struktur arsip Google Drive

Tab Automation → Status Upload Google Drive menampilkan file lokal dan Drive yang terkait rilisan Published. Kartu dapat memfilter total, belum diunggah/antre, sedang diproses, sudah terverifikasi, dan gagal. File pada FTP atau penyimpanan lain ditampilkan sebagai jumlah terpisah; pemindahan ini menangani media pada VPS dan penataan arsip Drive.

Struktur baru di bawah folder tujuan yang tersimpan:

```text
Nama Client (C123)/
  Nama Artis/
    Judul Release (R456)/
      Judul Lagu - Audio Penuh (F789-checksum).wav
      Judul Lagu - Audio Potongan (F790-checksum).wav
      Judul Release - Cover (F791-checksum).jpeg
```

Nama client menggunakan nama perusahaan jika profil perusahaan, atau nama lengkap profil. ID pada nama folder mencegah tabrakan antar-client dan rilisan bernama sama. Karakter pemisah path dan karakter khusus dinormalisasi. Audio bersama memakai satu salinan dalam folder rilisan Published dengan ID terkecil. Konten dan format media tidak dikonversi.

## Deployment melalui Artisan Plesk

Pasang kode, lalu jalankan migrasi khusus ini tanpa awalan php artisan:

```text
migrate --path=database/migrations/2026_11_07_060000_add_drive_layout_tracking.php --force
queue:restart
optimize:clear
drive:archive-released
```

Pemindaian dibatasi 500 kandidat per putaran agar beban antrean terkendali. Scheduler melanjutkan putaran berikutnya. Tombol Proses semua file Published memakai pemindaian yang sama. Worker drive-archive dan scheduler harus aktif, serta Google Drive harus sudah diaktifkan setelah tes koneksi berhasil.

Migrasi menambahkan layout_version dan previous_remote_identifier pada drive_transfers secara idempoten. Tidak mengubah file atau folder ketika migrasi dijalankan. Jika tabel drive_transfers belum ada, pasang dahulu migrasi pembuat tabel tersebut.

Unggahan baru mengikuti struktur nama ini. Arsip Drive lama disalin ke tujuan baru dan diperiksa ukuran/MD5-nya; aplikasi menyimpan pointer baru sebelum file Drive lama dibersihkan. Jika proses terhenti, lokasi lama dicatat untuk dicoba ulang. Direktori lama hanya dihapus memakai rmdir jika kosong dan cocok dengan pola arsip files/ID yang dikenal. Tidak menjalankan purge atau menghapus file lain dalam folder.

File VPS dihapus hanya setelah Drive dan pointer database terverifikasi. File yang berubah, gagal dibaca, gagal checksum, atau masih direferensikan record lokal lain dipertahankan dan dicoba lagi; jangan menghapusnya manual sebelum masalah selesai. Status cleanup_pending berarti file sudah di Drive tetapi pembersihan VPS belum tuntas. Status remote_cleanup_pending berarti tujuan baru sudah siap tetapi lokasi Drive lama belum selesai dibersihkan.

Status UI berasal dari catatan worker, bukan pemeriksaan cloud langsung setiap halaman dibuka. Waktu verifikasi adalah waktu pemeriksaan salinan oleh aplikasi.

Jika cover publik tidak tampil setelah diarsipkan, jalankan `drive:inspect-artwork ID_RILIS` melalui Artisan Plesk. Perintah hanya membaca record thumbnail/cover dan metadata Drive, menampilkan MIME, ukuran, serta diagnosis rclone yang telah disanitasi. Tidak memindahkan atau menghapus file. Hasil CLI belum memastikan izin PHP-FPM sama; bila CLI berhasil tetapi endpoint publik tetap 503, periksa konfigurasi dan izin PHP web. Cover Drive tanpa MIME database dapat dikenali melalui ekstensi gambar, lalu MIME metadata Drive diverifikasi sebelum disajikan. Thumbnail yang gagal diakses dapat beralih ke cover utama.

Jika copyto melaporkan kegagalan setelah membuat file tujuan, worker memeriksa kembali ukuran dan MD5 tujuan. Hanya salinan yang identik dengan sumber yang dapat dilanjutkan ke penyimpanan lokasi baru dan pembersihan sumber. Salinan yang tidak cocok tidak ditimpa. Error penataan mencatat tahap membaca sumber, menyalin, memverifikasi, menyimpan lokasi, atau membersihkan lokasi lama; error rclone juga mencatat nama perintah dan kode keluar tanpa stderr mentah.
