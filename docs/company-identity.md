# Identitas perusahaan

Setting System → Nama Perusahaan memuat nama perusahaan, alamat lengkap, direktur utama, tanda tangan direktur utama, nama kontak person, alamat korespondensi, nomor telepon, dan email. Data awal mengikuti identitas yang sebelumnya tertulis di template aplikasi.

Data disimpan melalui SystemSettings. Tanda tangan menerima PNG/JPG/WebP maksimal 2 MB dan 4096×4096 piksel; gambar dinormalisasi menjadi PNG di `storage/app/private/company-signatures`. Pratinjau hanya dilayani melalui rute admin dengan izin `manage-automation`, menggunakan respons privat tanpa cache. File tidak ditempatkan di direktori publik. Tanpa upload baru, tanda tangan dipertahankan; checkbox hapus menghapus tanda tangan terkonfigurasi. Jika belum ada pengaturan tanda tangan, file lama `storage/app/sign.jpeg` tetap menjadi fallback.

Penanda identitas penyedia layanan: `[PROVIDER_NAME]`, `[PROVIDER_ADDRESS]`, `[PROVIDER_DIRECTOR]`, `[PROVIDER_CONTACT_NAME]`, `[PROVIDER_CONTACT_ADDRESS]`, `[PROVIDER_PHONE]`, `[PROVIDER_EMAIL]`, dan `[PROVIDER_SIGNATURE]`. `[SIGNATURE1]` tetap merupakan alias tanda tangan direktur. Penanda `[COMPANYNAME]` dan data PIC lainnya tetap menunjuk perusahaan client.

Identitas lama pada template bawaan Aggregator dan Publishing dipetakan ke penanda ini sebelum data client diisi. Draft Aggregator tersimpan yang masih memakai teks bawaan juga dipetakan saat dibaca. Draft lain yang dibuat sendiri perlu menggunakan penanda `[PROVIDER_…]`. Nama perusahaan pada syarat layanan menggunakan pengaturan yang sama.

Kontrak Aggregator yang memiliki snapshot `generated_html` mempertahankan identitas dan gambar tanda tangan saat digenerate, termasuk setelah file tanda tangan diganti/dihapus. Kontrak lama tanpa snapshot dan PDF Publishing yang dirender langsung mengikuti konfigurasi terkini saat dicetak. PDF manual tidak diubah.

Pengujian meliputi data perusahaan pada semua template bawaan, pemisahan alamat kantor dan korespondensi, escaping nama perusahaan di syarat layanan, validasi upload, akses privat, penggantian/penghapusan file, PDF dengan gambar tanda tangan, serta ketetapan snapshot kontrak. Pengujian menggunakan SQLite dalam memori dan direktori sementara; pemeriksaan browser menggunakan snapshot lokal tanpa menyimpan perubahan perusahaan nyata.

Nama Pimpinan menggunakan pengaturan `company_director`; Jabatan menggunakan `company_position` dengan nilai awal Direktur Utama. Keduanya tersimpan melalui tabel `system_settings`. Token `[PROVIDER_DIRECTOR]` mengisi nama pimpinan, dan `[PROVIDER_POSITION]` mengisi jabatannya pada kontrak baru. Jabatan pihak client tetap mengikuti data profil client. Snapshot kontrak yang sudah dibuat mempertahankan identitas saat dibuat.