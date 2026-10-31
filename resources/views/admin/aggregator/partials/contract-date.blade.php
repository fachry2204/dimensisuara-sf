<div class="col-12 mb-3">
    <label for="{{ $datePrefix }}-contract-date" class="form-label">Tanggal Generate Kontrak <span class="text-danger">*</span></label>
    <input id="{{ $datePrefix }}-contract-date" type="date" name="contract_date" class="form-control" value="{{ old('contract_date', now()->toDateString()) }}" required>
    <p class="form-text">Tanggal ini digunakan di dalam kontrak. Bulan dan tahun nomor dokumen mengikuti pilihan Month dan Year.</p>
    @error('contract_date')<p class="text-danger">{{ $message }}</p>@enderror
</div>
