<div class="card mb-4"><div class="card-body p-4">
    <div class="d-flex align-items-center gap-2 mb-2"><span class="material-icons-outlined" aria-hidden="true">cloud_upload</span><h4 class="mb-0">Setting Google Drive</h4></div>
    <p class="text-secondary">Pindahkan WAV/audio, cover art, thumbnail, dan IPL ke Google Drive setelah rilis berstatus Released. File lokal dihapus setelah ukuran dan checksum salinan Drive cocok.</p>
    @if(!$driveReady)<div class="alert alert-warning">Jalankan <code>php artisan migrate --force</code> untuk menyiapkan riwayat pemindahan.</div>@endif
    <form method="POST" action="{{ route('admin.system-settings.drive.save') }}">
        @csrf<input type="hidden" name="section" value="google-drive"><input type="hidden" name="drive_enabled" value="0">
        <div class="form-check form-switch mb-4"><input class="form-check-input" id="drive-enabled" name="drive_enabled" type="checkbox" value="1" @checked(old('section') === 'google-drive' ? old('drive_enabled') : $settings['drive_enabled'])><label class="form-check-label" for="drive-enabled">Pindahkan file released secara otomatis</label></div>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="drive-remote">Nama Remote rclone</label><input class="form-control" id="drive-remote" name="drive_remote" value="{{ old('drive_remote',$settings['drive_remote']) }}" required maxlength="100"><div class="form-text">Contoh: <code>gdrive</code>, tanpa tanda titik dua. Remote harus bertipe Google Drive.</div></div>
            <div class="col-md-6"><label class="form-label" for="drive-folder">Folder Tujuan di Google Drive</label><input class="form-control" id="drive-folder" name="drive_folder" value="{{ old('drive_folder',$settings['drive_folder']) }}" required maxlength="500"><div class="form-text">Contoh: <code>DimensiSuara/Rilis</code>. Gunakan nama folder, bukan URL berbagi.</div></div>
            <div class="col-md-6"><label class="form-label" for="drive-binary">Lokasi rclone</label><input class="form-control" id="drive-binary" name="drive_binary" value="{{ old('drive_binary',$settings['drive_binary']) }}" required maxlength="500"><div class="form-text">Contoh: <code>/usr/bin/rclone</code> atau <code>rclone</code> jika tersedia di PATH PHP.</div></div>
            <div class="col-md-6"><label class="form-label" for="drive-config">Lokasi rclone.conf</label><input class="form-control" id="drive-config" name="drive_config" value="{{ old('drive_config',$settings['drive_config']) }}" maxlength="1000" placeholder="/home/akun/.config/rclone/rclone.conf"><div class="form-text">Kosongkan untuk konfigurasi bawaan pengguna PHP. Konfigurasi OAuth tetap berada di VPS dan harus terbaca serta dapat diperbarui oleh pengguna PHP/worker.</div></div>
        </div>
        <p class="form-text mt-3">Simpan koneksi dengan otomatis nonaktif → Tes Koneksi → aktifkan pemindahan. Menonaktifkan otomatis menghentikan pemindahan baru; file yang sudah di Drive tetap dapat diakses.</p>
        <button class="btn btn-primary mt-2" type="submit">Simpan Setting Google Drive</button>
    </form>
    <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
        <form method="POST" action="{{ route('admin.system-settings.drive.test') }}">@csrf<button class="btn btn-outline-primary" type="submit"><span class="material-icons-outlined align-middle me-1" aria-hidden="true">cloud_done</span>Tes Koneksi</button></form>
        @if($driveReady)<form method="POST" action="{{ route('admin.system-settings.drive.start') }}">@csrf<button class="btn btn-outline-primary" type="submit" @disabled(!$settings['drive_enabled'])>Pindahkan File Released Sekarang</button></form>@endif
        @if($settings['drive_connection_status'])<span role="status" class="text-secondary">{{ $settings['drive_connection_status'] === 'ok' ? 'Koneksi berhasil' : 'Tes koneksi gagal' }} · {{ $settings['drive_checked_at'] }}</span>@endif
    </div>
    <p class="form-text mt-3">Proses lama ke FTP dilewati saat Google Drive aktif. File yang sudah dipindahkan ke FTP tetap memakai koneksi FTP. Audio yang dipakai beberapa rilis menunggu sampai seluruh rilis terkait berstatus Released.</p>
</div></div>
<div class="card mb-4"><div class="card-body p-4"><h5>Worker dan Jadwal VPS</h5>
    <p>Jalankan worker khusus berikut melalui Supervisor atau layanan VPS:</p>
    <pre class="border rounded p-3 text-wrap"><code>php artisan queue:work drive-archive --queue=drive-archive --timeout=900 --tries=1</code></pre>
    <p>Aktifkan Laravel Scheduler setiap menit dari direktori aplikasi. Sistem akan memeriksa file released setiap lima menit:</p>
    <pre class="border rounded p-3 text-wrap"><code>* * * * * cd /path/aplikasi &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code></pre>
    <p class="form-text mb-0">WAV tidak dikonversi. Akses audio tetap melalui aplikasi dengan hak akses pengguna dan dukungan seek. Tidak ada salinan WAV permanen yang dibuat saat diputar; trafik dan latensi tetap melewati VPS. Jangan menghapus remote atau rclone.conf setelah file dipindahkan.</p>
</div></div>
@if($driveReady)
@php($driveStatuses = ['pending'=>'Menunggu','copying'=>'Menyalin','cleanup_pending'=>'Di Drive; penghapusan lokal tertunda','complete'=>'Selesai','failed'=>'Gagal; file lokal dipertahankan'])
<div class="card"><div class="card-body p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2"><h5 class="mb-0">Riwayat Pemindahan</h5><a class="btn btn-outline-primary" href="{{ route('admin.system-settings.index',['tab'=>'google-drive']) }}">Perbarui Progres</a></div>
    <div class="d-flex flex-wrap gap-4 my-3"><span>Selesai: <strong>{{ $driveStats->get('complete')->total ?? 0 }}</strong></span><span>Gagal: <strong>{{ $driveStats->get('failed')->total ?? 0 }}</strong></span><span>Ukuran diproses: <strong>{{ number_format(($driveStats->get('complete')->bytes ?? 0)/1073741824,2,',','.') }} GB</strong></span></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>ID File</th><th>Status</th><th>Ukuran</th><th>Terakhir Diproses</th><th>Keterangan</th></tr></thead><tbody>
        @forelse($driveHistory as $transfer)<tr><td>#{{ $transfer->file_id }}</td><td>{{ $driveStatuses[$transfer->status] ?? $transfer->status }}</td><td>{{ number_format($transfer->size/1048576,2,',','.') }} MB</td><td>{{ $transfer->updated_at }}</td><td>{{ $transfer->error ?: ($transfer->verified_at ? 'Checksum terverifikasi' : 'Menunggu verifikasi') }}</td></tr>@empty<tr><td colspan="5" class="text-center text-secondary">Belum ada file yang dipindahkan.</td></tr>@endforelse
    </tbody></table></div><p class="form-text mb-0">Menampilkan 30 proses terbaru. File yang gagal akan diperiksa lagi pada jadwal berikutnya. File di Google Drive dipertahankan ketika data di aplikasi dihapus; bersihkan arsip Drive secara terpisah setelah ditinjau.</p>
</div></div>
@endif
