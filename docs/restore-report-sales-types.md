# Memulihkan jenis penggunaan pada laporan lama

Impor SoundOn lama belum menyimpan Sales Type. Menambahkan kolom Sales Sub Type tidak mengisi nilai historis. Akibatnya seluruh quantity lama dapat tampil sebagai Unit lainnya, meskipun nominal penghasilan tetap tersedia.

Perintah berikut membaca ulang file sumber lokal yang terkait dengan laporan. Secara bawaan hanya laporan SoundOn berstatus completed atau pending_rate yang diperiksa. Dapat juga diberikan satu ID laporan.

Di tab Artisan Plesk, masukkan tanpa awalan `php artisan`:

```text
reports:restore-sales-types
```

Ini hanya pemeriksaan. Periksa jumlah baris cocok, jumlah yang dapat dipulihkan, dan peringatan nilai yang tidak ada dalam sumber. Untuk menyimpan:

```text
reports:restore-sales-types --apply
```

Untuk satu laporan, misalnya ID 75:

```text
reports:restore-sales-types 75
reports:restore-sales-types 75 --apply
```

Perintah hanya mengisi Sales Type dan Sales Sub Type kosong. File harus memiliki kolom Sales Type; subtype yang tidak tersedia tetap kosong dan ditampilkan sebagai `-`. Identitas baris dicocokkan berdasarkan urutan impor, jumlah baris, bulan laporan, bulan penjualan, platform, UPC, ISRC, quantity, dan net revenue. Nilai jenis yang sudah terisi tidak ditimpa jika berbeda dengan sumber.

Setiap laporan diperbarui dalam transaksi bersama seluruh ringkasan turunannya. Quantity, royalti, kepemilikan, status, dan jumlah baris sumber tidak diubah. Pemanggilan ulang tidak menggandakan data. Laporan yang gagal dicocokkan dilewati dan dilaporkan sebagai error; laporan lain yang berhasil tetap tersimpan.

Jika file sumber sudah tidak tersedia atau ada baris yang tidak cocok, perintah tidak mengubah laporan tersebut. Pulihkan dahulu file sumber asli beserta hubungannya ke laporan. Jangan mengimpor ulang laporan yang sama untuk memperbaiki kategori karena dapat menggandakan pendapatan.

Kolom sales_sub_type harus tersedia sebelum perintah dijalankan. Tidak ada migrasi baru untuk pemulihan ini. Perintah perlu kode aplikasi terbaru; belum tersedia pada versi sebelum perbaikan ini.
