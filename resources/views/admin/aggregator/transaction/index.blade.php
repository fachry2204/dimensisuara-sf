@extends('layouts.app')
@section('title')
Aggregator Transactions
@endsection
@section('header')
    <div class="transaction-page-heading">
        <div>
            <h4 class="mb-1 fs-2">Aggregator Transactions</h4>
            <p class="mb-0">View and manage revenue transactions from aggregator partners.</p>
        </div>
        @can('generate-transactions')
            <button type="button" class="btn btn-primary transaction-generate-btn" data-bs-toggle="modal" data-bs-target="#generate-transaction-modal"><i class="material-icons-outlined">add_circle_outline</i>Generate Transaction</button>
        @endcan
    </div>
@endsection
@section('content')
    <div class="row g-4 transaction-page">
        <div class="col-12 mb-3">
            <div class="card rounded-4 transaction-filter-card transaction-unified-frame">
                <div class="card-header p-3">
                    <h6 class="card-title mb-0 fw-bold"><i class="material-icons-outlined">filter_alt</i>Transaction Filter</h6>
                </div>
                <div class="card-body">
                    <p>Use the filter below to view transactions based on the selected criteria.</p>
                    <form id="filter-report">
                        <div class="row mb-3 justify-content-center align-items-end">
                            <div class="col-lg-3 col-sm-6 mb-3 mb-lg-0">
                                <label for="userid" class="form-label">User</label>
                                <select name="userid" id="userid" class="form-select form-select-sm">
                                    <option value="">Any</option>
                                    @foreach ($userprofiles as $userprofile)
                                        <option value="{{ $userprofile->id }}">{{ $userprofile->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-sm-6 mb-3 mb-lg-0">
                                <label for="year" class="form-label">Year</label>
                                <select name="year" id="year" class="form-select form-select-sm">
                                    @foreach ($years as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-sm-6 mb-3 mb-lg-0">
                                <label for="quarter" class="form-label">Quarter</label>
                                <select name="quarter" id="quarter" class="form-select form-select-sm">
                                    <option value="">Any</option>
                                    <option value="1">Q1</option>
                                    <option value="2">Q2</option>
                                    <option value="3">Q3</option>
                                    <option value="4">Q4</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-sm-6 mb-3 mb-lg-0">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select form-select-sm">
                                    <option value="">Any</option>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="carried_over">Carried Over</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-sm-6 mb-3 mb-lg-0 text-center text-lg-start">
                                <button type="submit" class="btn btn-primary btn-sm transaction-filter-submit"><i class="material-icons-outlined">search</i>Get Data</button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Summary cards inside same frame --}}
                <div class="transaction-frame-summary">
                    <div class="row justify-content-center g-3 transaction-summary-grid">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card rounded-4 transaction-summary-card transaction-summary-card--total">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <div class="wh-48 d-flex bg-info text-info bg-opacity-10 align-items-center justify-content-center rounded-circle">
                                            <span class="material-icons-outlined">payments</span>
                                        </div>
                                        <div class="">
                                            <h5 class="mb-0">Rp{{ number_format( round($rupiah_exact,0) , 0 , ',' , '.' ) }}</h5>
                                            <p class="mb-0">Total Transaction</p>
                                            <small>Pending and transferred value</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card rounded-4 transaction-summary-card transaction-summary-card--aggregator">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <div class="wh-48 d-flex bg-success text-success bg-opacity-10 align-items-center justify-content-center rounded-circle">
                                            <span class="material-icons-outlined">corporate_fare</span>
                                        </div>
                                        <div class="">
                                            <h5 class="mb-0">Rp{{ number_format( round($aggregator_rupiah_exact) , 0 , ',' , '.' ) }}</h5>
                                            <p class="mb-0">Aggregator Share</p>
                                            <small>Platform revenue allocation</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card rounded-4 transaction-summary-card transaction-summary-card--client">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <div class="wh-48 d-flex bg-warning text-warning bg-opacity-10 align-items-center justify-content-center rounded-circle">
                                            <span class="material-symbols-outlined">artist</span>
                                        </div>
                                        <div class="">
                                            <h5 class="mb-0">Rp{{ number_format( round($client_rupiah_exact) , 0 , ',' , '.' ) }}</h5>
                                            <p class="mb-0">Client Share</p>
                                            <small>Creator payout allocation</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Info note inside same frame --}}
                <div class="transaction-frame-note">
                    <div class="transaction-note"><i class="material-icons-outlined">info</i>The values above include transactions with status <strong>pending</strong> or <strong>transferred</strong>.</div>
                </div>

                {{-- Toolbar inside same frame --}}
                <div class="transaction-frame-toolbar">
                    <form id="export-report" action="{{ route('admin.releases.settlement.export') }}" method="POST" class="d-none">
                        @csrf
                        <input type="hidden" name="userid" id="export-userid">
                        <input type="hidden" name="year" id="export-year">
                        <input type="hidden" name="quarter" id="export-quarter">
                        <input type="hidden" name="status" id="export-status">
                    </form>
                    <button type="submit" form="export-report" class="btn btn-outline-primary btn-sm"><i class="material-icons-outlined">download</i>Export Report as CSV</button>
                </div>
            </div>
        </div>
        <div class="col-12 mb-3">
            <div class="transaction-table-shell"><div class="table-responsive"><table id="rows-table" class="table table-striped">
                <thead>
                    <th>User</th>
                    <th>Year</th>
                    <th>Quarter</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Generated At</th>
                    <th>Action</th>
                </thead>
            </table>
            </div></div>
        </div>
    </div>
