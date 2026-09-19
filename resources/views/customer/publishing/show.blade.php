@extends('layouts.app')
@section('title')
View Publishing Request
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('publishing.index') }}">Publishing</a></li>
                    <li class="breadcrumb-item active" aria-current="page"></li>
                </ol>
            </nav>
            <h4 class="mb-0 font-weight-bold fs-2">View Publishing Request</h4>
        </div>

        <div class="d-flex flex-row gap-2">
            @if ($publishing->status == 'pending')
                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancel-modal">Cancel Publishing</button>
            @endif
        </div>
    </div>
    <div class="mb-5">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <span class="text-muted">by {{ $publishing->requestor->full_name }}</span>

            @include('components.badge-publishing', ['status' => $publishing->status])

            @if($publishing->status == 'pending')
                <span class="badge bg-grd-royal">Due by {{ \Carbon\Carbon::parse($publishing->due_date)->format('j F Y') }}</span>
            @elseif($publishing->finished_at)
                <span class="badge bg-grd-royal">Finished on {{ \Carbon\Carbon::parse($publishing->finished_at)->format('j F Y') }}</span>
            @endif
        </div>
        <div class="text-muted small mt-2">
            <span class="material-icons-outlined align-middle" style="font-size: 14px;">schedule</span>
            Created {{ \Carbon\Carbon::parse($publishing->created_at)->diffForHumans() }} ({{ \Carbon\Carbon::parse($publishing->created_at)->format('j F Y, H:i') }})
        </div>
    </div>
@endsection
@section('content')
    @if($publishing->status == 'rejected')
        <div class="alert alert-danger" role="alert">
            @if($publishing->rejectorid)
                <div>This publishing contract has been rejected by {{ $publishing->rejector->songwriter->full_name }} ({{ $publishing->rejector->songwriter->email }}).</div>
            @else
                <div>This publishing contract has been rejected.</div>
            @endif
            <div>Reason:</div>
            <div class="mx-2"><i>{{ $publishing->reason }}</i></div>
        </div>
    @endif

    <div class="card rounded-4">
        <div class="card-body p-4">
            @include('components.publishing', [
                    'publishing' => $publishing,
                    'allowEdit' => true,
                    'allowGenerateContract' => false,
                    'resendRoute' => 'publishing.signee.resend',
                    'contractRoute' => 'publishing.contract.retrieve',
                    'isDetailed' => true
                ])

            <div class="mt-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="accept_terms" id="accept_terms" @if($publishing->accept_terms) checked @endif disabled>
                    <label class="form-check-label" for="accept_terms">
                        <i>In the event the Author/Composer terminates the collaboration agreement prior to the expiration of the agreement term, such termination shall become effective <strong>one (1) year</strong> after the submission of the termination request by the Author/Composer, provided that it has been approved by all parties (<strong>Post Term Period</strong> applies to this agreement)</i>.
                    </label>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('modals')
    <!-- Modal -->
    <div class="modal fade" id="cancel-modal" tabindex="-1" aria-labelledby="cancel-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancel-modal-label">Cancel Publishing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to cancel this publishing request? All the signee will be notified upon cancellation.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="{{ route('publishing.cancel', ['id' => $publishing->id]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="btn btn-danger">Cancel Publishing</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endpush
@push('css')
@endpush
@push('script')
@endpush
