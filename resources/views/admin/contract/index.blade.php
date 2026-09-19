@extends('layouts.app')
@section('title')
Contracts
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <h4 class="mb-0 fs-2">Contracts</h4>
        @can('generate-contracts')
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#create-contract-modal">Create Contract</button>
        @endcan
    </div>
@endsection
@section('content')

    <ul class="nav nav-pills mb-3 fw-bold">
        <li class="nav-item">
            <button class="nav-link active" id="contracts-tab" data-bs-toggle="tab" data-bs-target="#contracts" type="button" role="tab" aria-controls="contracts" aria-selected="true">All Contracts</button>
        </li>
        <li class="nav-item" id="per-user">
            <button class="nav-link" id="per-users-tab" data-bs-toggle="tab" data-bs-target="#per-users" type="button" role="tab" aria-controls="per-users" aria-selected="false">Per User</button>
        </li>
    </ul>

    <div class="tab-content pb-5">
        <div class="tab-pane fade show active" id="contracts" role="tabpanel" aria-labelledby="contracts-tab">
            <table class="table table-striped" id="contracts-table">
                <thead>
                    <th>User</th>
                    <th>Type</th>
                    <th>Document Number</th>
                    <th>Date Created</th>
                    <th>Actions</th>
                </thead>
            </table>
        </div>
        <div class="tab-pane fade" id="per-users" role="tabpanel" aria-labelledby="per-users-tab">

            <ul class="nav nav-pills mb-3 fw-bold">
                <li class="nav-item">
                    <button class="nav-link active" id="aggregators-tab" data-bs-toggle="tab" data-bs-target="#aggregators" type="button" role="tab" aria-controls="aggregators" aria-selected="true">Aggregator</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="aggregators" role="tabpanel" aria-labelledby="aggregators-tab">
                    <table class="table table-striped table-sm" style="width:100%" id="aggregators-table">
                        <thead>
                            <th>User</th>
                            <th>Document Number</th>
                            <th>Actions</th>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('modals')
    @can('generate-contracts')
        <div class="modal fade" id="create-contract-modal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-bottom-0 py-2">
                        <h5 class="modal-title">Create Contract</h5>
                        <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                            <i class="material-icons-outlined">close</i>
                        </a>
                    </div>
                    <div class="modal-body">
                        <p>Please input the contract details</p>
                        <form id="create-contract-form" action="{{ route('admin.releases.contract.aggregator.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="type" value="aggregator">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="profileid">User <span class="text-danger">*</span></label>
                                    <select name="profileid" id="profileid" class="form-select form-select-sm" required>
                                        @foreach ($userprofiles as $userprofile)
                                            <option value="{{ $userprofile->id }}">{{ $userprofile->full_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="type">Type <span class="text-danger">*</span></label>
                                    <select name="type" class="form-control" required>
                                        <option value="aggregator">Aggregator</option>
                                        <option value="Publishing" disabled>Publishing</option>
                                        <option value="mcn" disabled>MCN</option>
                                    </select>
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label for="number">Document Number <span class="text-danger">*</span></label>
                                    <input type="number" name="number" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="month">Month <span class="text-danger">*</span></label>
                                    <select type="text" name="month" class="form-control" required>
                                        <option value="1">January (I)</option>
                                        <option value="2">February (II)</option>
                                        <option value="3">March (III)</option>
                                        <option value="4">April (IV)</option>
                                        <option value="5">May (V)</option>
                                        <option value="6">June (VI)</option>
                                        <option value="7">July (VII)</option>
                                        <option value="8">August (VIII)</option>
                                        <option value="9">September (IX)</option>
                                        <option value="10">October (X)</option>
                                        <option value="11">November (XI)</option>
                                        <option value="12">December (XII)</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="year">Year <span class="text-danger">*</span></label>
                                    <input type="text" name="year" class="form-control" required>
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
        #aggregators-table td{
            height: 40px;
        }
        #aggregators-table td:first-child {
            /* white-space: nowrap; */
            width: 30%;
        }
        #aggregators-table td:last-child{
            width: 15%;
        }
    </style>
@endpush
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script type="application/json" id="json-contracts">@json($contracts)</script>
    <script type="application/json" id="json-aggregators">@json($aggregators)</script>
    
    <script>
        $(document).ready(function() {
            $('#contracts-table').DataTable({
                data: JSON.parse(document.getElementById('json-contracts').textContent),
                responsive: true,
                columnDefs: [
                    { responsivePriority: 1, targets: 2 },
                    { responsivePriority: 2, targets: 4 },
                    { responsivePriority: 3, targets: 0 },
                    { responsivePriority: 4, targets: 3 },
                    { responsivePriority: 5, targets: 1 }
                ],

                order: [[ 2, "asc" ]],
                columns: [
                    { data: 'full_name' },
                    { data: 'type' },
                    {
                        data: 'pretty',
                        render: {
                            display: function(data, type, row) {
                                return data;
                            },
                            sort: function(data, type, row) {
                                return row.sort;
                            }
                        }
                    },
                    {
                        data: "created_at",
                        render: {
                            display: function(data, type, row) {
                                return row.created_at ? new Date(row.created_at).toLocaleDateString('default', { month: 'long', day: 'numeric', year: 'numeric' }) : '-';
                            },
                            sort: function(data, type, row) {
                                return data;
                            }
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            var generate_url = "{{ route('admin.releases.contract.view_raw', ':id') }}".replace(':id', row.id);
                            var generate = `<a href="${generate_url}" target="_blank" class="btn btn-outline-primary btn-sm">View</a>`;
                            var pdf_url = "{{ route('admin.releases.contract.view_uploaded', ':id') }}".replace(':id', row.id);
                            var pdf = row.file ? `<a href="${pdf_url}" target="_blank" class="btn btn-outline-primary btn-sm">PDF</a>` : '';
                            return `<div class="d-flex justify-content-end" style="width: fit-content; gap: 5px">` + generate + pdf + `</div>`;
                        }
                    }
                ]
            });

            $('#aggregators-table').DataTable({
                data: JSON.parse(document.getElementById('json-aggregators').textContent),
                responsive: true,
                columnDefs: [
                    { responsivePriority: 1, targets: 2 },
                    { responsivePriority: 2, targets: 0 },
                    { responsivePriority: 3, targets: 1 }
                ],
                order: [[ 0, "asc" ]],
                columns: [
                    { data: 'full_name' },
                    { data: 'contract' },
                    {
                        data: null,
                        render: function(data, type, row) {
                            
                            var generate_url = "{{ route('admin.releases.contract.view_raw', ':id') }}".replace(':id', row.contract_id);
                            var generate = row.contract_id ? `<a href="${generate_url}" target="_blank" class="btn btn-outline-primary btn-sm">View</a>` : '';
                            var pdf_url = "{{ route('admin.releases.contract.view_uploaded', ':id') }}".replace(':id', row.contract_id);
                            var pdf = row.file ? `<a href="${pdf_url}" target="_blank" class="btn btn-outline-primary btn-sm">PDF</a>` : '';
                            return `<div class="d-flex justify-content-end" style="width: fit-content; gap: 5px">` + generate + pdf + `</div>`;
                        }
                    }
                ]
            });
            
        });
    </script>
@endpush
