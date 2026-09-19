@extends('layouts.app')
@section('title')
Edit Role
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.user-roles.index') }}">Admin Roles and Permissions</a></li>
                    <li class="breadcrumb-item active" aria-current="page"></li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <h4 class="mb-0 fs-2">Edit Role</h4>
    </div>
@endsection
@section('content')
<form action="{{ route('admin.user-roles.update', $role->id)}}" method="POST">
    @csrf
    @method('PUT')
    <div class="row mb-3">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Details</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $role->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="description" name="description" value="{{ old('description', $role->description) }}" required>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Permissions</h6>
                </div>
                <div class="card-body">
                    @php
                        $sections = [['users' => 'User Management', 'aggregator' => 'Aggregator (Releases)', 'publishing' => 'Publishing', 'mcn' => 'MCN', 'reporting' => 'Reporting', 'transaction' => 'Transaction'], ['contract'  => 'Contracts', 'logging' => 'Logging', 'automation' => 'Automation', 'rolespermissions' => 'Admin Roles and Permissions']];
                    @endphp
                    <div class="row px-5">
                        @foreach ($sections as $section)
                            <div class="col-lg-6 mb-3">
                                @foreach ($section as $category => $name)
                                    <div class="mb-3">
                                        <h4>{{ $name }}</h4>
                                        @foreach ($permissions->where('category', $category) as $permission)
                                            <div class="form-check">
                                                <input type="checkbox" name="permissions[]" id="permission-{{ $permission->id }}" value="{{ $permission->id }}" class="form-check-input" {{ in_array($permission->id, old('permissions', $role->permissions->pluck('id')->toArray())) ? 'checked' : '' }}>
                                                <label for="permission-{{ $permission->id }}" class="form-check-label">{{ $permission->name }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">Update Role</button>
    </div>
</form>
@endsection
@push('css')
@endpush
@push('script')
@endpush
