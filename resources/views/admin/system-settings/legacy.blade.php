<div class="card mb-4"><div class="card-body p-4">
    <h4>Import Data Lama</h4>
    <p class="text-secondary">Pindahkan pelanggan, rilis, lagu, sampul, audio, dokumen identitas klien, kontrak lama, dan dokumen IPL dari website Dimensi lama. File sumber tetap berada di website lama; salinannya disimpan privat di aplikasi ini.</p>
    @if(!$importsReady)
        <div class="alert alert-warning" role="alert"><strong>Tabel impor belum siap.</strong> Form unggah tersedia di bawah, tetapi belum dapat digunakan. Di terminal hosting, jalankan <code>php artisan migrate --force</code> dari folder aplikasi, lalu muat ulang halaman ini. Pada tab Artisan Laravel Toolkit Plesk, cukup masukkan <code>migrate --force</code> karena awalan <code>php artisan</code> sudah disediakan.</div>
    @endif
        <div class="alert alert-info">Mulai dengan pemeriksaan SQL. Data pelanggan dan katalog baru disimpan setelah Anda menekan <strong>Import ke Database</strong>. Akun admin lama tidak diaktifkan otomatis. Konflik email, UPC, dan ISRC dilaporkan, bukan ditimpa.</div>
        @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('admin.system-settings.legacy.store') }}" enctype="multipart/form-data">
            @csrf<input type="hidden" name="section" value="legacy">
            <fieldset @disabled(!$importsReady) aria-describedby="legacy-upload-help">
            <div class="mb-3"><label class="form-label" for="legacy-sql">Upload File Database Lama (.sql)</label><input class="form-control" id="legacy-sql" type="file" name="sql" accept=".sql"><div class="form-text" id="legacy-upload-help">Pilih file database hasil ekspor phpMyAdmin (.sql) dengan INSERT beserta nama kolom. Batas aplikasi 100 MB; batas unggah hosting juga berlaku.</div></div>
            <div class="mb-3"><label class="form-label" for="legacy-server-sql">Atau nama file SQL di server</label><input class="form-control" id="legacy-server-sql" name="server_sql" value="{{ old('server_sql') }}" placeholder="database-lama.sql"><div class="form-text">Untuk melewati batas unggah browser, letakkan SQL melalui File Manager/SFTP di <code class="text-break">{{ config('legacy-import.inbox') }}</code>, lalu masukkan nama filenya.</div></div>
            <div class="mb-3"><label class="form-label" for="legacy-media-root">Folder utama media website lama (opsional)</label><input class="form-control" id="legacy-media-root" name="media_root" value="{{ old('media_root') }}" placeholder="/var/www/vhosts/domain-lama/httpdocs"><div class="form-text">Isi path absolut pada server. Contoh: jika SQL menyimpan <code>/uploads/covers/lagu.jpg</code>, pilih folder yang berisi <code>uploads</code>. URL hanya dibaca sebagai path; file disalin langsung dari disk.</div></div>
            <button class="btn btn-primary" type="submit"><span class="material-icons-outlined align-middle me-1" aria-hidden="true">fact_check</span>Periksa SQL Lama</button>
            </fieldset>
        </form>
        <p class="form-text mt-3">Untuk melengkapi KTP, NPWP, NIB, SK Kemenkumham, tanda tangan, dan kontrak klien dari impor sebelumnya, buka hasil impor lalu jalankan kembali Salin Media dengan folder sumber yang benar. Profil dan rilisan tidak dibuat ulang; dokumen yang berbeda tidak ditimpa.</p><details class="mt-4"><summary>Persiapan hosting dan worker impor</summary>
            <p class="mt-2">Worker khusus ini harus aktif agar pemeriksaan, impor, dan penyalinan WAV berjalan. Gunakan PHP CLI yang sama dengan aplikasi.</p>
            <pre class="border rounded p-3 text-wrap"><code>php artisan queue:work legacy-import --queue=legacy-import --timeout=900 --tries=1</code></pre>
            <p>Jika menggunakan Scheduled Tasks Plesk, jalankan setiap menit dari folder aplikasi:</p>
            <pre class="border rounded p-3 text-wrap"><code>php artisan queue:work legacy-import --queue=legacy-import --timeout=900 --tries=1 --stop-when-empty --max-time=50</code></pre>
            <p class="form-text">Folder sumber harus terbaca oleh PHP dan berada di dalam <code class="text-break">{{ config('legacy-import.allowed_root') }}</code>. Jika website lama berada di luar folder tersebut, administrator server dapat mengatur <code>LEGACY_IMPORT_ALLOWED_ROOT</code> di .env lalu menjalankan <code>php artisan config:clear</code>. Arsip SQL disimpan privat; hapus salinannya setelah migrasi terverifikasi.</p>
        </details>
