# Analitik Admin

Menu Analitik tersedia untuk admin dengan izin `view-reporting`, termasuk halaman dan ekspor CSV. Tidak ada perubahan skema database.

- Angka dibaca langsung dari baris laporan berstatus finalized dengan profileid, pada batch completed. Data pending, failed, No account, atau tanpa parent batch tidak masuk KPI pendapatan.
- Periode memakai sales_month (bulan penjualan). Default 12 bulan sampai bulan penjualan terakhir yang sudah dihitung, sehingga keterlambatan upload tidak membuat dashboard kosong. Rentang custom maksimal 10 tahun. Perbandingan memakai rentang sebelumnya dengan jumlah bulan sama atau tahun sebelumnya.
- Stream / Unit memakai quantity sumber. Angka ini bukan pengukuran streaming langsung dan bisa termasuk tipe penjualan lain. Koreksi negatif dipertahankan. Pembagian pendapatan memakai nilai yang sudah tersimpan pada baris, bukan rate profil terbaru.
- Total global dijumlah dari sumber sekali. Rilis dipetakan melalui releaseid yang cocok pemiliknya, UPC unik per profil, kemudian relasi lagu yang hanya mengarah ke satu rilis milik profil tersebut. Relasi banyak rilis tidak menggandakan angka. Baris ambigu tetap masuk total global dan dicatat pada Kualitas Data, tetapi tidak ditempelkan ke rilis secara acak.
- Tabel rilis mencakup katalog yang belum mempunyai aktivitas (nilai 0). Filter platform/negara membatasi aktivitas, bukan menghapus rilis tanpa aktivitas dari katalog. Search judul/UPC/ISRC dan urutan hanya memengaruhi tabel dan ekspor rilis, bukan KPI. Pencarian ISRC juga dapat menampilkan beberapa rilis yang menggunakan lagu yang sama.
- Kredit artis menggunakan teks sumber laporan; kolaborasi tidak dipecah dan dihitung berulang. Grafik donut menampilkan kontribusi positif platform; tabel dan KPI tetap memakai nilai bersih termasuk koreksi negatif.
- Kualitas Data mengikuti periode/filter baris sumber. Laporan tanpa baris belum tercakup dalam hitungan status. Selisih rekonsiliasi adalah total dikurangi bagian user dan sistem.
- Ekspor CSV memakai filter dan pencarian yang sama dengan tabel rilis, mencakup seluruh hasil tanpa batas halaman. Kolom teks yang dapat menjadi formula dinetralkan dan UPC dipertahankan sebagai teks agar nol awal tidak hilang. Tidak ada penulisan data analitik ke file JSON maupun kolom JSON.

Setelah deployment: `npm run build`, `php artisan route:clear`, dan `php artisan view:clear`. Tidak perlu migrate. Data produksi tidak diubah oleh pengujian; tes memakai SQLite in-memory terisolasi.
