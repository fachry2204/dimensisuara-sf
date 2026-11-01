@extends('layouts.app')
@section('title')
Aggregator Contracts
@endsection
@section('header')
    <div class="contract-page-header d-flex flex-row align-items-center justify-content-between mb-4">
        <div><h4 class="mb-0 fs-2">Aggregator Contracts</h4><p class="mb-0">Manage user contracts for your aggregator partnership.</p></div>
        @can('generate-contracts')
            <button type="button" class="btn contract-create-button" data-bs-toggle="modal" data-bs-target="#create-contract-modal"><i class="material-icons-outlined">add</i> Create Contract</button>
        @endcan
    </div>
@endsection
@section('content')
    <div class="contract-summary-grid">
        <div class="contract-summary-card contract-summary-card--generated"><span class="material-icons-outlined">description</span><div><small>Contracts Generated</small><strong>{{ number_format($generatedContractUsers) }}</strong><p>users with an active contract</p></div></div>
        <div class="contract-summary-card contract-summary-card--missing"><span class="material-icons-outlined">group</span><div><small>Users Without Contract</small><strong>{{ number_format($usersWithoutContract) }}</strong><p>users still need a contract</p></div><button type="button" class="contract-review-users" id="review-users">Review Users <i class="material-icons-outlined">arrow_forward</i></button></div>
    </div>

    <ul class="nav nav-pills contract-tabs mb-3 fw-bold">
        <li class="nav-item">
            <button class="nav-link active" id="contracts-tab" data-bs-toggle="tab" data-bs-target="#contracts" type="button" role="tab" aria-controls="contracts" aria-selected="true">All Contracts</button>
        </li>
        <li class="nav-item" id="per-user">
            <button class="nav-link" id="per-users-tab" data-bs-toggle="tab" data-bs-target="#per-users" type="button" role="tab" aria-controls="per-users" aria-selected="false">User Not Contract</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="unstamped-tab" data-bs-toggle="tab" data-bs-target="#unstamped-contracts" type="button" role="tab" aria-controls="unstamped-contracts" aria-selected="false">Contract Not Materai <span class="badge bg-warning text-dark ms-1">{{ number_format($contractsWithoutStamp) }}</span></button>
        </li>
    </ul>

    <div class="tab-content contract-table-panel pb-5">
        <div class="tab-pane fade show active" id="contracts" role="tabpanel" aria-labelledby="contracts-tab">
            <table class="table table-striped" id="all-table">
                <thead>
                    <th>User</th>
                    <th>Document Number</th>
                    <th>Date Created</th>
                    <th>Actions</th>
                </thead>
            </table>
        </div>
        <div class="tab-pane fade" id="per-users" role="tabpanel" aria-labelledby="per-users-tab">
            <p>User dengan fitur Aggregator aktif yang belum memiliki kontrak Aggregator.</p>
            <table class="table table-striped table-sm" style="width:100%" id="user-table">
                <thead><tr><th>User</th><th>Status Profil</th><th>Tanggal Daftar</th><th>Actions</th></tr></thead>
            </table>
        </div>
        <div class="tab-pane fade" id="unstamped-contracts" role="tabpanel" aria-labelledby="unstamped-tab">
            <p>Kontrak Aggregator yang belum memiliki PDF bermaterai digital. PDF manual biasa tidak dihitung sebagai dokumen bermaterai.</p>
            <table class="table table-striped" id="unstamped-table" style="width:100%">
                <thead><tr><th>User</th><th>Document Number</th><th>Date Created</th><th>Actions</th></tr></thead>
            </table>
        </div>
    </div>

