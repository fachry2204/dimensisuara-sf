@extends('layouts.app')
@section('title')
View Transaction
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.releases.settlement.index') }}">Aggregator Transactions</a></li>
                    <li class="breadcrumb-item active" aria-current="page"></li>
                </ol>
            </nav>
            <h4 class="mb-0 font-weight-bold fs-2">View Transaction</h4>
        </div>
        @can('manage-generated-transactions')
            @if ($settlement->status == 'pending')
                <div class="d-flex justify-content-center mt-3">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#complete-modal">Mark as Transferred</button>
                </div>
            @endif
        @endcan
    </div>
@endsection
@section('content')
    <div class="row g-3 justify-content-center">
        <div class="col-md-9">
            @include('components.settlement', ['transaction' => $settlement])
        </div>
    
        @can('manage-generated-transactions')
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0 fw-bold">Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <button type="button" id="upload-slip" class="btn btn-outline-primary settlement-upload-button"><span class="material-icons-outlined" aria-hidden="true">account_balance</span><span>Upload Bukti Transfer Bank</span></button>
                            <button type="button" id="upload-tax" class="btn btn-outline-primary settlement-upload-button"><span class="material-icons-outlined" aria-hidden="true">receipt_long</span><span>Upload Bukti Pajak</span></button>
                        </div>
                        <p class="form-text mt-3 mb-0">PDF, PNG, JPG, atau WebP. Maksimal 10 MB per dokumen.</p>

                        <form action="{{ route('admin.releases.settlement.upload', $settlement->id) }}" method="POST" class="d-none" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="type" id="type" required>
                            <input type="file" name="file" id="file" accept=".pdf,.png,.jpg,.jpeg,.webp" required>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    </div>
@endsection
@push('modals')
    @can('manage-generated-transactions')
        <div class="modal fade" id="complete-modal" tabindex="-1" aria-labelledby="complete-modal-label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="complete-modal-label">Mark as Transferred</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to mark this transaction as transferred?</p>
                        <form action="{{ route('admin.releases.settlement.complete', $settlement->id) }}" method="post" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary btn-sm">Mark as Transferred</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endpush

@push('script')
    <script>
        $(document).ready(function() {
            $('#upload-slip').on('click', function(e) {
                e.preventDefault();
                $('#type').val('slip');
                $('#file').click();
            });
            $('#upload-tax').on('click', function(e) {
                e.preventDefault();
                $('#type').val('tax');
                $('#file').click();
            });

            $('#file').on('change', function(e) {
                e.preventDefault();
                if (!this.files.length) return;
                var form = $(this).closest('form');
                form.submit();
            });
        });
    </script>
@endpush
@push('css')
<style>
    .settlement-upload-button { display:flex;align-items:center;gap:.6rem;text-align:left;white-space:normal;min-height:48px; }
    .settlement-upload-button .material-icons-outlined { flex-shrink:0;font-size:24px; }
    .settlement-documents { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;margin-top:1.5rem; }
    .settlement-document { border:1px solid var(--sf-border);border-radius:.75rem;padding:1rem;background:var(--sf-surface-soft);min-width:0; }
    .settlement-document-heading { display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem; }
    .settlement-document-heading .material-icons-outlined { color:var(--sf-accent);font-size:25px; }
    .settlement-document .btn { display:inline-flex;align-items:center;justify-content:center;gap:.4rem;white-space:normal;min-height:40px; }
    .settlement-document small { display:block;margin-top:.5rem;overflow-wrap:anywhere; }
    @media(max-width:767px) { .settlement-documents { grid-template-columns:1fr; } }
</style>
@endpush
