<div class="card mb-4"><div class="card-body p-4">
    <h4>Prefix Kode Contract</h4>
    <p class="text-secondary">Atur kode tengah nomor kontrak Aggregator yang saat ini menggunakan {{ $settings['aggregator_contract_prefix'] }}.</p>
    <form action="{{ route('admin.system-settings.update') }}" method="POST">
        @csrf @method('PUT')<input type="hidden" name="section" value="contract_prefix">
        <label for="aggregator_contract_prefix" class="form-label">Prefix Kode Contract Aggregator</label>
        <input id="aggregator_contract_prefix" name="aggregator_contract_prefix" class="form-control" value="{{ old('aggregator_contract_prefix', $settings['aggregator_contract_prefix']) }}" required maxlength="50" pattern="[A-Za-z0-9][A-Za-z0-9._-]*" aria-describedby="contract-prefix-help">
        <div id="contract-prefix-help" class="form-text">Huruf, angka, titik, garis bawah, atau tanda hubung; maksimal 50 karakter. Huruf disimpan sebagai kapital.</div>
        @error('aggregator_contract_prefix')<div class="text-danger mt-2">{{ $message }}</div>@enderror
        <p class="mt-3 mb-1">Contoh: <strong id="contract-prefix-example">001/{{ old('aggregator_contract_prefix', $settings['aggregator_contract_prefix']) }}/X/{{ now()->year }}</strong></p>
        <p class="form-text">Berlaku untuk kontrak baru. Nomor kontrak lama tetap menggunakan kode saat dibuat. Pengaturan tersimpan di database.</p>
        <button type="submit" class="btn btn-primary mt-2">Simpan Prefix Contract</button>
    </form>
</div></div>
<script>
document.getElementById('aggregator_contract_prefix').addEventListener('input', event => {
    document.getElementById('contract-prefix-example').textContent = '001/'+event.target.value.trim().toUpperCase()+'/X/{{ now()->year }}';
});
</script>
<div class="card"><div class="card-body p-4">
    <h4>Draft Kontrak Aggregator</h4>
    <p class="text-secondary">Kelola draft Non PT dan PT. Data profil serta persentase user akan mengisi penanda saat kontrak digenerate. Draft awal menggunakan kontrak yang sudah tersedia di aplikasi.</p>
    <div class="alert alert-info">Kontrak yang sudah digenerate menyimpan salinan isinya sendiri. Perubahan draft berlaku untuk kontrak baru. Upload PDF per user tetap opsional.</div>
    <details class="mb-4"><summary>Daftar penanda data otomatis</summary>
        <div class="d-flex flex-wrap gap-2 mt-3">@foreach(\App\Services\AggregatorContractTemplates::FIELDS as $field)<code>[{{ $field }}]</code>@endforeach</div>
        <p class="form-text mt-2">Wajib: [FULLNAME], [USER_PERCENT], dan [SYSTEM_PERCENT]. Tambahkan simbol % setelah penanda persentase. PT memakai data perusahaan dan PIC; Non PT memakai data perorangan.</p>
        <p class="form-text">Penanda [PROVIDER_…] mengambil identitas dari tab Nama Perusahaan. [PROVIDER_DIRECTOR] mengisi Nama Pimpinan dan [PROVIDER_POSITION] mengisi Jabatan. [COMPANYNAME] merupakan nama perusahaan client. [PROVIDER_SIGNATURE] atau [SIGNATURE1] memakai tanda tangan yang tersimpan.</p>
    </details>
    <form action="{{ route('admin.system-settings.update') }}" method="POST">
        @csrf @method('PUT')<input type="hidden" name="section" value="contracts">
        @foreach(['personal' => 'Non PT', 'company' => 'PT'] as $kind => $label)
            @php($key = 'aggregator_draft_'.$kind)
            <section class="border rounded p-3 mb-4">
                <h5>Draft Kontrak {{ $label }}</h5>
                <label for="import-{{ $kind }}" class="form-label">Impor draft HTML / TXT (opsional)</label>
                <input type="file" id="import-{{ $kind }}" class="form-control mb-3" accept=".html,.htm,.txt,text/html,text/plain" data-contract-import="{{ $key }}">
                <label for="{{ $key }}" class="form-label">Isi draft (HTML)</label>
                <textarea id="{{ $key }}" name="{{ $key }}" class="form-control font-monospace" rows="14" required maxlength="250000" spellcheck="false">{{ old($key, $contractDrafts[$kind]) }}</textarea>
                @error($key)<div class="text-danger mt-2">{{ $message }}</div>@enderror
                <div class="form-text">Gunakan penanda di atas untuk data yang berbeda per user. Maksimal 250.000 karakter. HTML dokumen tanpa script atau aset eksternal.</div>
                <button type="button" class="btn btn-outline-primary mt-3" data-contract-preview="{{ $key }}" aria-controls="preview-{{ $kind }}">Pratinjau Draft {{ $label }}</button>
                <iframe id="preview-{{ $kind }}" title="Pratinjau Draft {{ $label }}" sandbox="" class="w-100 border rounded mt-3 bg-white" style="height:520px" hidden></iframe>
            </section>
        @endforeach
        <button type="submit" class="btn btn-primary">Simpan Draft Kontrak</button>
    </form>
</div></div>
<script>
document.querySelectorAll('[data-contract-import]').forEach(input => input.addEventListener('change', async () => {
    const file = input.files[0];
    if (!file) return;
    if (!/\.(html?|txt)$/i.test(file.name) || file.size > 1000000) { alert('Pilih file HTML atau TXT maksimal 1 MB.'); input.value = ''; return; }
    let value = await file.text();
    if (/\.txt$/i.test(file.name)) {
        value = value.split(/\r?\n/).map(line => '<p>'+line.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')+'</p>').join('\n');
    }
    document.getElementById(input.dataset.contractImport).value = value;
}));
document.querySelectorAll('[data-contract-preview]').forEach(button => button.addEventListener('click', () => {
    const frame = document.getElementById(button.getAttribute('aria-controls'));
    const html = document.getElementById(button.dataset.contractPreview).value;
    frame.srcdoc = '<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; style-src \'unsafe-inline\'; img-src data:"><style>body{font-family:Arial,sans-serif;color:#172033;background:white;padding:24px;line-height:1.5}</style>'+html;
    frame.hidden = false;
}));
</script>
