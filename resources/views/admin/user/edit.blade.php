@extends('layouts.app')
@section('title')
Edit User
@endsection
@section('header')
    {{-- Breadcrumbs --}}
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">User Management</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.show', $user->id) }}">View User</a></li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <h4 class="mb-0 fs-2">Edit User</h4>
    </div>
@endsection
@section('content')

    @include('components.profile', [
        'user'                      => $user,
        'type'                      => 'customer',
        'profileupdate'             => 'admin.users.update',
        'profileidentityupdate'     => 'admin.users.identity.update',
        'profileidentitydelete'     => 'admin.users.identity.delete',
    ])

    @if($user->profile)
        <div class="card">
            <div class="card-header p-3">
                <h6 class="card-title mb-0 fw-bold">Revenue Share Rate</h6>
            </div>
            <div class="card-body">
                <p>Define this user's revenue share rate in percentage. This value will be used to calculate the revenue share on future reports.</p>
                <form action="{{ route('admin.users.update_rate', $user->id) }}" method="POST" id="rate-form">
                    @csrf
                    @method('put')
                    
                    <div class="row g-3 mb-3">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Aggregator Share Rate</label>
                                </div>
                                <div class="col-md-2">
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="aggregator_rate" id="aggregator_rate" value="{{  $user->profile->aggregator_rate ?? 0 }}" min="0" max="100">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Publishing Share Rate</label>
                                </div>
                                <div class="col-md-2">
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="publishing_rate" id="publishing_rate" value="{{  $user->profile->publishing_rate ?? 0 }}" min="0" max="100">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">MCN Share Rate</label>
                                </div>
                                <div class="col-md-2">
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="mcn_rate" id="mcn_rate" value="{{  $user->profile->mcn_rate ?? 0 }}" min="0" max="100">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

@endsection