</div></div>
@if($importsReady && $selectedImport)
@php($statusLabels = ['trial_queued'=>'Menunggu / menjalankan pemeriksaan SQL', 'trial_ready'=>'Pemeriksaan selesai', 'trial_failed'=>'Pemeriksaan gagal', 'apply_queued'=>'Menunggu / menjalankan impor database', 'apply_failed'=>'Impor database gagal', 'imported'=>'Database berhasil diimpor; media belum disalin', 'media_queued'=>'Menunggu / menyalin media', 'media_failed'=>'Penyalinan media terhenti', 'media_issues'=>'Penyalinan selesai dengan kendala', 'complete'=>'Database dan referensi media selesai diproses', 'identities_queued'=>'Menunggu import dokumen identitas', 'identities_complete'=>'Dokumen identitas selesai diproses', 'identities_issues'=>'Import dokumen identitas selesai dengan kendala', 'identities_failed'=>'Import dokumen identitas terhenti'])
<div class="card mb-4"><div class="card-body p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3"><h5 class="mb-0">Hasil Import #{{ $selectedImport->id }}</h5><a class="btn btn-outline-primary" href="{{ route('admin.system-settings.index', ['tab'=>'legacy', 'import'=>$selectedImport->id]) }}">Perbarui Progres</a></div>
    <p role="status"><strong>{{ in_array($selectedImport->status,['media_queued','identities_queued'],true) ? $selectedImport->mediaQueueLabel() : ($statusLabels[$selectedImport->status] ?? $selectedImport->status) }}</strong></p>
    @if(str_ends_with($selectedImport->status, '_queued'))
        <div class="alert alert-info">Permintaan masuk antrean khusus <strong>legacy-import</strong>. Klik ulang atau memuat ulang halaman tidak menjalankan worker. Pastikan worker atau Scheduled Tasks hosting aktif, lalu klik <strong>Perbarui Progres</strong>.
        <pre class="mt-2 mb-0 text-wrap"><code>php artisan queue:work legacy-import --queue=legacy-import --timeout=900 --tries=1 --stop-when-empty</code></pre></div>
        @if($selectedImport->started_at)<p class="form-text">Worker pertama memproses tahap ini: {{ $selectedImport->started_at->format('d-m-Y H:i:s') }}. Aktivitas terakhir: {{ $selectedImport->updated_at->format('d-m-Y H:i:s') }}. Jika waktu ini tidak berubah, periksa worker dan log hosting.</p>@else<p class="form-text">Worker belum mulai memproses tahap ini.</p>@endif
    @endif
    @if($selectedImport->error)<div class="alert alert-danger">{{ $selectedImport->error }}</div>@endif
    @if($selectedImport->status === 'trial_ready')<p class="form-text">Percobaan dibatalkan kembali sehingga pelanggan dan katalog belum ditambahkan. Nomor urut database dapat bertambah selama percobaan. Hasil berikut menunjukkan data yang akan diproses.</p>@endif
    @if($selectedImport->stats)
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Pemeriksaan</th><th class="text-end">Jumlah</th></tr></thead><tbody>@foreach($selectedImport->stats as $name=>$count)<tr><td>{{ $name }}</td><td class="text-end">{{ $count }}</td></tr>@endforeach</tbody></table></div>
    @endif
    @if($selectedImport->status === 'trial_ready')
        <form method="POST" action="{{ route('admin.system-settings.legacy.action', [$selectedImport->id, 'apply']) }}">@csrf<input type="hidden" name="section" value="legacy">
            <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="legacy-backup" name="backup_confirmed" value="1" required><label class="form-check-label" for="legacy-backup">Backup database baru sudah tersedia dan hasil pemeriksaan sudah ditinjau.</label></div>
            <button class="btn btn-primary" type="submit">Import ke Database</button>
        </form>
    @elseif(in_array($selectedImport->status, ['trial_failed', 'apply_failed'], true))
        <form method="POST" action="{{ route('admin.system-settings.legacy.action', [$selectedImport->id, 'trial']) }}">@csrf<input type="hidden" name="section" value="legacy"><button class="btn btn-outline-primary">Ulangi Pemeriksaan SQL</button></form>
    @endif
    @if(in_array($selectedImport->status, ['imported', 'media_failed', 'media_issues', 'complete', 'identities_failed', 'identities_issues', 'identities_complete'], true))
        <form class="mt-3" method="POST" action="{{ route('admin.system-settings.legacy.action', [$selectedImport->id, 'media']) }}">@csrf<input type="hidden" name="section" value="legacy">
            <label class="form-label" for="legacy-retry-root">Folder utama media website lama</label><input class="form-control mb-2" id="legacy-retry-root" name="media_root" value="{{ $selectedImport->media_root }}" required>
            <p class="form-text">Tombol <strong>Import Dokumen Identitas Saja</strong> hanya melengkapi dokumen klien yang belum ada dan mempertahankan dokumen tujuan yang tersedia. WAV dan cover art tidak diproses. Pilih folder utama aplikasi lama, misalnya <code>/var/www/vhosts/dimensisuara.id/httpdocs</code>, yang berisi <code>public/uploads</code> dan <code>storage/private</code>. Jangan isi sampai <code>uploads</code>. Referensi <code>/uploads/...</code> juga diperiksa di <code>public/uploads</code>. Dokumen klien, sampul, dan audio dipasangkan melalui ID sumber yang sudah dipetakan. WAV disalin utuh dan checksum diverifikasi. Media tujuan yang berbeda tidak ditimpa. Setelah memperbaiki folder atau file hilang, Anda dapat mengulang penyalinan.</p>
            <button class="btn btn-primary me-2 mb-2" type="submit" formaction="{{ route('admin.system-settings.legacy.action', [$selectedImport->id, 'identities']) }}">Import Dokumen Identitas Saja</button>
            <button class="btn btn-outline-primary mb-2" type="submit">{{ $selectedImport->status === 'imported' ? 'Salin Dokumen, Gambar, Audio & IPL' : 'Periksa dan Ulangi Media' }}</button>
        </form>
    @endif
    @if($selectedImport->identitiesOnly())<p class="alert alert-info mt-3">Mode dokumen identitas saja: WAV, cover art, dan IPL tidak diproses ulang. Hitungan dan kendala di bawah hanya untuk dokumen pengguna.</p>@endif
    <div class="d-flex flex-wrap gap-4 mt-4"><span>{{ $selectedImport->identitiesOnly() ? 'Pengguna diperiksa:' : 'Data pengguna/rilis/lagu diproses:' }} <strong>{{ $selectedImport->media_processed }}</strong></span><span>File terverifikasi: <strong>{{ $importMediaCounts['copied'] ?? 0 }}</strong></span><span>Dokumen dipertahankan: <strong>{{ $importMediaCounts['kept'] ?? 0 }}</strong></span><span>File perlu diperiksa: <strong>{{ $importMediaCounts['issue'] ?? 0 }}</strong></span></div>
    @if(in_array($selectedImport->status,['media_queued','identities_queued'],true))<p class="form-text mt-2"><strong>Hasil belum final.</strong> Jumlah file dan daftar kendala di bawah masih dapat memuat hasil percobaan sebelumnya. Hasil setiap file diperbarui setelah worker memprosesnya kembali.</p>@endif
    @if($importMediaIssues && $importMediaIssues->count())
        <div class="alert alert-warning mt-3">File yang tidak ditemukan belum disalin. Cocokkan folder utama media dengan lokasi fisik file lama di File Manager dan pastikan PHP dapat membacanya. SQL hanya menyimpan referensi file. Setelah folder atau izin diperbaiki, klik <strong>Periksa dan Ulangi Media</strong> untuk memperbarui hasil dan lokasi file yang dicari.</div>
        <div class="table-responsive mt-3"><table class="table"><thead><tr><th>ID Pemetaan</th><th>Jenis</th><th>Kendala</th></tr></thead><tbody>@foreach($importMediaIssues as $issue)<tr><td>{{ $issue->record_id }}</td><td>{{ $issue->field }}</td><td class="text-break">{{ $issue->message }}</td></tr>@endforeach</tbody></table></div>{{ $importMediaIssues->links('pagination::bootstrap-5') }}
    @endif
    <p class="form-text mt-3">Format ini mendukung katalog Dimensi lama. KTP, NPWP, NIB, SK Kemenkumham, tanda tangan, dan file kontrak lama disalin ke dokumen profil. Kontrak lama menjadi arsip dokumen, bukan kontrak baru yang digenerasi. Saldo, transaksi, dan riwayat upload lainnya belum dipindahkan ke fitur baru. Akun admin tetap dikelola terpisah. Media dengan referensi kosong di SQL tidak memiliki file untuk disalin.</p>
</div></div>
@endif
@if($importsReady && $importRuns->count())
<div class="card"><div class="card-body p-4"><h5>Riwayat Import</h5><div class="table-responsive"><table class="table"><thead><tr><th>Import</th><th>Waktu</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@foreach($importRuns as $item)<tr><td>#{{ $item->id }}</td><td>{{ $item->created_at->format('d-m-Y H:i') }}</td><td>{{ in_array($item->status,['media_queued','identities_queued'],true) ? $item->mediaQueueLabel() : ($statusLabels[$item->status] ?? $item->status) }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.system-settings.index', ['tab'=>'legacy', 'import'=>$item->id]) }}">Lihat</a></td></tr>@endforeach</tbody></table></div></div></div>
@endif
