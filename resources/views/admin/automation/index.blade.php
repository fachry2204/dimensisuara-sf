@extends('layouts.app')
@section('title')
Automation Status
@endsection
@section('header')
    <div class="automation-header"><div><h1>Automation</h1><p>Pantau batch, antrean pekerjaan, dan pekerjaan yang perlu dicoba ulang.</p></div><span><i class="material-icons-outlined">settings_suggest</i> Job monitor</span></div>
@endsection
@section('content')
    <div class="automation-summary"><article><i class="material-icons-outlined blue">queue</i><div><small>Menunggu diproses</small><strong>{{ count($jobs) }}</strong><span>Job dalam antrean</span></div></article><article><i class="material-icons-outlined green">task_alt</i><div><small>Batch tersedia</small><strong>{{ count($batches) }}</strong><span>Riwayat batch</span></div></article><article><i class="material-icons-outlined orange">warning_amber</i><div><small>Gagal diproses</small><strong>{{ count($fails) }}</strong><span>Memerlukan retry</span></div></article></div>
    <div class="row automation-page">
        <div class="col-12 mb-3">
            <div class="card automation-panel">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Job Batches</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm" id="batches-table">
                        <thead>
                            <th>Name</th>
                            <th>Queued At</th>
                            <th>Failed At</th>
                            <th>Status</th>
                            <th>Action</th>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="card automation-panel">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Pending Jobs</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm" id="jobs-table">
                        <thead>
                            <th>Name</th>
                            <th>Queued At</th>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="card automation-panel">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Failed Jobs</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm" id="fails-table">
                        <thead>
                            <th>Name</th>
                            <th>Failed At</th>
                            <th>Action</th>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>    
