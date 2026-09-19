@extends('layouts.app')
@section('title')
    Account Pending
@endsection
@section('content')
    <div class="mx-3 mx-lg-0">
        <div class="card my-5 col-xl-9 col-xxl-8 mx-auto rounded-4 overflow-hidden border-3 p-4">
            <div class="row g-4">
                <div class="col-12">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <h4 class="fw-bold">Account Pending</h4>
                            <p class="mb-0">Your data will be verified by our team as soon as possible.</p>
                            <p>You will be notified via email and WhatsApp if there is any updates.</p>
                            <p class="mb-2">Your account status is now:</p>

                            @if(current_user()->profile)
                                @if (current_user()->profile->status == 'pending')
                                    <span class="badge bg-grd-warning text-dark mb-4">Pending</span>
                                @elseif (current_user()->profile->status == 'blocked')
                                    <span class="badge bg-grd-danger mb-4">Blocked</span>
                                @endif

                                @if (current_user()->profile->reason)
                                    <p class="fw-bold mb-1">Reason:</p>
                                    <p class="text-danger">{{ current_user()->profile->reason }}</p>
                                @endif
                            @else
                                <span class="badge bg-grd-royal mb-4">Unknown</span>
                            @endif

                            <p class="mt-3">Thank you for your trust in our service!</p>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary mt-4">Back to Login</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection