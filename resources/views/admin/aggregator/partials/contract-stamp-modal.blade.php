@push('modals')
<div class="modal fade" id="stamp-upload-{{ $contract->id }}" tabindex="-1" aria-labelledby="stamp-title-{{ $contract->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" action="{{ route('admin.releases.contract.stamp_upload', $contract->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="stamp_contract_id" value="{{ $contract->id }}">
            <div class="modal-header">
                <h5 class="modal-title" id="stamp-title-{{ $contract->id }}">Upload Kontrak Bermaterai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold">{{ $contract->docnum }}</p>
                <label for="stamped-pdf-{{ $contract->id }}" class="form-label">PDF kontrak bermaterai digital</label>
                <input id="stamped-pdf-{{ $contract->id }}" type="file" name="stamped_pdf" class="form-control" accept=".pdf,application/pdf" required>
                <p class="form-text">Upload PDF kontrak lengkap yang sudah diberi materai digital. Maksimal 10 MB.</p>
                @if((int) old('stamp_contract_id', session('contract_stamp_id')) === $contract->id)
                    @error('stamped_pdf')<p class="text-danger">{{ $message }}</p>@enderror
                @endif
                @if($contract->digitalStamp)
                    <p class="form-text">File saat ini: {{ $contract->digitalStamp->original_name }}. Upload baru akan mengganti file bermaterai sebelumnya.</p>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Upload Kontrak Bermaterai</button>
            </div>
        </form>
    </div>
</div>
@endpush