@endsection
@push('modals')
    @can('generate-transactions')
        <div class="modal fade" id="generate-transaction-modal" tabindex="-1" aria-labelledby="generate-transaction-modal-label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="generate-transaction-modal-label">Generate Transaction</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <div class="d-flex align-items-center">
                                <div class="ms-3">
                                    <h6 class="mb-0 text-warning">Attention!</h6>
                                    <ul>
                                        <li>A list of transactions will be generated for your confirmation before being finalized.</li>
                                        <li>The transaction amount is automatically calculated from the submitted report corresponding to the chosen year and quarter.</li>
                                        <li>Revenue that has already been paid will not be included in the transaction amount.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <form id="generate-transaction-form" method="POST" action="{{ route('admin.releases.settlement.preview') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="year" class="form-label">Year</label>
                                <input type="text" class="form-control" id="year" name="year" value="{{ date('Y') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="quarter" class="form-label">Quarter</label>
                                <select class="form-select" id="quarter" name="quarter" required>
                                    <option value="1">Q1</option>
                                    <option value="2">Q2</option>
                                    <option value="3">Q3</option>
                                    <option value="4">Q4</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="minimum" class="form-label">Minimum Payable Amount</label>
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-text">Rp</div>
                                        <input type="text" class="form-control" id="minimum" name="minimum" value="5000" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input class="form-check-input" type="checkbox" id="allow_empty" name="allow_empty" value="1" checked>
                                <label class="form-check-label" for="allow_empty">Include transactions with zero amount</label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" form="generate-transaction-form">Get Data</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endpush
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .transaction-page-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1.5rem; margin-bottom:2rem; }
        .transaction-page-heading h4 { color:#f4f8ff; font-weight:700; letter-spacing:-.025em; }
        .transaction-page-heading p { color:#8fb9e8; font-size:.94rem; }
        .transaction-generate-btn { display:inline-flex; align-items:center; gap:.42rem; padding:.5rem .78rem; border-radius:.5rem; font-size:.82rem; font-weight:700; }
        .transaction-generate-btn .material-icons-outlined { font-size:17px; }
        .transaction-filter-card { overflow:hidden; border:1px solid #27618f; background:#0e2944; }
        .transaction-filter-card .card-header { padding:1rem 1.25rem!important; background:#173d63; border-bottom:1px solid #28618e; }
        .transaction-filter-card .card-header .card-title { display:flex; align-items:center; gap:.5rem; color:#f3f8ff; }
        .transaction-filter-card .card-header .material-icons-outlined { color:#8fc8ff; font-size:19px; }
        .transaction-filter-card .card-body { padding:1.2rem 1.25rem; color:#c9e0f7; background:#0e2944; }
        .transaction-filter-card .card-body>p { margin-bottom:1rem; color:#9fc3e8; font-size:.88rem; }
        .transaction-filter-card .form-label { margin-bottom:.35rem; color:#cfe4f7; font-size:.8rem; font-weight:700; }
        .transaction-filter-card .form-select { min-height:38px; color:#edf6ff; background:#091f34; border-color:#356e9f; font-size:.86rem; }
        .transaction-filter-submit { display:inline-flex; align-items:center; gap:.35rem; padding:.48rem .72rem; font-size:.8rem; font-weight:700; }
        .transaction-filter-submit .material-icons-outlined { font-size:16px; }
        .transaction-summary-card { border:1px solid #27618f; background:linear-gradient(135deg,#123754,#0d2943); box-shadow:0 9px 20px rgba(0,0,0,.14); }
        .transaction-summary-card .card-body { padding:1rem 1.1rem; background:transparent; color:#edf7ff; }
        .transaction-summary-card h5 { color:#fff; font-size:1.15rem; font-weight:750; white-space:nowrap; }
        .transaction-summary-card p { color:#acd0f4; font-size:.78rem; font-weight:650; }
        .transaction-summary-card .wh-48 { width:44px!important; height:44px!important; }
        .transaction-summary-card--total .wh-48 { background:rgba(39,135,234,.18)!important; color:#69b6ff!important; }
        .transaction-summary-card--aggregator .wh-48 { background:rgba(128,85,241,.18)!important; color:#b499ff!important; }
        .transaction-summary-card--client .wh-48 { background:rgba(46,191,151,.18)!important; color:#67e1bd!important; }
        .transaction-note { display:flex; align-items:center; gap:.5rem; padding:.75rem 1rem; color:#b8e4ff; background:rgba(12,74,100,.54); border:1px solid #1e88a9; border-radius:.55rem; font-size:.83rem; }
        .transaction-note .material-icons-outlined { color:#57caf3; font-size:18px; }
        .transaction-frame-summary { padding:1.2rem 1.25rem 0; border-top:1px solid rgba(39,97,143,.42); }
        .transaction-frame-note { padding:.75rem 1.25rem; }
        .transaction-frame-toolbar { display:flex; justify-content:flex-end; padding:.8rem 1.25rem; border-top:1px solid rgba(39,97,143,.42); }
        .transaction-frame-toolbar .btn { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .7rem; border-radius:.42rem; font-size:.8rem; font-weight:700; }
        .transaction-frame-toolbar .material-icons-outlined { font-size:16px; }
        .transaction-table-shell { padding:1rem; border:1px solid #27618f; border-radius:.8rem; background:rgba(11,37,62,.77); }
        #rows-table { margin-top:.45rem!important; color:#eaf5ff; border-color:#234d73; }
        #rows-table thead th { color:#d3e8ff; background:rgba(27,66,104,.76); border-bottom-color:#3972a0; font-size:.8rem; letter-spacing:.01em; white-space:nowrap; }
        #rows-table td { padding:.75rem .5rem; vertical-align:middle; border-color:#234d73; }
        #rows-table tbody tr:hover { background:rgba(37,94,143,.19); }
        #rows-table .btn { border-radius:.4rem; padding:.35rem .65rem; font-size:.78rem; font-weight:700; }
        .transaction-table-shell .dataTables_filter input, .transaction-table-shell .dataTables_length select { color:#edf6ff; background:#081e33; border-color:#356e9f; border-radius:.4rem; }

        /* Dark mode: neutral black surfaces without glow */
        html[data-bs-theme="dark"] body.sf-app .transaction-page-heading h4 { color:#f5f5f5; }
        html[data-bs-theme="dark"] body.sf-app .transaction-page-heading p { color:#a3a3a3; }
        html[data-bs-theme="dark"] body.sf-app .transaction-unified-frame,
        html[data-bs-theme="dark"] body.sf-app .transaction-filter-card,
        html[data-bs-theme="dark"] body.sf-app .transaction-filter-card .card-body { background:#0b0b0b; border-color:#303030; box-shadow:none!important; }
        html[data-bs-theme="dark"] body.sf-app .transaction-filter-card .card-header { background:#151515; border-color:#303030; }
        html[data-bs-theme="dark"] body.sf-app .transaction-filter-card .card-header .card-title { color:#f5f5f5; }
        html[data-bs-theme="dark"] body.sf-app .transaction-filter-card .card-header .material-icons-outlined { color:#b8b8b8; }
        html[data-bs-theme="dark"] body.sf-app .transaction-filter-card .card-body { color:#d4d4d4; }
        html[data-bs-theme="dark"] body.sf-app .transaction-filter-card .card-body>p { color:#a3a3a3; }
        html[data-bs-theme="dark"] body.sf-app .transaction-filter-card .form-label { color:#d4d4d4; }
        html[data-bs-theme="dark"] body.sf-app .transaction-filter-card .form-select { color:#f5f5f5; background:#111; border-color:#404040; box-shadow:none!important; }
        html[data-bs-theme="dark"] body.sf-app .transaction-generate-btn,
        html[data-bs-theme="dark"] body.sf-app .transaction-filter-submit { color:#fff!important; background:#171717!important; border-color:#525252!important; box-shadow:none!important; }
        html[data-bs-theme="dark"] body.sf-app .transaction-generate-btn:hover,
        html[data-bs-theme="dark"] body.sf-app .transaction-filter-submit:hover { background:#262626!important; border-color:#737373!important; }
        html[data-bs-theme="dark"] body.sf-app .transaction-frame-summary { background:#0b0b0b; border-color:#303030; }
        html[data-bs-theme="dark"] body.sf-app .transaction-summary-card { background:#141414; border-color:#303030; box-shadow:none!important; }
        html[data-bs-theme="dark"] body.sf-app .transaction-summary-card .card-body { color:#f5f5f5; }
        html[data-bs-theme="dark"] body.sf-app .transaction-summary-card h5 { color:#fff; }
        html[data-bs-theme="dark"] body.sf-app .transaction-summary-card p { color:#a3a3a3; }
        html[data-bs-theme="dark"] body.sf-app .transaction-summary-card--total .wh-48 { color:#d4d4d4!important; background:#242424!important; }
        html[data-bs-theme="dark"] body.sf-app .transaction-frame-note { background:#0b0b0b; }
        html[data-bs-theme="dark"] body.sf-app .transaction-note { color:#d4d4d4; background:#151515; border-color:#383838; }
        html[data-bs-theme="dark"] body.sf-app .transaction-note .material-icons-outlined { color:#a3a3a3; }
        html[data-bs-theme="dark"] body.sf-app .transaction-frame-toolbar { background:#0b0b0b; border-color:#303030; }
        html[data-bs-theme="dark"] body.sf-app .transaction-frame-toolbar .btn { box-shadow:none!important; }
        html[data-bs-theme="dark"] body.sf-app .transaction-table-shell { color:#e5e5e5; background:#0b0b0b; border-color:#303030; box-shadow:none!important; }
        html[data-bs-theme="dark"] body.sf-app #rows-table { color:#e5e5e5; border-color:#303030; }
        html[data-bs-theme="dark"] body.sf-app #rows-table thead th { color:#d4d4d4; background:#171717; border-color:#404040; }
        html[data-bs-theme="dark"] body.sf-app #rows-table td { border-color:#303030; }
        html[data-bs-theme="dark"] body.sf-app #rows-table tbody tr:hover { background:#191919; }
        html[data-bs-theme="dark"] body.sf-app .transaction-table-shell .dataTables_filter input,
        html[data-bs-theme="dark"] body.sf-app .transaction-table-shell .dataTables_length select { color:#f5f5f5; background:#111; border-color:#404040; box-shadow:none!important; }
        @media(max-width:700px) { .transaction-page-heading { flex-direction:column; } .transaction-generate-btn { width:100%; justify-content:center; } .transaction-filter-card .card-body { padding:1rem; } .transaction-table-shell { padding:.65rem; } }
    </style>
@endpush
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        function badge_transaction(status) {
            if (status == 'pending') {
                return `@include('components.badge-transaction', [ 'status' => 'pending' ])`;
            } else if (status == 'completed') {
                return `@include('components.badge-transaction', [ 'status' => 'completed' ])`;
            } else if (status == 'carried_over') {
                return `@include('components.badge-transaction', [ 'status' => 'carried_over' ])`;
            } else {
                return `<span class="badge bg-secondary">${status}</span>`;
            }
        }

        function copyToExport() {
            $('#export-report input[name=year]').val($('#filter-report select[name=year]').val());
            $('#export-report input[name=quarter]').val($('#filter-report select[name=quarter]').val());
            $('#export-report input[name=status]').val($('#filter-report select[name=status]').val());
            $('#export-report input[name=userid]').val($('#filter-report select[name=userid]').val());
        }

        $(document).ready(function() {
            $('#rows-table').DataTable({
                ajax: {
                    url: "{{ route('admin.releases.settlement.get_data') }}",
                    dataSrc: function(json) {
                        copyToExport();
                        return json.results;
                    },
                    data: function(d) {
                        d.year      = $('#year').val();
                        d.quarter   = $('#quarter').val();
                        d.status    = $('#status').val();
                        d.userid    = $('#userid').val();
                    }
                },
                columns: [
                    { data: "profile_name" },
                    { data: "year" },
                    {
                        data: "quarter",
                        render: {
                            display: function(data, type, row) {
                                return `Q${data}`;
                            },
                            sort: function(data, type, row) {
                                return data;
                            }
                        }
                    },
                    {
                        data: "total",
                        render: {
                            display: function(data, type, row) {
                                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(data);

                            },
                            sort: function(data, type, row) {
                                return data;
                            }
                        }
                    },
                    {
                        render: function(data, type, row) {
                            return badge_transaction(row.status);
                        }
                    },
                    {
                        data: "created_at",
                        render: {
                            display: function(data, type, row) {
                                return row.created_at ? new Date(row.created_at).toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' }) : '-';

                            },
                            sort: function(data, type, row) {
                                return data;
                            }
                        }
                    },
                    {
                        render: function(data, type, row) {
                            var url = "{{ route('admin.releases.settlement.show', ':id') }}".replace(':id', row.id);
                            return `<a href="${url}" class="btn btn-sm btn-outline-primary">Open</a>`;
                        }
                    },
                ],
            });

            $('#filter-report').on('submit', function(e) {
                e.preventDefault();
                $('#rows-table').DataTable().ajax.reload();
            });
        });
    </script>
@endpush
