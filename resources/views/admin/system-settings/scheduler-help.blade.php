<details class="mt-3">
    <summary>Cara mengisi Scheduled Tasks di Plesk</summary>
    <div class="alert alert-info mt-3">Tulisan <code>/path/aplikasi</code> adalah contoh, bukan lokasi server Anda. Gunakan pilihan <strong>Run a PHP script</strong> dan pilih file melalui ikon folder agar path tidak salah.</div>
    <ol class="mt-2">
        <li>Buka Domains → domain aplikasi → Scheduled Tasks → Add Task, lalu centang <strong>Active</strong>.</li>
        <li>Pilih <strong>Run a PHP script</strong>.</li>
        <li>Pada <strong>Script path</strong>, klik ikon folder dan pilih file <code>artisan</code> di folder aplikasi. Jika aplikasi berada di httpdocs, hasilnya <code>httpdocs/artisan</code>. Jangan memilih folder <code>public</code> atau <code>index.php</code>.</li>
        <li>Pada <strong>with arguments</strong>, isi hanya <code>schedule:run</code>.</li>
        <li>Pilih PHP CLI yang sesuai dengan website, minimal <strong>PHP 8.2</strong>.</li>
        <li>Pilih <strong>Cron style</strong> dan isi <code>* * * * *</code> (setiap menit). Pilih <strong>Errors only</strong> untuk pemberitahuan error task.</li>
        <li>Klik <strong>Run Now</strong>, lalu <strong>OK</strong> jika berhasil. Jika gagal, buka <strong>See details</strong> untuk membaca error lengkap.</li>
    </ol>
    <p class="mb-1">Lokasi file artisan yang terdeteksi pada server halaman ini:</p>
    <code class="d-block border rounded p-3 text-break">{{ base_path('artisan') }}</code>
    <p class="form-text mt-2">Path di atas adalah referensi lokasi pada server ini. Pemilih file Plesk akan mengisi path relatif yang sesuai. Tidak perlu menambahkan cd, php, atau pengalihan output pada kolom arguments. Cukup satu task schedule:run; tugas aplikasi dan worker Drive yang aktif dijalankan oleh scheduler.</p>
</details>