@endsection
@push('modals')
    @can('generate-contracts')
        <div class="modal fade" id="create-contract-modal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-bottom-0 py-2">
                        <h5 class="modal-title">Create Aggregator Contract</h5>
                        <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                            <i class="material-icons-outlined">close</i>
                        </a>
                    </div>
                    <div class="modal-body">
                        <p>Please input the contract details</p>
                        <form id="create-contract-form" action="{{ route('admin.releases.contract.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @include('admin.aggregator.partials.contract-date', ['datePrefix' => 'list'])
                            <input type="hidden" name="type" value="aggregator">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="profileid">User <span class="text-danger">*</span></label>
                                    <select name="profileid" id="profileid" class="form-select form-select-sm" required>
                                        @foreach ($profiles as $profile)
                                            <option value="{{ $profile->id }}" data-rate="{{ $profile->aggregator_rate ?? 70 }}" @selected((string) old('profileid') === (string) $profile->id) @disabled(!$profile->feature_aggregator || $profile->status !== 'verified')>{{ $profile->full_name }}{{ $profile->status !== 'verified' ? ' — belum terverifikasi' : '' }}</option>
                                        @endforeach
                                    </select>

                                    <input type="hidden" name="type" value="aggregator">
                                </div>
                                <div class="col-12">
                                    @include('admin.aggregator.partials.contract-rates', ['initialUserRate' => ($profiles->firstWhere('id', old('profileid')) ?? $profiles->first(fn ($profile) => $profile->feature_aggregator && $profile->status === 'verified'))?->aggregator_rate ?? 70, 'ratePrefix' => 'list-contract'])
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label for="number">Document Number <span class="text-danger">*</span></label>
                                    <input type="number" name="number" class="form-control" min="1" value="{{ old('number') }}" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="month">Month <span class="text-danger">*</span></label>
                                    <select type="text" name="month" class="form-control" required>
                                        <option value="1" @selected((int) old('month', now()->month) === 1)>January (I)</option>
                                        <option value="2" @selected((int) old('month', now()->month) === 2)>February (II)</option>
                                        <option value="3" @selected((int) old('month', now()->month) === 3)>March (III)</option>
                                        <option value="4" @selected((int) old('month', now()->month) === 4)>April (IV)</option>
                                        <option value="5" @selected((int) old('month', now()->month) === 5)>May (V)</option>
                                        <option value="6" @selected((int) old('month', now()->month) === 6)>June (VI)</option>
                                        <option value="7" @selected((int) old('month', now()->month) === 7)>July (VII)</option>
                                        <option value="8" @selected((int) old('month', now()->month) === 8)>August (VIII)</option>
                                        <option value="9" @selected((int) old('month', now()->month) === 9)>September (IX)</option>
                                        <option value="10" @selected((int) old('month', now()->month) === 10)>October (X)</option>
                                        <option value="11" @selected((int) old('month', now()->month) === 11)>November (XI)</option>
                                        <option value="12" @selected((int) old('month', now()->month) === 12)>December (XII)</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="year">Year <span class="text-danger">*</span></label>
                                    <input type="number" name="year" class="form-control" min="2000" max="2100" value="{{ old('year', now()->year) }}" required>
                                </div>
                                @can('upload-contracts')
                                    <div class="col-12 mb-3">
                                        <label for="pdf">PDF file (optional)</label>
                                        <input type="file" name="pdf" class="form-control" accept="application/pdf">
                                    </div>
                                @endcan
                            </div>

                        </form>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button class="btn btn-primary" type="submit" form="create-contract-form">Create Contract</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endpush
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .contract-page-header h4{color:#f7fbff;letter-spacing:-.035em}.contract-page-header p{margin-top:4px;color:#8eafd0;font-size:.82rem}.contract-create-button{display:inline-flex;align-items:center;gap:7px;min-height:42px;border:0;background:#ff7043;color:#fff;font-size:.8rem;font-weight:800;box-shadow:0 8px 18px rgba(255,112,67,.2)}.contract-summary-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-bottom:20px}.contract-summary-card{position:relative;display:flex;align-items:center;gap:16px;min-height:125px;padding:22px;border:1px solid #2f5d86;border-radius:14px;background:linear-gradient(120deg,#102943,#0c2036);overflow:hidden}.contract-summary-card>span{display:grid;place-items:center;flex:none;width:52px;height:52px;border-radius:50%;font-size:27px}.contract-summary-card small{display:block;color:#c5dcf2;font-size:.8rem;font-weight:800}.contract-summary-card strong{display:block;margin:2px 0;color:#fff;font-size:2rem;line-height:1;font-weight:800}.contract-summary-card p{margin:5px 0 0;color:#8facca;font-size:.75rem}.contract-summary-card--generated{border-color:#2c9b78}.contract-summary-card--generated>span{background:rgba(60,219,159,.15);color:#46dfaa}.contract-summary-card--missing{border-color:#cf7446}.contract-summary-card--missing>span{background:rgba(255,145,87,.16);color:#ff9b65}.contract-review-users{margin-left:auto;display:inline-flex;align-items:center;gap:5px;padding:9px 11px;border:1px solid #4e91c7;border-radius:8px;background:transparent;color:#a9d4fa;font-size:.75rem;font-weight:800}.contract-review-users i{font-size:17px}.contract-tabs{display:flex;gap:5px;padding:7px;border:1px solid #294d74;border-radius:11px;background:#0d1d31;width:max-content}.contract-tabs .nav-link{padding:9px 16px;border-radius:7px;color:#a8c3e0;font-size:.8rem}.contract-tabs .nav-link.active{background:#1d69bc;color:#fff}.contract-table-panel{padding:18px;border:1px solid #294d74;border-radius:13px;background:#0d1d31}.contract-table-panel .dataTables_filter label,.contract-table-panel .dataTables_length label{color:#c9dbed}.contract-table-panel .dataTables_filter input,.contract-table-panel .dataTables_length select{border-color:#45688d;background:#08182a;color:#eef6ff;border-radius:7px}.contract-table-panel table{margin-top:16px!important}.contract-table-panel table thead th{border-bottom:0;background:#172f4c;color:#b5d0ec;font-size:.7rem;text-transform:uppercase}.contract-table-panel table tbody td{border-color:rgba(59,94,129,.48);color:#d8e6f5}.contract-table-panel .btn-outline-primary{border-color:#5b88b5;color:#a9cdf2}.contract-table-panel .btn-outline-primary:hover{border-color:#ff7043;background:#ff7043;color:#fff}
        #user-table td{
            height: 40px;
        }
        #user-table td:first-child {
            /* white-space: nowrap; */
            width: 30%;
        }
        #user-table td:last-child{
            width: 15%;
        }
        @media(max-width:767px){.contract-page-header{align-items:flex-start!important;flex-direction:column;gap:12px}.contract-summary-grid{grid-template-columns:1fr}.contract-summary-card{min-height:112px}.contract-review-users{position:absolute;right:16px;bottom:15px}.contract-table-panel{padding:12px;overflow-x:auto}}
    </style>
