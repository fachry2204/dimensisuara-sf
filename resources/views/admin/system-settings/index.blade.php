@extends('layouts.app')
@section('title', 'Setting System')
@section('content')
<div class="system-settings mx-auto" style="max-width:1100px">
    <div class="mb-4">
        <small class="text-uppercase text-secondary">Server Management</small>
        <h2 class="mt-2">Setting System</h2>
        <p class="text-secondary">Kelola identitas aplikasi, perusahaan, tampilan, kontrak, dan koneksi layanan.</p>
    </div>
    @php($settingsTab = old('section', request('tab', 'general')))
    @php($contractTab = in_array($settingsTab, ['contracts', 'contract_prefix'], true))
    @php($companyTab = $settingsTab === 'company')
    @php($maintenanceTab = $settingsTab === 'maintenance')
    @php($legacyTab = $settingsTab === 'legacy')
    @php($driveTab = $settingsTab === 'google-drive')
    @php($emailNotificationTab = $settingsTab === 'notifications-email')
    @php($whatsappNotificationTab = $settingsTab === 'notifications-whatsapp')
    @php($generalTab = !$contractTab && !$companyTab && !$maintenanceTab && !$legacyTab && !$driveTab && !$emailNotificationTab && !$whatsappNotificationTab)
    <ul class="nav nav-pills gap-2 mb-4" role="tablist" aria-label="Setting System">
        <li class="nav-item"><button class="nav-link {{ $generalTab ? 'active' : '' }}" id="system-general-tab" data-bs-toggle="pill" data-bs-target="#system-general" type="button" role="tab" aria-controls="system-general" aria-selected="{{ $generalTab ? 'true' : 'false' }}">Pengaturan Umum</button></li>
        <li class="nav-item"><button class="nav-link {{ $maintenanceTab ? 'active' : '' }}" id="system-maintenance-tab" data-bs-toggle="pill" data-bs-target="#system-maintenance" type="button" role="tab" aria-controls="system-maintenance" aria-selected="{{ $maintenanceTab ? 'true' : 'false' }}">Maintenance</button></li>
        <li class="nav-item"><button class="nav-link {{ $companyTab ? 'active' : '' }}" id="system-company-tab" data-bs-toggle="pill" data-bs-target="#system-company" type="button" role="tab" aria-controls="system-company" aria-selected="{{ $companyTab ? 'true' : 'false' }}">Nama Perusahaan</button></li>
        <li class="nav-item"><button class="nav-link {{ $contractTab ? 'active' : '' }}" id="system-contract-tab" data-bs-toggle="pill" data-bs-target="#system-contract" type="button" role="tab" aria-controls="system-contract" aria-selected="{{ $contractTab ? 'true' : 'false' }}">Contract</button></li>
        <li class="nav-item"><button class="nav-link {{ $legacyTab ? 'active' : '' }}" id="system-legacy-tab" data-bs-toggle="pill" data-bs-target="#system-legacy" type="button" role="tab" aria-controls="system-legacy" aria-selected="{{ $legacyTab ? 'true' : 'false' }}">Import Data Lama</button></li>
        <li class="nav-item"><button class="nav-link {{ $driveTab ? 'active' : '' }}" id="system-drive-tab" data-bs-toggle="pill" data-bs-target="#system-drive" type="button" role="tab" aria-controls="system-drive" aria-selected="{{ $driveTab ? 'true' : 'false' }}">Setting Google Drive</button></li>
        <li class="nav-item"><button class="nav-link {{ $emailNotificationTab ? 'active' : '' }}" id="system-notifications-email-tab" data-bs-toggle="pill" data-bs-target="#system-notifications-email" type="button" role="tab" aria-controls="system-notifications-email" aria-selected="{{ $emailNotificationTab ? 'true' : 'false' }}">Notifikasi Email</button></li>
        <li class="nav-item"><button class="nav-link {{ $whatsappNotificationTab ? 'active' : '' }}" id="system-notifications-whatsapp-tab" data-bs-toggle="pill" data-bs-target="#system-notifications-whatsapp" type="button" role="tab" aria-controls="system-notifications-whatsapp" aria-selected="{{ $whatsappNotificationTab ? 'true' : 'false' }}">Notifikasi WhatsApp</button></li>
    </ul>
    <div class="tab-content border-0 bg-transparent p-0">
    <div class="tab-pane fade {{ $generalTab ? 'show active' : '' }}" id="system-general" role="tabpanel" aria-labelledby="system-general-tab">
    @include('admin.system-settings.colors')
    <div class="card mb-4"><div class="card-body p-4">
        <h5 class="d-flex align-items-center gap-2"><span class="material-icons-outlined">palette</span> Identitas Aplikasi</h5>
        <p class="text-secondary">Nama, logo, dan favicon digunakan di seluruh halaman aplikasi dan template email.</p>
        @if(!empty($missingBranding))
            <div class="alert alert-warning" role="alert">Berkas {{ implode(' dan ', $missingBranding) }} yang sebelumnya disimpan tidak ditemukan di server ini. Unggah ulang gambar di bawah, lalu klik Simpan Identitas Aplikasi.</div>
        @endif
        <form action="{{ route('admin.system-settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <input type="hidden" name="section" value="branding">
            <div class="mb-4"><label for="app_name" class="form-label">Nama Aplikasi</label>
                <input id="app_name" name="app_name" class="form-control" value="{{ old('app_name', $settings['app_name']) }}" required maxlength="100">
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="logo" class="form-label">Logo Aplikasi</label>
                    <div class="branding-preview border rounded p-3 mb-3 d-flex align-items-center justify-content-center" style="height:110px"><img id="logo-preview" src="{{ app_logo() }}" alt="Logo aplikasi" style="max-width:100%;max-height:80px;object-fit:contain"></div>
                    <input id="logo" name="logo" type="file" class="form-control" accept="image/png,image/jpeg,image/webp" data-preview="logo-preview">
                    <div class="form-text">PNG, JPG, atau WebP. Maksimal 2 MB. Logo transparan disarankan.</div>
                    @if($settings['logo'])<div class="form-check mt-2"><input id="remove_logo" class="form-check-input" type="checkbox" name="remove_logo" value="1"><label class="form-check-label" for="remove_logo">Kembalikan logo bawaan</label></div>@endif
                </div>
                <div class="col-md-6">
                    <label for="favicon" class="form-label">Favicon Website</label>
                    <div class="border rounded p-3 mb-3 d-flex align-items-center justify-content-center" style="height:110px"><img id="favicon-preview" src="{{ app_favicon() }}" alt="Favicon aplikasi" width="48" height="48" style="object-fit:contain"></div>
                    <input id="favicon" name="favicon" type="file" class="form-control" accept="image/png,image/x-icon,image/vnd.microsoft.icon,.ico" data-preview="favicon-preview">
                    <div class="form-text">PNG atau ICO. Maksimal 512 KB. Ukuran 32 × 32 atau 48 × 48 piksel disarankan.</div>
                    @if($settings['favicon'])<div class="form-check mt-2"><input id="remove_favicon" class="form-check-input" type="checkbox" name="remove_favicon" value="1"><label class="form-check-label" for="remove_favicon">Kembalikan favicon bawaan</label></div>@endif
                </div>
            </div>
            <p class="form-text mt-3">Setelah memilih gambar, klik Simpan Identitas Aplikasi agar logo dan favicon tersimpan.</p>
            <button class="btn btn-primary mt-4" type="submit">Simpan Identitas Aplikasi</button>
        </form>
    </div></div>
    <div class="card mb-4"><div class="card-body p-4">
        <h5 class="d-flex align-items-center gap-2"><span class="material-icons-outlined">hub</span> Nama Aggregator</h5>
        <p class="text-secondary">Kelola pilihan aggregator pada form tambah, edit, dan review rilis.</p>
        <form action="{{ route('admin.system-settings.update') }}" method="POST">
            @csrf @method('PUT')<input type="hidden" name="section" value="aggregators">
            <label for="aggregator_names" class="form-label">Daftar Nama Aggregator</label>
            <textarea id="aggregator_names" name="aggregator_names" class="form-control" rows="5" aria-describedby="aggregator-names-help" placeholder="Believe&#10;SoundOn&#10;Lokamusik">{{ implode("\n", (array) old('aggregator_names', $settings['aggregator_names'])) }}</textarea>
            <div id="aggregator-names-help" class="form-text">Tulis satu nama per baris. Tambahkan baris untuk menambah aggregator, atau hapus baris untuk mengurangi pilihan. Maksimal 100 nama, masing-masing 100 karakter, tanpa duplikat.</div>
            <p class="form-text mt-2">Menghapus nama dari daftar tidak mengubah aggregator yang sudah tersimpan pada rilis lama. Daftar kosong hanya menyediakan pilihan Unassigned pada form rilis.</p>
            <button class="btn btn-primary mt-3" type="submit">Simpan Nama Aggregator</button>
        </form>
    </div></div>
    <div class="card mb-4"><div class="card-body p-4">
        <h5 class="d-flex align-items-center gap-2"><span class="material-icons-outlined">mail</span> SMTP Gmail</h5>
        <p class="text-secondary">Atur akun pengirim email notifikasi aplikasi.</p>
        <form action="{{ route('admin.system-settings.update') }}" method="POST">
            @csrf @method('PUT')<input type="hidden" name="section" value="smtp">
            <input type="hidden" name="smtp_enabled" value="0">
            <div class="form-check form-switch mb-3"><input id="smtp_enabled" class="form-check-input" type="checkbox" name="smtp_enabled" value="1" @checked(old('section') === 'smtp' ? old('smtp_enabled') : $settings['smtp_enabled'])><label class="form-check-label" for="smtp_enabled">Aktifkan pengiriman SMTP</label></div>
            <div class="form-text mb-3">Jika dinonaktifkan, email hanya dicatat pada log aplikasi.</div>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="smtp_host">Host SMTP</label><input class="form-control" id="smtp_host" name="smtp_host" value="{{ old('smtp_host', $settings['smtp_host']) }}" placeholder="smtp.gmail.com" required></div>
                <div class="col-md-3"><label class="form-label" for="smtp_port">Port</label><input class="form-control" type="number" id="smtp_port" name="smtp_port" value="{{ old('smtp_port', $settings['smtp_port']) }}" min="1" max="65535" required></div>
                <div class="col-md-3"><label class="form-label" for="smtp_encryption">Enkripsi</label><select class="form-select" id="smtp_encryption" name="smtp_encryption"><option value="tls" @selected(old('smtp_encryption', $settings['smtp_encryption']) === 'tls')>TLS (587)</option><option value="ssl" @selected(old('smtp_encryption', $settings['smtp_encryption']) === 'ssl')>SSL (465)</option></select></div>
                <div class="col-md-6"><label class="form-label" for="smtp_username">Email Gmail / Username</label><input class="form-control" type="email" id="smtp_username" name="smtp_username" value="{{ old('smtp_username', $settings['smtp_username']) }}" autocomplete="off"></div>
                <div class="col-md-6"><label class="form-label" for="smtp_password">App Password Gmail</label><input class="form-control" type="password" id="smtp_password" name="smtp_password" autocomplete="new-password" placeholder="{{ $settings['has_smtp_password'] ? 'Tersimpan — kosongkan untuk mempertahankan' : 'Masukkan App Password Gmail' }}"><div class="form-text">Gunakan App Password dari akun Gmail. Password tersimpan tidak ditampilkan kembali.</div></div>
                <div class="col-md-6"><label class="form-label" for="smtp_from_address">Email Pengirim</label><input class="form-control" type="email" id="smtp_from_address" name="smtp_from_address" value="{{ old('smtp_from_address', $settings['smtp_from_address']) }}"></div>
                <div class="col-md-6"><label class="form-label" for="smtp_from_name">Nama Pengirim</label><input class="form-control" id="smtp_from_name" name="smtp_from_name" value="{{ old('smtp_from_name', $settings['smtp_from_name']) }}" maxlength="100" placeholder="{{ app_name() }}"><div class="form-text">Kosongkan untuk mengikuti Nama Aplikasi.</div></div>
            </div>
            <button class="btn btn-primary mt-4" type="submit">Simpan SMTP</button>
        </form>
    </div></div>
    <div class="card mb-4"><div class="card-body p-4">
        <h5 class="d-flex align-items-center gap-2"><span class="material-icons-outlined">chat</span> WhatsApp Gateway — Fonnte</h5>
        <p class="text-secondary">Gunakan token device Fonnte yang sudah terhubung ke WhatsApp. Pengirim ditentukan otomatis oleh token.</p>
        <p class="form-text">Jika sebelumnya menggunakan gateway lain, masukkan token Fonnte baru. Token gateway lama tidak digunakan untuk Fonnte.</p>
        <form action="{{ route('admin.system-settings.update') }}" method="POST">
            @csrf @method('PUT')<input type="hidden" name="section" value="whatsapp">
            <div class="mb-3"><label class="form-label" for="whatsapp_url">Endpoint Fonnte</label><input class="form-control" id="whatsapp_url" value="https://api.fonnte.com/send" readonly><div class="form-text">Endpoint resmi Fonnte digunakan otomatis.</div></div>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="whatsapp_number">Nomor Device Fonnte (opsional)</label><input class="form-control" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $settings['whatsapp_number']) }}" placeholder="6281234567890"><div class="form-text">Untuk pencatatan pengirim. Nomor device sebenarnya mengikuti token Fonnte.</div></div>
                <div class="col-md-6"><label class="form-label" for="whatsapp_token">Token Device Fonnte</label><input class="form-control" type="password" id="whatsapp_token" name="whatsapp_token" autocomplete="new-password" placeholder="{{ $settings['has_whatsapp_token'] ? 'Tersimpan — kosongkan untuk mempertahankan' : 'Masukkan token Fonnte' }}"><div class="form-text">Salin token dari dashboard Fonnte. Tersimpan terenkripsi di database. Kosongkan untuk mempertahankan token.</div></div>
            </div>
            <button class="btn btn-primary mt-4" type="submit">Simpan WhatsApp Gateway</button>
        </form>
    </div></div>
    </div>
    <div class="tab-pane fade {{ $contractTab ? 'show active' : '' }}" id="system-contract" role="tabpanel" aria-labelledby="system-contract-tab">
        @include('admin.system-settings.contracts')
    </div>
    <div class="tab-pane fade {{ $companyTab ? 'show active' : '' }}" id="system-company" role="tabpanel" aria-labelledby="system-company-tab">
        @include('admin.system-settings.company')
    </div>
    <div class="tab-pane fade {{ $maintenanceTab ? 'show active' : '' }}" id="system-maintenance" role="tabpanel" aria-labelledby="system-maintenance-tab">
        @include('admin.system-settings.maintenance')
    </div>
    <div class="tab-pane fade {{ $driveTab ? 'show active' : '' }}" id="system-drive" role="tabpanel" aria-labelledby="system-drive-tab">
        @include('admin.system-settings.google-drive')
    </div>
    <div class="tab-pane fade {{ $legacyTab ? 'show active' : '' }}" id="system-legacy" role="tabpanel" aria-labelledby="system-legacy-tab">
        @include('admin.system-settings.legacy')
    </div>
    @foreach(['email'=>$emailNotificationTab,'whatsapp'=>$whatsappNotificationTab] as $notificationChannel=>$notificationActive)
    <div class="tab-pane fade {{ $notificationActive ? 'show active' : '' }}" id="system-notifications-{{ $notificationChannel }}" role="tabpanel" aria-labelledby="system-notifications-{{ $notificationChannel }}-tab">
        @include('admin.system-settings.notifications', ['channel'=>$notificationChannel])
    </div>
    @endforeach
    </div>
</div>
<script>
document.querySelectorAll('[data-preview]').forEach(input => {
    let previewUrl;
    input.addEventListener('change', () => {
        if (!input.files[0]) return;
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(input.files[0]);
        const preview = document.getElementById(input.dataset.preview);
        preview.src = previewUrl;
        preview.hidden = false;
        if (preview.nextElementSibling?.hasAttribute('data-empty-preview')) preview.nextElementSibling.hidden = true;
    });
});
</script>
@endsection
