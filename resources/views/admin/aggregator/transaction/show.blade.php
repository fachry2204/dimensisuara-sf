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
            @if ($transaction->status == 'pending')
                <div class="d-flex justify-content-center mt-3">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#complete-modal">Complete Transaction</button>
                </div>
            @endif
        @endcan
    </div>
@endsection
@section('content')
    @include('components.transaction', ['transaction' => $transaction])
@endsection
@push('modals')
    @can('manage-generated-transactions')
        <div class="modal fade" id="complete-modal" tabindex="-1" aria-labelledby="complete-modal-label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="complete-modal-label">Complete Transaction</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Please upload required documents to complete the transaction</p>
                        <form action="{{ route('admin.releases.settlement.update', $transaction->id) }}" method="post" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label for="slip" class="form-label">Transfer Slip (Bukti Transfer)</label>
                                <input type="file" class="form-control" name="slip" id="slip" required>
                            </div>
                            <div class="mb-3">
                                <label for="tax" class="form-label">Tax Invoice (Faktur Pajak)</label>
                                <input type="file" class="form-control" name="tax" id="tax" required>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Proceed Completion</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endpush
