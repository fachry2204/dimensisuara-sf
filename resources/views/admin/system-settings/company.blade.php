<div class="card"><div class="card-body p-4">
    <h4>Nama Perusahaan</h4>
    <p class="text-secondary">Identitas pihak perusahaan pada kontrak, dokumen, dan syarat layanan. Alamat lengkap merupakan alamat perusahaan; alamat kontak person digunakan untuk korespondensi.</p>
    <form action="{{ route('admin.system-settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')<input type="hidden" name="section" value="company">
        <div class="row g-3">
            <div class="col-md-6"><label for="company_name" class="form-label">Nama Perusahaan</label><input id="company_name" name="company_name" class="form-control" value="{{ old('company_name', $settings['company_name']) }}" maxlength="200" required></div>
            <div class="col-md-6"><label for="company_director" class="form-label">Nama Pimpinan</label><input id="company_director" name="company_director" class="form-control" value="{{ old('company_director', $settings['company_director']) }}" maxlength="200" required></div>
            <div class="col-md-6"><label for="company_position" class="form-label">Jabatan</label><input id="company_position" name="company_position" class="form-control" value="{{ old('company_position', $settings['company_position']) }}" maxlength="200" required><div class="form-text">Digunakan sebagai jabatan pimpinan perusahaan di dalam kontrak.</div></div>
            <div class="col-12"><label for="company_address" class="form-label">Alamat Lengkap Perusahaan</label><textarea id="company_address" name="company_address" class="form-control" rows="3" maxlength="2000" required>{{ old('company_address', $settings['company_address']) }}</textarea></div>
            <div class="col-12">
                <label for="company_signature" class="form-label">Tanda Tangan</label>
                <div class="border rounded p-3 mb-2 d-flex align-items-center justify-content-center" style="min-height:110px;background:#fff;color:#475569">
                    <img id="company-signature-preview" @if($hasCompanySignature) src="{{ route('admin.system-settings.company-signature') }}" @else hidden @endif alt="Pratinjau tanda tangan" style="max-height:100px;max-width:100%;object-fit:contain">
                    <span data-empty-preview @if($hasCompanySignature) hidden @endif>Belum ada tanda tangan tersimpan.</span>
                </div>
                <input id="company_signature" name="company_signature" type="file" class="form-control" accept="image/png,image/jpeg,image/webp" data-preview="company-signature-preview">
                <div class="form-text">PNG, JPG, atau WebP maksimal 2 MB. Kosongkan untuk mempertahankan tanda tangan. Gambar tersimpan secara privat dan digunakan pada dokumen yang dibuat berikutnya.</div>
                @if($hasCompanySignature)<div class="form-check mt-2"><input id="remove_company_signature" name="remove_company_signature" type="checkbox" class="form-check-input" value="1"><label for="remove_company_signature" class="form-check-label">Hapus tanda tangan tersimpan</label></div>@endif
            </div>
            <div class="col-md-6"><label for="company_contact_name" class="form-label">Nama Kontak Person</label><input id="company_contact_name" name="company_contact_name" class="form-control" value="{{ old('company_contact_name', $settings['company_contact_name']) }}" maxlength="200" required></div>
            <div class="col-md-6"><label for="company_phone" class="form-label">No Tlp</label><input id="company_phone" name="company_phone" type="tel" class="form-control" value="{{ old('company_phone', $settings['company_phone']) }}" maxlength="30" required></div>
            <div class="col-md-6"><label for="company_contact_address" class="form-label">Alamat Kontak Person</label><textarea id="company_contact_address" name="company_contact_address" class="form-control" rows="3" maxlength="2000" required>{{ old('company_contact_address', $settings['company_contact_address']) }}</textarea></div>
            <div class="col-md-6"><label for="company_email" class="form-label">Email</label><input id="company_email" name="company_email" type="email" class="form-control" value="{{ old('company_email', $settings['company_email']) }}" maxlength="255" required></div>
        </div>
        <p class="form-text mt-3">Kontrak Aggregator yang sudah digenerate tetap memakai salinan identitas dan tanda tangan saat dibuat. Gunakan penanda [PROVIDER_…] pada draft agar identitas perusahaan terisi otomatis.</p>
        <button class="btn btn-primary mt-2" type="submit">Simpan Nama Perusahaan</button>
    </form>
</div></div>