@endpush
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            const canViewUsers = @json(auth()->user()->can('view-users'));
            const canGenerate = @json(auth()->user()->can('generate-contracts'));
            const safeText = $.fn.dataTable.render.text();
            const dateColumn = { data:'created_at', render: function(value,type) {
                return type === 'display' && value ? new Date(value).toLocaleDateString('id-ID',{day:'numeric',month:'long',year:'numeric'}) : (value || '—');
            }};
            function contractTable(selector, filter) {
                return $(selector).DataTable({
                    processing:true, serverSide:true, pageLength:10, order:[[2,'desc']],
                    ajax:{url:@json(route('admin.releases.contract.get_data')),data:{filter}},
                    columns:[
                        {data:'fullname',defaultContent:'—',render:safeText},
                        {data:'docnum',defaultContent:'—',render:safeText},
                        dateColumn,
                        {data:'id',orderable:false,searchable:false,render:function(id,type,row) {
                            if (type !== 'display') return id;
                            const view = @json(route('admin.releases.contract.view_raw', ':id')).replace(':id',Number(id));
                            const pdf = @json(route('admin.releases.contract.view_uploaded', ':id')).replace(':id',Number(id));
                            let buttons = `<a href="${view}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">View Contract</a>`;
                            if (row.file) buttons += `<a href="${pdf}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">PDF</a>`;
                            if (canViewUsers && row.user_id) {
                                const user = @json(route('admin.users.show', ':id')).replace(':id',Number(row.user_id));
                                buttons += `<a href="${user}" class="btn btn-outline-primary btn-sm">${filter === 'unstamped' ? 'Buka User / Materai' : 'Buka User'}</a>`;
                            }
                            return `<div class="d-flex flex-wrap gap-2">${buttons}</div>`;
                        }}
                    ]
                });
            }
            contractTable('#all-table','all');
            contractTable('#unstamped-table','unstamped');
            $('#user-table').DataTable({
                processing:true,serverSide:true,pageLength:10,order:[[2,'desc']],
                ajax:@json(route('admin.releases.contract.get_data_per_user')),
                columns:[
                    {data:'fullname',render:safeText},
                    {data:'status',render:safeText},
                    dateColumn,
                    {data:'id',orderable:false,searchable:false,render:function(id,type,row) {
                        if (type !== 'display') return id;
                        let buttons = '';
                        if (canViewUsers && row.userid) {
                            const user = @json(route('admin.users.show', ':id')).replace(':id',Number(row.userid));
                            buttons += `<a href="${user}" class="btn btn-outline-primary btn-sm">Buka User</a>`;
                        }
                        if (canGenerate && row.status === 'verified') buttons += `<button type="button" class="btn btn-primary btn-sm create-user-contract" data-profile="${Number(id)}">Generate Kontrak</button>`;
                        return `<div class="d-flex flex-wrap gap-2">${buttons || '—'}</div>`;
                    }}
                ]
            });
            $('#review-users').on('click',function() { bootstrap.Tab.getOrCreateInstance(document.getElementById('per-users-tab')).show(); });
            $('#user-table').on('click','.create-user-contract',function() {
                const profile = document.getElementById('profileid');
                profile.value = this.dataset.profile;
                profile.dispatchEvent(new Event('change',{bubbles:true}));
                bootstrap.Modal.getOrCreateInstance(document.getElementById('create-contract-modal')).show();
            });
            $('[data-bs-toggle="tab"]').on('shown.bs.tab',function() { $.fn.dataTable.tables({visible:true,api:true}).columns.adjust(); });
        });
    </script>
@endpush
