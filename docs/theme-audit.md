# Audit tema dan kontrol form

Tanggal: 30 Oktober 2026.

## Cakupan

- Pemeriksaan sumber 117 template Blade, komponen Vue, CSS tema, serta inisialisasi tema.
- 49 rute GET dirender melalui kernel Laravel dalam transaksi database **read-only**. 46 memberikan HTTP 200; dua di antaranya adalah halaman MCN pengguna yang masih kosong.
- Tampilan awal 44 halaman berisi konten diperiksa di browser pada tema terang dan gelap. Audit awal belum mencakup seluruh keadaan interaktif; screenshot pengguna mengungkap bagian modal pilihan rilis dan tab Detail Report yang terlewat.
- Snapshot hanya dipakai untuk audit lokal. Pengiriman form ke data akun nyata, pembayaran, notifikasi, dan penandatanganan tidak dilakukan.

## Perbaikan

- Palet dan kontrol bersama di `resources/css/theme-controls.css`, termasuk teks, placeholder, dropdown, tanggal, file, readonly/disabled, validasi, fokus keyboard, dan hover.
- Aturan putih pada semua input diperbaiki agar tidak menimpa radio/checkbox. Tanda pilihan radio, checkbox, switch, dan tombol-radio sekarang memiliki kontras yang jelas.
- Perbaikan pemulihan pilihan lisensi Publishing dan mode Broadcast setelah validasi gagal; radio rilis memiliki pilihan awal sebelum Alpine berjalan.
- Tema diterapkan sebelum CSS dimuat dan tidak bergantung pada keberhasilan plugin sidebar. Preferensi yang sama berlaku pada halaman masuk/daftar.
- Tabel, ringkasan, profil, preview, tab aktif, badge, dan kontrol grafik mengikuti tema. Cache versi asset diperbarui otomatis.

## Verifikasi

- Build Vite berhasil; template Blade dan CSS berhasil dikompilasi/diparse.
- 21 pengujian otomatis lulus, 117 assertion: SystemSettingsTest, AnalyticsPeriodTest, ThemeFormsTest. Pengujian memakai penyimpanan sementara atau database SQLite terpisah.
- Browser: input teks, textarea, dropdown, tanggal, radio, checkbox, switch, dan tombol-radio dapat diisi/dipilih. Pergantian tema mempertahankan nilai dan pilihan.
- Form rilis admin: pilihan "sudah dirilis" menampilkan tanggal asli/UPC; pilihan "rilis baru" menonaktifkan field tersebut. Nilainya tetap tersedia saat dipilih kembali.
- Endpoint provinsi untuk form pendaftaran memberikan HTTP 200 dan 38 pilihan.

## Temuan lama yang masih membatasi pemeriksaan

| Rute | Temuan |
| --- | --- |
| `admin.publishing.edit` | HTTP 500: metode `MainController::edit` belum tersedia. |
| `admin.users.create` | HTTP 500: metode `UserController::create` belum tersedia. |
| `admin.mcn.index` | HTTP 500: view `admin.mcn.index` belum tersedia. |
| `mcn.index`, `mcn.create` | Controller belum mengembalikan tampilan; respons kosong. |

Rute-rute ini membutuhkan implementasi fiturnya sebelum tampilan dan alur pengisiannya bisa diverifikasi. Status HTTP 200 pada rute lain tidak menyatakan semua proses penyimpanan atau integrasi eksternalnya sudah diuji.

Audit dapat diulang secara lokal dengan `php tests/render-theme-audit.php`. Snapshot sementara disimpan di folder temp sistem, bukan direktori publik aplikasi.

## Pemeriksaan lanjutan screenshot pengguna

- Modal pilihan rilis dibuka dalam kedua tema: latar, judul, kartu, ikon, tombol tutup, dan CTA kini memakai palet bersama. Gradien gelap tidak lagi tertinggal di tema terang.
- Tab Detail Report dibuka dan kuartal Q2 dipilih: label filter dan tombol Ekspor Excel memiliki warna eksplisit pada kedua tema.
- DataTables Releases: pencarian diisi dan jumlah baris diganti ke 25; nilai terlihat gelap di atas input putih pada tema terang.
- Filter status transaksi dipilih dan pencarian diisi; select beserta option menggunakan palet terang. Wrapper periode Analytics, kontrolnya, dan tanggal juga terverifikasi terang.
- Tombol riwayat pembayaran memakai teks putih di atas oranye tua; ikon mewarisi warna tombol.
- Versi kedua stylesheet tema memakai hash isi file, sehingga perubahan CSS menghasilkan URL baru setelah halaman dimuat ulang.
- Pemeriksaan browser menggunakan snapshot lokal tanpa pengiriman form/fetch data akun. Pemeriksaan ini memverifikasi tampilan dan perubahan pilihan lokal, bukan pengambilan laporan atau ekspor server.

## Kartu ringkasan admin dan tabel Log Activity

- Aturan gradasi gelap khusus light mode pada `.admin-summary-grid`, `.automation-summary`, dan `.roles-summary` dihapus karena bertabrakan dengan warna teks palet bersama. Kartu sekarang memakai `--sf-surface`, angka `--sf-text`, dan keterangan `--sf-muted` pada kedua tema.
- Pesan log dan nama pengguna yang dibuat oleh DataTables mengikuti palet. Badge info/error/warning/default memiliki pasangan latar dan warna teks yang terbaca pada light mode.
- Browser memverifikasi Announcements, Log Activity, Automation, dan User Roles pada kedua tema. Tabel log pada fixture memakai tiga pesan contoh lokal untuk memeriksa hasil render DataTables; data log aplikasi tidak diubah.
