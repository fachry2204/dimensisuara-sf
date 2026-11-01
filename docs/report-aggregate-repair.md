# Perbaikan ringkasan laporan setelah penghapusan batch

Penghapusan batch sekarang menghapus baris sumber batch itu secara eksplisit, lalu membangun ulang tabel ringkasan dalam transaksi database yang sama. Tidak bergantung pada worker untuk memperbaiki total. Apabila pembangunan ringkasan gagal, penghapusan batch dan barisnya ikut dibatalkan; file sumber dipertahankan.

Ringkasan memakai baris `finalized` dengan pemilik pada batch berstatus `pending_rate` atau `completed`, sesuai cakupan laporan yang telah dipublikasikan. Baris `No account`, batch yang belum selesai diproses, dan baris yatim tanpa batch tidak dimasukkan. Tidak ada perubahan pada akun, rilis, WAV, cover art, atau baris laporan batch lain.

Tabel turunan dibersihkan memakai `DELETE` transaksional, kemudian diisi kembali dari sumber tersisa. Tabel ringkasan bulanan, lagu, platform, negara, artis, jenis penjualan, multidimensi, serta rilis (bila tersedia) diperbarui. Identitas grup yang kosong dan duplikasi lama pada dimensi nullable ikut dibersihkan. Nama jenis penjualan kosong tetap memakai fallback `Stream_Failback`. Pembangunan ulang mengganti ringkasan, bukan menambahkan total ke hasil sebelumnya.

Pembangunan ulang dan penghapusan memakai advisory lock MySQL yang sama. Perhitungan antrean mengunci batch sebelum membaca ulang barisnya; pekerjaan lama melewati batch/baris yang sudah hilang. Callback perhitungan hanya mengubah batch yang masih menjalankan ID antrean tersebut. Batch antrean aktif dibatalkan saat penghapusan bila metadata antreannya masih tersedia.

File sumber yang tercatat sebagai milik batch dibersihkan setelah transaksi berhasil. Jika pembersihan berkas gagal, peringatan dicatat dan identitas file dipertahankan agar bisa ditangani kembali. Kegagalan ini tidak mengembalikan data laporan yang sudah dihapus. Nama file dari pengguna tidak dipakai sebagai path penghapusan.

## Penerapan dan perbaikan ringkasan yang sudah tertinggal

Setelah backup database dan memperbarui kode hosting, hentikan/ganti worker lama agar tidak menggunakan implementasi agregasi sebelumnya. Tidak ada migrasi skema baru pada perubahan ini. Jalankan dari folder aplikasi baru:

```bash
php artisan queue:restart
php artisan view:clear
php artisan reports:rebuild-aggregates
```

Perintah terakhir membangun ulang ringkasan dari laporan yang masih tersimpan; tidak mengimpor ulang Excel dan tidak mengubah baris sumber laporan. Jika gagal, transaksi ringkasan dibatalkan dan pesan kegagalan ditampilkan. Periksa log lalu ulangi setelah penyebabnya diperbaiki.

Di kolom Artisan Plesk, masukkan nama perintah tanpa awalan `php artisan`.

## Validasi

Pengujian menggunakan SQLite terisolasi, bukan database hosting. Mencakup dua batch yang berbagi grup, grup yang hanya dimiliki batch terhapus, penghapusan batch terakhir, dimensi null dan koreksi negatif, pengulangan agregasi, pemulihan ringkasan yatim, rollback saat query ringkasan gagal, pemeliharaan file ketika rollback, pembatasan file sumber pada pemilik batch, pekerjaan antrean lama, serta izin penghapusan.

Hosting belum diakses atau diperbaiki dari lingkungan ini.
