@extends('layouts.app')
@section('title')
    Aggregator Reporting
@endsection
@section('header')
    <div class="reporting-page-heading">
        <div>
            <h4 class="mb-1 fs-2">Aggregator Reporting</h4>
            <p class="mb-0">Upload, review, and manage your aggregator reporting batches.</p>
        </div>
        @can('upload-reporting')
            <div class="reporting-page-actions">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                    data-bs-target="#recheck-modal"><i class="material-icons-outlined">sync</i>Recheck New ISRCs</button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#upload-modal"><i class="material-icons-outlined">upload</i>Upload Report</button>
            </div>
        @endcan
    </div>
@endsection
@section('content')
    <div class="reporting-summary-grid">
        <article class="reporting-summary-card reporting-summary-card--batches">
            <div class="reporting-summary-icon"><i class="material-icons-outlined">inventory_2</i></div>
            <div><span>Total Report Batches</span><strong>{{ number_format($reportingSummary['total_batches']) }}</strong><small>All uploaded report files</small></div>
        </article>
        <article class="reporting-summary-card reporting-summary-card--rows">
            <div class="reporting-summary-icon"><i class="material-icons-outlined">table_rows</i></div>
            <div><span>Rows Processed</span><strong>{{ number_format($reportingSummary['total_rows']) }}</strong><small>Total rows across all batches</small></div>
        </article>
        <article class="reporting-summary-card reporting-summary-card--completed">
            <div class="reporting-summary-icon"><i class="material-icons-outlined">task_alt</i></div>
            <div><span>Completed</span><strong>{{ number_format($reportingSummary['completed']) }}</strong><small>Ready to use in reporting</small></div>
        </article>
        <article class="reporting-summary-card reporting-summary-card--attention">
            <div class="reporting-summary-icon"><i class="material-icons-outlined">warning_amber</i></div>
            <div><span>Needs Attention</span><strong>{{ number_format($reportingSummary['needs_attention']) }}</strong><small>Awaiting a processing step</small></div>
        </article>
    </div>

    <ul class="nav nav-pills reporting-tabs fw-bold" id="batch">
        <li class="nav-item">
            <button class="nav-link active" id="batches-tab" data-bs-toggle="tab" data-bs-target="#batches" type="button"
                role="tab" aria-controls="batches" aria-selected="true">Report Batches</button>
        </li>
        <li class="nav-item" id="row">
            <button class="nav-link" id="rows-tab" data-bs-toggle="tab" data-bs-target="#rows" type="button" role="tab"
                aria-controls="rows" aria-selected="false">Data</button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="batches" role="tabpanel" aria-labelledby="batches-tab">
            <div class="reporting-table-shell">
            <div class="table-responsive">
                <table id="batches-table" class="table table-stripped table-sm" style="width: 100%">
                    <thead>
                        <th>File Name</th>
                        <th>Sheet</th>
                        <th># of Rows</th>
                        <th>Uploaded By</th>
                        <th>Uploaded At</th>
                        <th>Status</th>
                        <th>Action</th>
                    </thead>
                </table>
            </div>
            </div>
        </div>
        <div class="tab-pane fade reporting-data-tab" id="rows" role="tabpanel" aria-labelledby="rows-tab">
            <div class="row g-4">
                <div class="col-12">
                    <div class="card rounded-4 reporting-filter-card">
                        <div class="card-header p-3">
                            <h6 class="card-title mb-0 fw-bold">Earnings Report Filter</h6>
                        </div>
                        <div class="card-body">
                            <p>Use the filter below to view detailed earnings report. The data is filtered based on the
                                reporting month.</p>
                            <form id="filter-report">
                                {{-- <div class="row mb-3">
                                    <div class="col-lg-4 mb-3 mb-lg-0">
                                        <label for="userid" class="form-label">User</label>
                                        <select name="userid" id="userid" class="form-select form-select-sm">
                                            @foreach ($userprofiles as $userprofile)
                                            <option value="{{ $userprofile->id }}">{{ $userprofile->full_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-4 mb-3 mb-lg-0">
                                        <label for="upc" class="form-label">UPC</label>
                                        <input type="text" name="upc" id="upc" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-lg-4 mb-3 mb-lg-0">
                                        <label for="isrc" class="form-label">ISRC</label>
                                        <input type="text" name="isrc" id="isrc" class="form-control form-control-sm">
                                    </div>
                                </div> --}}
                                <div class="row mb-3 justify-content-center">
                                    <div class="col-lg-3 mb-3 mb-lg-0">
                                        <label for="profileid" class="form-label">User</label>
                                        <select name="profileid" id="profileid" class="form-select form-select-sm">
                                            <option value="">Any</option>
                                            @foreach ($userprofiles as $userprofile)
                                                <option value="{{ $userprofile->id }}">{{ $userprofile->full_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-3 mb-3 mb-lg-0">
                                        <label for="artist" class="form-label">Artist</label>
                                        <select name="artist" id="artist" class="form-select form-select-sm">
                                            <option value="">Any</option>
                                            @foreach ($artists as $artist)
                                                <option value="{{ $artist }}">{{ $artist }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3 justify-content-center">
                                    <div class="col-lg-2 col-sm-6 mb-3 mb-lg-0">
                                        <label for="period" class="form-label">Period</label>
                                        <select name="period" id="period" class="form-select form-select-sm">
                                            @foreach ($years as $year)
                                                <option value="{{ $year }}">{{ $year }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-sm-6 mb-3 mb-lg-0">
                                        <label for="quarter" class="form-label">Quarter</label>
                                        <select name="quarter" id="quarter" class="form-select form-select-sm">
                                            <option value="1">Q1</option>
                                            <option value="2">Q2</option>
                                            <option value="3">Q3</option>
                                            <option value="4">Q4</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-sm-6 mb-3 mb-lg-0">
                                        <label for="platform" class="form-label">Platform</label>
                                        <select name="platform" id="platform" class="form-select form-select-sm">
                                            <option value="">Any</option>
                                            @foreach ($platforms as $platform)
                                                <option value="{{ $platform }}">{{ $platform }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-sm-6 mb-3 mb-lg-0">
                                        <label for="region" class="form-label">Country/Region</label>
                                        <select name="region" id="region" class="form-select form-select-sm">
                                            <option value="">Any</option>
                                            @foreach ($regions as $region)
                                                <option value="{{ $region }}">{{ $region }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 justify-content-center">
                                    <button type="submit" class="btn btn-primary btn-sm">Get Data</button>
                                    <button type="submit" form="export-report" class="btn btn-outline-secondary btn-sm">
                                        <i class="material-icons-outlined material-symbols-outlined me-1"
                                            style="font-size: 16px; vertical-align: middle;">download</i>
                                        Export Report
                                    </button>
                                </div>
                            </form>
                            <form id="export-report" action="{{ route('admin.releases.reporting.export') }}" method="POST"
                                class="d-none">
                                @csrf
                                <input type="hidden" name="profileid" id="export-profileid">
                                <input type="hidden" name="artist" id="export-artist">
                                <input type="hidden" name="period" id="export-period">
                                <input type="hidden" name="quarter" id="export-quarter">
                                <input type="hidden" name="platform" id="export-platform">
                                <input type="hidden" name="region" id="export-region">
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 py-3">
                    <div class="row justify-content-center">

                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card rounded-4 reporting-revenue-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <div
                                            class="wh-48 d-flex bg-info text-info bg-opacity-10 align-items-center justify-content-center rounded-circle">
                                            <span class="material-icons-outlined">payments</span>
                                        </div>
                                        <div class="">
                                            <h5 class="mb-0" id="sum-total">Waiting for data...</h5>
                                            <p class="mb-0">Total Revenue</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card rounded-4 reporting-revenue-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <div
                                            class="wh-48 d-flex bg-success text-success bg-opacity-10 align-items-center justify-content-center rounded-circle">
                                            <span class="material-icons-outlined">corporate_fare</span>
                                        </div>
                                        <div class="">
                                            <h5 class="mb-0" id="sum-aggregator">Waiting for data...</h5>
                                            <p class="mb-0">Aggregator Share</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card rounded-4 reporting-revenue-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <div
                                            class="wh-48 d-flex bg-warning text-warning bg-opacity-10 align-items-center justify-content-center rounded-circle">
                                            <span class="material-symbols-outlined">artist</span>
                                        </div>
                                        <div class="">
                                            <h5 class="mb-0" id="sum-client">Waiting for data...</h5>
                                            <p class="mb-0">Client Share</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col-12">
                    <div class="reporting-table-shell reporting-data-table-shell">
                    <div class="table-responsive">
                        <table id="rows-table" class="table table-stripped table-sm" style="width: 100%">
                            <thead>
                                <th>Quarter</th>
                                <th>Month</th>
                                <th>Title</th>
                                <th>Artist</th>
                                <th>UPC</th>
                                <th>ISRC</th>
                                <th>Platform</th>
                                <th>Qty</th>
                                <th>Country</th>
                                <th>Total Revenue (Rupiah)</th>
                            </thead>
                        </table>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('modals')
    <div class="modal fade" id="upload-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="upload-report" method="POST" action="{{ route('admin.releases.reporting.upload') }}"
                enctype="multipart/form-data" class="m-0">
                @csrf
                <div class="modal-content">
                    <div class="modal-header border-bottom-0 py-2">
                        <h5 class="modal-title">Upload Report</h5>
                        <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                            <i class="material-icons-outlined">close</i>
                        </a>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="source" class="form-label">Report Source</label>
                            <select name="source" id="source" class="form-select" required>
                                <option value="believe">Believe</option>
                                <option value="soundon">SoundOn</option>
                                <option value="lokamusik">Lokamusik</option>
                            </select>

                        </div>
                        <div id="alert-believe" class="source-alert alert alert-warning fade show">
                            <div class="d-flex align-items-center">
                                <div class="ms-3">
                                    <h6 class="mb-0 text-warning">Attention!</h6>
                                    <div>Report must be in Excel format and has the following columns:
                                        <ul>
                                            <li>Reporting Month</li>
                                            <li>Sales Month</li>
                                            <li>Platform</li>
                                            <li>Country / Region</li>
                                            <li>Label Name</li>
                                            <li>Artist Name</li>
                                            <li>Release Title</li>
                                            <li>Track Title</li>
                                            <li>UPC</li>
                                            <li>ISRC</li>
                                            <li>Quantity</li>
                                            <li>Net Revenue</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="alert-soundon" class="source-alert alert alert-warning fade d-none">
                            <div class="d-flex align-items-center">
                                <div class="ms-3">
                                    <h6 class="mb-0 text-warning">Attention!</h6>
                                    <div>Report must be in Excel format and has the following columns:
                                        <ul>
                                            <li>Sales Period</li>
                                            <li>Reporting Period</li>
                                            <li>Track Title</li>
                                            <li>ISRC</li>
                                            <li>Track Artists</li>
                                            <li>UPC Code</li>
                                            <li>Album Title</li>
                                            <li>Store Name</li>
                                            <li>Sales Region</li>
                                            <li>Units of Sold</li>
                                            <li>Final Royalty</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="alert-lokamusik" class="source-alert alert alert-warning fade d-none">
                            <div class="d-flex align-items-center">
                                <div class="ms-3">
                                    <h6 class="mb-0 text-warning">Attention!</h6>
                                    <div>Report must be in Excel format and has the following columns:
                                        <ul>
                                            <li>period</li>
                                            <li>sales_period</li>
                                            <li>isrc</li>
                                            <li>upc</li>
                                            <li>title</li>
                                            <li>artist</li>
                                            <li>label_name</li>
                                            <li>platform</li>
                                            <li>country</li>
                                            <li>sales_type</li>
                                            <li>sales_unit</li>
                                            <li>client_net_share</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="report" class="form-label">Upload Excel File</label>
                            <input type="file" class="form-control" id="report" name="report"
                                accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="sheet" class="form-label">Sheet Name (optional)</label>
                            <input type="text" class="form-control" id="sheet" name="sheet" placeholder="Sheet1">
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="delete-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="delete-form" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header border-bottom-0 py-2">
                        <h5 class="modal-title">Delete Report</h5>
                        <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                            <i class="material-icons-outlined">close</i>
                        </a>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this report?</p>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="recheck-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="recheck-form" method="POST" action="{{ route('admin.releases.reporting.recheck') }}">
                    @csrf
                    <div class="modal-header border-bottom-0 py-2">
                        <h5 class="modal-title">Recheck Report</h5>
                        <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                            <i class="material-icons-outlined">close</i>
                        </a>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to recheck existing report?</p>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Proceed Recheck</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush
@push('css')
<link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
<style>
    .reporting-page-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1.5rem; margin-bottom:2rem; }
    .reporting-page-heading h4 { color:#f5f8ff; font-weight:700; letter-spacing:-.025em; }
    .reporting-page-heading p { color:#8fb9e8; font-size:.95rem; }
    .reporting-page-actions { display:flex; flex-wrap:wrap; gap:.7rem; }
    .reporting-page-actions .btn { display:inline-flex; align-items:center; gap:.38rem; border-radius:.48rem; padding:.48rem .72rem; font-size:.82rem; line-height:1.2; font-weight:700; }
    .reporting-page-actions .material-icons-outlined { font-size:16px; }
    .reporting-summary-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; margin-bottom:1.6rem; background:transparent; }
    .reporting-summary-card { --reporting-accent:#60a5fa; position:relative; min-height:124px; display:flex; align-items:flex-start; gap:.85rem; padding:1rem 1.1rem; overflow:hidden; border:1px solid color-mix(in srgb,var(--reporting-accent) 55%,#334155); border-top:3px solid var(--reporting-accent); border-radius:.75rem; background:transparent; box-shadow:none; transition:transform .2s ease,border-color .2s ease; }
    .reporting-summary-card::after { content:""; position:absolute; right:-26px; bottom:-32px; width:78px; height:78px; border:1px solid color-mix(in srgb,var(--reporting-accent) 42%,transparent); border-radius:50%; opacity:.55; transition:transform .25s ease,opacity .2s ease; pointer-events:none; }
    .reporting-summary-card:hover { transform:translateY(-4px); border-color:var(--reporting-accent); }
    .reporting-summary-card:hover::after { transform:scale(1.18); opacity:.9; }
    .reporting-summary-icon { flex:0 0 46px; height:46px; display:grid; place-items:center; border:1px solid color-mix(in srgb,var(--reporting-accent) 45%,transparent); border-radius:.75rem; color:var(--reporting-accent); background:transparent; font-size:24px; transition:transform .2s ease; }
    .reporting-summary-card:hover .reporting-summary-icon { transform:scale(1.08) rotate(-4deg); }
    .reporting-summary-icon .material-icons-outlined { font-size:25px; }
    .reporting-summary-card span,.reporting-summary-card small { display:block; color:#a7c7e8; font-size:.82rem; line-height:1.3; }
    .reporting-summary-card strong { display:block; margin:.1rem 0 .2rem; color:var(--reporting-accent); font-size:1.72rem; line-height:1.05; }
    .reporting-summary-card--batches { --reporting-accent:#60a5fa; }
    .reporting-summary-card--rows { --reporting-accent:#a78bfa; }
    .reporting-summary-card--completed { --reporting-accent:#34d399; }
    .reporting-summary-card--attention { --reporting-accent:#fbbf24; }
    @media (prefers-reduced-motion:reduce) { .reporting-summary-card,.reporting-summary-card::after,.reporting-summary-icon { transition:none!important; } .reporting-summary-card:hover,.reporting-summary-card:hover .reporting-summary-icon { transform:none!important; } }
    .reporting-tabs { gap:.35rem; margin-bottom:1rem; border-bottom:1px solid #2a5a85; }
    .reporting-tabs .nav-link { border-radius:.65rem .65rem 0 0; color:#bed8f2; padding:.78rem 1.15rem; }
    .reporting-tabs .nav-link.active { color:#fff; background:#173e67; border:1px solid #3981bd; border-bottom-color:#173e67; }
    .reporting-table-shell { padding:1rem; border:1px solid #245781; border-radius:.8rem; background:rgba(11,37,62,.76); }
    .reporting-table-shell .dataTables_filter input { min-width:220px; border-color:#326a9c; background:#071c30; color:#eef7ff; border-radius:.4rem; }
    .reporting-table-shell .dataTables_length select { border-color:#326a9c; background:#071c30; color:#eef7ff; border-radius:.4rem; }
    #batches-table { margin-top:.45rem!important; color:#e9f4ff; border-color:#234d73; }
    #batches-table thead th { color:#d3e8ff; background:rgba(27,66,104,.72); border-bottom-color:#3871a0; font-size:.82rem; letter-spacing:.02em; white-space:nowrap; }
    #batches-table td { padding:.82rem .5rem; vertical-align:middle; border-color:#234d73; }
    #batches-table tbody tr:hover { background:rgba(37,94,143,.19); }
    #batches-table td:first-child { max-width:350px; color:#fff; font-weight:600; overflow-wrap:anywhere; }
    #batches-table .btn { border-radius:.4rem; font-weight:700; }
    .reporting-data-tab { padding-top:.1rem; }
    .reporting-filter-card { overflow:hidden; border:1px solid #285984; background:#0e2944; }
    .reporting-filter-card .card-header { padding:1rem 1.25rem!important; color:#f3f8ff; background:#173d63; border-bottom:1px solid #285984; }
    .reporting-filter-card .card-body { padding:1.25rem; color:#c5dcf5; background:#0d2944; }
    .reporting-filter-card .card-body > p { margin:0 0 1.1rem; color:#9cc2e8; font-size:.88rem; }
    .reporting-filter-card .form-label { margin-bottom:.35rem; color:#cfe3f8; font-size:.8rem; font-weight:700; }
    .reporting-filter-card .form-select { min-height:38px; color:#edf6ff; background-color:#091e32; border-color:#346d9f; font-size:.86rem; }
    .reporting-filter-card .btn { padding:.45rem .7rem; border-radius:.42rem; font-size:.8rem; font-weight:700; }
    .reporting-revenue-card { border:1px solid #285984; background:#102d49; box-shadow:0 8px 20px rgba(0,0,0,.12); }
    .reporting-revenue-card .card-body { padding:1rem; color:#eaf5ff; background:transparent; }
    .reporting-revenue-card h5 { color:#fff; font-size:1.05rem; font-weight:700; }
    .reporting-revenue-card p { color:#9fc5eb; font-size:.78rem; font-weight:600; }
    .reporting-revenue-card .wh-48 { width:42px!important; height:42px!important; }
    .reporting-data-table-shell { margin-top:.15rem; }
    #rows-table { margin-top:.45rem!important; color:#e9f4ff; border-color:#234d73; }
    #rows-table thead th { color:#d3e8ff; background:rgba(27,66,104,.72); border-bottom-color:#3871a0; font-size:.78rem; letter-spacing:.01em; white-space:nowrap; }
    #rows-table td { padding:.7rem .45rem; font-size:.78rem; line-height:1.32; vertical-align:middle; border-color:#234d73; }
    #rows-table tbody tr:hover { background:rgba(37,94,143,.19); }
    .reporting-data-table-shell .dataTables_filter input, .reporting-data-table-shell .dataTables_length select { border-color:#326a9c; background:#071c30; color:#eef7ff; border-radius:.4rem; }
    @media (max-width: 1100px) { .reporting-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width: 700px) { .reporting-page-heading { flex-direction:column; } .reporting-summary-grid { grid-template-columns:1fr; } .reporting-page-actions { width:100%; } .reporting-page-actions .btn { flex:1; justify-content:center; } .reporting-table-shell { padding:.65rem; } }
</style>
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script type="application/json" id="json-batches">@json($batches)</script>
    <script>
        function badge_reporting(status) {
            if (status == 'pending_import') {
                return `@include('components.badge-reporting', ['status' => 'pending_import'])`;
            } else if (status == 'pending_review') {
                return `@include('components.badge-reporting', ['status' => 'pending_review'])`;
            } else if (status == 'pending_rate') {
                return `@include('components.badge-reporting', ['status' => 'pending_rate'])`;
            } else if (status == 'pending_calculation') {
                return `@include('components.badge-reporting', ['status' => 'pending_calculation'])`;
            } else if (status == 'failed') {
                return `@include('components.badge-reporting', ['status' => 'failed'])`;
            } else if (status == 'completed') {
                return `@include('components.badge-reporting', ['status' => 'completed'])`;
            } else {
                return `<span class="badge bg-secondary">${status}</span>`;
            }
        }
        function deleteReport(id) {
            $('#delete-form').attr('action', "{{ route('admin.releases.reporting.destroy', ':id') }}".replace(':id', id));
            $('#delete-modal').modal('show');
        }
        $(document).ready(function () {
            $('#source').change(function (e) {
                $('.source-alert').removeClass('fade show');
                $('.source-alert').addClass('d-none');
                $('#alert-' + e.target.value).removeClass('d-none');
                $('#alert-' + e.target.value).addClass('fade show');
            });

            $('#batches-table').DataTable({
                data: JSON.parse(document.getElementById('json-batches').textContent),
                order: [[4, 'desc']],
                columns: [
                    { data: 'original_filename' },
                    { data: 'sheet' },
                    { data: 'total_rows' },
                    { data: 'uploader' },
                    {
                        data: "created_at",
                        render: {
                            display: function (data, type, row) {
                                return row.created_at ? new Date(row.created_at).toLocaleString('default', { year: 'numeric', month: 'long', day: 'numeric' }) : '-';
                            },
                            sort: function (data, type, row) {
                                return data;
                            }
                        }
                    },
                    {
                        data: 'status',
                        render: {
                            display: function (data, type, row) {
                                return badge_reporting(data);
                            },
                            sort: function (data, type, row) {
                                return data;
                            }
                        }
                    },
                    {
                        render: function (data, type, row) {
                            @can('delete-reporting')
                                var url = "{{ route('admin.releases.reporting.show', ':id') }}".replace(':id', row.id);
                                return `<div class="d-flex justify-content-end">
                                                                                    <a href="${url}" class="btn btn-outline-primary btn-sm mx-1 w-100">View</a>
                                                                                    <button class="btn btn-outline-danger btn-sm mx-1 w-100" onclick="deleteReport(${row.id})">Delete</button>
                                                                                </div>`;
                            @else
                                                                        var url = "{{ route('admin.releases.reporting.show', ':id') }}".replace(':id', row.id);
                                return `<div class="d-flex justify-content-end">
                                                                                    <a href="${url}" class="btn btn-outline-primary btn-sm mx-1 w-100">View</a>
                                                                                </div>`;
                            @endcan
                                            }
                    }
                ],
            });

            $('#rows-table').DataTable({
                "ajax": {
                    "url": "{{ route('admin.releases.reporting.get_data') }}",
                    "dataType": "json",
                    "type": "GET",
                    "dataSrc": function (json) {
                        $('#sum-total').text(json.sum_total);
                        $('#sum-aggregator').text(json.sum_aggregator);
                        $('#sum-client').text(json.sum_client);
                        return json.results;
                    },
                    "data": function (d) {
                        d.profileid = $('#profileid').val();
                        d.artist = $('#artist').val();
                        d.upc = $('#upc').val();
                        d.isrc = $('#isrc').val();
                        d.platform = $('#platform').val();
                        d.region = $('#region').val();
                        d.period = $('#period').val();
                        d.quarter = $('#quarter').val();
                    },
                },
                "columns": [
                    { "data": "quarter" },
                    { "data": "month" },
                    { "data": "track_title" },
                    { "data": "artist_name" },
                    { "data": "upc" },
                    { "data": "isrc" },
                    { "data": "platform" },
                    { "data": "views" },
                    { "data": "region" },
                    {
                        "data": "rupiah",
                        // "render": function(data, type, row) {
                        //     return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(data);
                        // },
                    }
                ],
            });

            $('#filter-report').on('submit', function (e) {
                e.preventDefault();
                $('#rows-table').DataTable().ajax.reload();
            });

            // Handle export form submission
            $('#export-report').submit(function (e) {
                // Copy filter values to export form
                $('#export-profileid').val($('#filter-report select[name=profileid]').val());
                $('#export-artist').val($('#filter-report select[name=artist]').val());
                $('#export-period').val($('#filter-report select[name=period]').val());
                $('#export-quarter').val($('#filter-report select[name=quarter]').val());
                $('#export-platform').val($('#filter-report select[name=platform]').val());
                $('#export-region').val($('#filter-report select[name=region]').val());
            });
        });
    </script>
@endpush