@endsection
@push('modals')
    <!-- retry job modal -->
    <div class="modal fade" id="retry-job-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="retryJobModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="retryJobModalLabel">Retry Job</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" method="POST" id="retry-job-form">
                    @csrf
                    @method('POST')
                    <input type="hidden" name="job_id" id="job-id">
                    <div class="modal-body">
                        <p>Are you sure you want to retry this job?</p>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Retry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- retry batch modal -->
    <div class="modal fade" id="retry-batch-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="retryBatchModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="retryBatchModalLabel">Retry Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="#" method="POST" id="retry-batch-form">
                    @csrf
                    @method('POST')
                    <div class="modal-body">
                        <p>Are you sure you want to retry this batch?</p>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Retry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
    .sf-app .main-wrapper .main-content{max-width:1440px!important;padding:28px 32px 46px!important;background:#081225!important}.automation-header{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:20px}.automation-header h1{margin:0;color:#f5f9ff!important;font-size:1.9rem!important;font-weight:800!important}.automation-header p{margin:6px 0 0;color:#91afd0;font-size:.78rem}.automation-header>span{display:inline-flex;align-items:center;gap:5px;padding:8px 10px;border:1px solid #345a82;border-radius:8px;color:#9ec7ef;font-size:.69rem;font-weight:700}.automation-header i{font-size:17px}.automation-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:17px}.automation-summary article{display:flex;align-items:center;gap:13px;min-height:105px;padding:16px;border:1px solid #294c71;border-radius:13px;background:linear-gradient(140deg,#0f223a,#0a192c)}.automation-summary article>i{display:grid;place-items:center;width:44px;height:44px;border-radius:11px;font-size:23px}.automation-summary .blue{background:rgba(88,158,255,.16);color:#79b5ff}.automation-summary .green{background:rgba(73,219,155,.15);color:#65e2aa}.automation-summary .orange{background:rgba(255,151,92,.15);color:#ffab7f}.automation-summary small,.automation-summary span{display:block;color:#93b3d4;font-size:.68rem}.automation-summary strong{display:block;margin:2px 0;color:#f5f9ff;font-size:1.43rem;line-height:1.05}.automation-summary span{color:#789aba;font-size:.64rem}.automation-panel{overflow:hidden;border:1px solid #294c71!important;border-radius:14px!important;background:linear-gradient(145deg,#0e2036,#0a192b)!important}.automation-panel .card-header{padding:17px 19px;border-color:rgba(75,116,157,.35)!important;background:transparent!important;color:#f3f8ff}.automation-panel .card-body{padding:6px 14px 14px}.automation-panel .table{color:#c4d6e8}.automation-panel .table th{border-color:#3a5b7d;color:#8eb3d7;font-size:.63rem;text-transform:uppercase}.automation-panel .table td{border-color:rgba(57,90,124,.48);font-size:.75rem}.automation-panel .dataTables_filter input,.automation-panel .dataTables_length select{border:1px solid #365b83;border-radius:7px;background:#09192b;color:#edf6ff}.automation-panel .dataTables_paginate .paginate_button{border:1px solid #365b83!important;border-radius:6px!important;color:#b8d4ee!important;background:transparent!important}.automation-panel .dataTables_paginate .paginate_button.current{border-color:#ff7146!important;background:#ff7146!important;color:#fff!important}@media(max-width:700px){.sf-app .main-wrapper .main-content{padding:18px!important}.automation-header{align-items:flex-start;flex-direction:column}.automation-summary{grid-template-columns:1fr}}
    </style>
@endpush
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script type="application/json" id="json-batches">@json($batches)</script>
    <script type="application/json" id="json-jobs">@json($jobs)</script>
    <script type="application/json" id="json-fails">@json($fails)</script>
    <script>
        $(document).ready(function() {
            $('#batches-table').DataTable({
                data: JSON.parse(document.getElementById('json-batches').textContent),
                // responsive: true,
                order: [[ 1, "asc" ]],
                columns: [
                    { data: 'name' },
                    {
                        data: 'created_at',
                        render: {
                            display: function(data, type, row) {
                                return row.created_at ? new Date(row.created_at * 1000).toLocaleString('default', { 
                                    year: 'numeric', month: 'long', day: 'numeric', 
                                    hour: '2-digit', minute: '2-digit', second: '2-digit' 
                                }) : '-';
                            },
                            sort: function(data, type, row) {
                                return data;
                            }
                        }
                    },
                    {
                        data: 'cancelled_at',
                        render: {
                            display: function(data, type, row) {
                                return row.cancelled_at ? new Date(row.cancelled_at * 1000).toLocaleString('default', { 
                                    year: 'numeric', month: 'long', day: 'numeric', 
                                    hour: '2-digit', minute: '2-digit', second: '2-digit' 
                                }) : '-';
                            },
                            sort: function(data, type, row) {
                                return data;
                            }
                        }
                    },
                    {
                        data: 'cancelled_at',
                        render: {
                            display: function(data, type, row) {
                                return row.cancelled_at ? '<span class="badge bg-danger">Failed</span>' : '<span class="badge bg-warning">Waiting</span>';
                            },
                        }
                    },
                    {
                        data: 'cancelled_at',
                        render: {
                            display: function(data, type, row) {
                                var link = '{{ route('admin.automation.retry_batch', ':id') }}'.replace(':id', row.id);
                                return `<div class="actions"><button data-link="${link}" class="btn btn-sm btn-outline-primary retry-batch" data-bs-toggle="modal" data-bs-target="#retry-batch-modal">Retry</button></div>`;
                            },
                        }
                    },
                ]
            });

            $('#jobs-table').DataTable({
                data: JSON.parse(document.getElementById('json-jobs').textContent),
                order: [[ 1, "desc" ]],
                columns: [
                    {
                        data: 'payload',
                        render: function(data, type, row) {
                            return JSON.parse(row.payload).displayName;
                        }
                    },
                    {
                        data: 'created_at',
                        render: {
                            display: function(data, type, row) {
                                return row.created_at ? new Date(row.created_at * 1000).toLocaleString('default', { 
                                    year: 'numeric', month: 'long', day: 'numeric', 
                                    hour: '2-digit', minute: '2-digit', second: '2-digit' 
                                }) : '-';
                            },
                            sort: function(data, type, row) {
                                return data;
                            }
                        }
                    },
                ]
            });

            $('#fails-table').DataTable({
                data: JSON.parse(document.getElementById('json-fails').textContent),
                order: [[ 1, "desc" ]],
                columns: [
                    {
                        data: 'payload',
                        render: function(data, type, row) {
                            return JSON.parse(row.payload).displayName;
                        }
                    },
                    {
                        data: 'failed_at',
                        render: {
                            display: function(data, type, row) {
                                return row.failed_at ? new Date(row.failed_at).toLocaleString('default', {
                                    year: 'numeric', month: 'long', day: 'numeric',
                                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                                }) : '-';
                            },
                            sort: function(data, type, row) {
                                return data;
                            }
                        }
                    },
                    {
                        data: 'failed_at',
                        render: {
                            display: function(data, type, row) {

                                if(row.failed_at) {
                                    var link = '{{ route('admin.automation.retry', ':id') }}'.replace(':id', row.id);
                                    return `<div class="actions"><button data-link="${link}" class="btn btn-sm btn-outline-primary retry-job" data-bs-toggle="modal" data-bs-target="#retry-job-modal">Retry</button></div>`;
                                }
                            },

                        }
                    }
                ]
            });

            $('.retry-job').on('click', function() {
                var link = $(this).data('link');
                $('#retry-job-form').attr('action', link);
            });

            $('.retry-batch').on('click', function() {
                var link = $(this).data('link');
                $('#retry-batch-form').attr('action', link);
            });
        });
    </script>
@endpush
