# Warna utama aplikasi

Setting System → Pengaturan Umum → Warna Utama menyediakan mode Solid dan Gradasi, dua pemilih warna, arah gradasi 0–360 derajat, dan pratinjau langsung. Klik Simpan Warna Utama untuk menerapkannya; halaman lain mengambil pengaturan saat dimuat ulang. Warna awal tetap oranye aplikasi.

Pengaturan disimpan melalui SystemSettings terenkripsi, memakai izin server yang sama (`manage-automation`). Server hanya menerima kode hex enam digit, mode yang tersedia, dan sudut berupa bilangan bulat.

`PrimaryColors` menentukan warna teks hitam/putih, warna hover, dan aksen yang terbaca pada kedua tema. Gradasi dengan perbedaan luminansi ekstrem diberi penyesuaian kecerahan agar teks memiliki kontras minimal. Warna outline mengikuti aksen, lalu memakai warna/gradasi utama saat hover/fokus. Kontrol disabled tetap memakai palet netral.

Token warna disisipkan melalui `layouts.primary-colors` di layout bersama. `resources/css/primary-colors.css` menghubungkan token ke tombol Bootstrap, aksi kustom, login/registrasi, tab aktif, dan tanda pilihan kontrol. Warna badge status, grafik, serta ikon kategori tidak diubah oleh pengaturan ini.

Verifikasi otomatis mencakup penyimpanan, penerapan pada login, penolakan input warna tidak valid, izin pengaturan, pemilihan warna teks, dan kontras gradasi ekstrem. Pemeriksaan browser menggunakan konfigurasi sementara terpisah dengan contoh gradasi biru/ungu dan solid kuning; tidak mengganti pilihan warna aplikasi yang tersimpan.
