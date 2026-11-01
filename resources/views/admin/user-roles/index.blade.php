@extends('layouts.app')
@section('title')
Admin Roles and Permissions
@endsection
@section('header')
    <div class="roles-page-header"><div><h1>User Roles</h1><p>Kelola peran, akses, dan administrator pada sistem.</p></div>@can('manage-roles')<a href="{{ route('admin.user-roles.create') }}"><i class="material-icons-outlined">add</i> Create role</a>@endcan</div>
@endsection
@section('content')
    {{-- <ul class="nav nav-pills mb-3" id="roles-permission-tab">
        <li class="nav-item">
            <button class="nav-link active" id="assignment-tab" data-bs-toggle="tab" data-bs-target="#assignment" type="button" role="tab" aria-controls="assignment" aria-selected="true">User Assignment</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles" type="button" role="tab" aria-controls="roles" aria-selected="false">Role list</button>
        </li>
    </ul>
    <div class="tab-content" id="roles-permission">
        <div class="tab-pane fade show active" id="assignment" role="tabpanel" aria-labelledby="assignment-tab">
            <div class="table-responsive">
                <table class="table" id="assignment-table">
                    <thead>
                        <tr>
                            <th style="width: 30%">Name</th>
                            <th>Roles</th>
                            <th style="width: 10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="roles" role="tabpanel" aria-labelledby="roles-tab">
            <div class="table-responsive">
                <table class="table" id="roles-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Display Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div> --}}

    <div class="roles-summary"><article><i class="material-icons-outlined blue">admin_panel_settings</i><div><small>Total role</small><strong>{{ $roles->count() }}</strong><span>Role tersedia</span></div></article><article><i class="material-icons-outlined purple">verified_user</i><div><small>Admin</small><strong>{{ count($admins) }}</strong><span>Akses administratif</span></div></article><article><i class="material-icons-outlined green">group</i><div><small>Admin Nonaktif</small><strong>{{ count($users) }}</strong><span>Admin tanpa peran</span></div></article></div>
    <div class="mb-3 roles-panel-wrap">
        <div class="card roles-panel">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0 fw-bold">
                        User Roles
                    </h6>
                    @can('manage-roles')
                        <a href="{{ route('admin.user-roles.create') }}" class="btn btn-primary btn-sm">Create New Role</a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="roles-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th style="width: 45%">Members</th>
                                @can('manage-roles')
                                    <th style="width: 15%">Actions</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr>
                                    <td>{{ $role->name }}</td>
                                    <td>{{ $role->description }}</td>
                                    <td>{{ implode(', ', $role->users()->pluck('name')->toArray()) }}</td>
                                    @can('manage-roles')
                                        <td>
                                            <a href="{{ route('admin.user-roles.edit', $role->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                            @if( $role->users()->count() == 0 )
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteRole({{ $role->id }})">Delete</button>
                                            @endif
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="mb-3 roles-panel-wrap">
        <div class="card roles-panel">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0 fw-bold">
                        List Admins
                    </h6>
                    @can('manage-admins')
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#create-admin-modal">Create New Admin</button>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="admins-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status Email</th>
                                @can('manage-admins')
                                    <th style="width: 15%">Actions</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($admins as $admin)
                                <tr>
                                    <td>{{ $admin->full_name }}</td>
                                    <td>{{ $admin->email }}</td>
                                    <td>@if ($admin->roles->first()) {{ $admin->roles->first()->name }} @endif</td>
                                    <td><x-account-email-status :account="$admin" :action="route('admin.user-admin.verify-email', $admin->id)" :can-manage="auth()->user()->can('manage-admins')" /></td>
                                    @can('manage-admins')
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger" onclick="revokeAdmin({{ $admin->id }})">Revoke</button>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('modals')
    @can('manage-roles')
        <div class="modal fade" id="confirm-delete-role-modal" tabindex="-1" aria-labelledby="confirm-delete-role-modal-title" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="delete-role-form" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirm-delete-role-modal-title">Confirm Delete Role</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this role?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete Role</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @can('manage-admins')
        <div class="modal fade" id="create-admin-modal" tabindex="-1" aria-labelledby="create-admin-modal-title" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="create-admin-modal-title">Create New Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        
                        <ul class="nav nav-pills mb-3" id="createAdmin">
                            <li class="nav-item">
                                <button class="nav-link active" id="existing-tab" data-bs-toggle="tab" data-bs-target="#existing" type="button" role="tab" aria-controls="existing" aria-selected="true">Admin Nonaktif</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="new-tab" data-bs-toggle="tab" data-bs-target="#new" type="button" role="tab" aria-controls="new" aria-selected="false">Admin Baru</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="createAdminContent">
                            <div class="tab-pane fade show active" id="existing" role="tabpanel" aria-labelledby="existing-tab">
                                <form action="{{ route('admin.user-admin.store') }}" method="POST" id="delegate-admin-form">
                                    @csrf
                                    <input type="hidden" name="type" value="existing">
                                    <div class="mb-3">
                                        <label for="user" class="form-label">Select Admin Nonaktif</label>
                                        <select class="form-control" name="userid" required>
                                            <option value="" selected disabled>Pilih Admin</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->full_name }} ({{ $user->email }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Admin Role</label>
                                        <select class="form-control" name="role" required>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Create Admin</button>
                                    </div>
                                </form>
                            </div>
                            <div class="tab-pane fade" id="new" role="tabpanel" aria-labelledby="new-tab">
                                <form action="{{ route('admin.user-admin.store') }}" method="POST" id="create-admin-form">
                                    @csrf
                                    <input type="hidden" name="type" value="new">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Admin Role</label>
                                        <select class="form-control" name="role" required>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control mb-3" id="password" name="password" required>
                                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm Password" required>
                                    </div>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Create Admin</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="revoke-admin-modal" tabindex="-1" aria-labelledby="revoke-admin-modal-title" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="" method="POST" id="revoke-admin-form">
                        @csrf
                        @method('DELETE')
                        <div class="modal-header">
                            <h5 class="modal-title" id="revoke-admin-modal-title">Revoke Admin</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to revoke this admin?</p>
                            <p>Peran admin akan dicabut. Akun tetap tersimpan di tabel admin dan tidak menjadi akun pelanggan.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger" form="revoke-admin-form">Revoke Admin</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endpush
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
    .sf-app .main-wrapper .main-content{max-width:1440px!important;padding:28px 32px 46px!important;background:#081225!important}.roles-page-header{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:20px}.roles-page-header h1{margin:0;color:#f5f9ff!important;font-size:1.9rem!important;font-weight:800!important}.roles-page-header p{margin:6px 0 0;color:#91afd0;font-size:.78rem}.roles-page-header>a{display:inline-flex;align-items:center;gap:5px;padding:10px 13px;border-radius:8px;background:#ff7146;color:#fff!important;text-decoration:none;font-size:.72rem;font-weight:800}.roles-page-header>a i{font-size:17px}.roles-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:17px}.roles-summary article{display:flex;align-items:center;gap:13px;min-height:105px;padding:16px;border:1px solid #294c71;border-radius:13px;background:linear-gradient(140deg,#0f223a,#0a192c)}.roles-summary article>i{display:grid;place-items:center;width:44px;height:44px;border-radius:11px;font-size:23px}.roles-summary .blue{background:rgba(88,158,255,.16);color:#79b5ff}.roles-summary .purple{background:rgba(166,123,255,.16);color:#be9cff}.roles-summary .green{background:rgba(73,219,155,.15);color:#65e2aa}.roles-summary small,.roles-summary span{display:block;color:#93b3d4;font-size:.68rem}.roles-summary strong{display:block;margin:2px 0;color:#f5f9ff;font-size:1.43rem;line-height:1.05}.roles-summary span{color:#789aba;font-size:.64rem}.roles-panel{overflow:hidden;border:1px solid #294c71!important;border-radius:14px!important;background:linear-gradient(145deg,#0e2036,#0a192b)!important}.roles-panel .card-header{padding:17px 19px;border-color:rgba(75,116,157,.35)!important;background:transparent!important;color:#f3f8ff}.roles-panel .card-body{padding:6px 14px 14px}.roles-panel .table{color:#c4d6e8}.roles-panel .table th{border-color:#3a5b7d;color:#8eb3d7;font-size:.63rem;text-transform:uppercase}.roles-panel .table td{border-color:rgba(57,90,124,.48);font-size:.75rem;vertical-align:middle}@media(max-width:700px){.sf-app .main-wrapper .main-content{padding:18px!important}.roles-page-header{align-items:flex-start;flex-direction:column}.roles-summary{grid-template-columns:1fr}}
    </style>
@endpush
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        function deleteRole(id) {
            $('#delete-role-form').attr('action', "{{ route('admin.user-roles.destroy', ':id') }}".replace(':id', id));
            $('#confirm-delete-role-modal').modal('show');
        }

        function revokeAdmin(id) {
            $('#revoke-admin-form').attr('action', "{{ route('admin.user-admin.revoke', ':id') }}".replace(':id', id));
            $('#revoke-admin-modal').modal('show');
        }
    </script>
@endpush
