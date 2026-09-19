@extends('layouts.app')
@section('title')
Publishing Contracts
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <h4 class="mb-0 fs-2">Publishing Contracts</h4>
    </div>
@endsection
@section('content')

    <table class="table table-striped" id="all-table">
        <thead>
            <th>Songwriter</th>
            <th>Document Number</th>
            <th>Publishing Title</th>
            <th>Under User</th>
            <th>Date Created</th>
            <th>Actions</th>
        </thead>
    </table>

@endsection
@push('modals')
    @can('generate-contracts')

    
    @endcan
@endpush
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
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
    </style>
@endpush
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#all-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.publishing.contract.get_data') }}",
                pageLength: 10,
                lengthChange: false,
                columnDefs: [
                    { targets: 5, orderable: false, searchable: false },
                ],
                order: [[ 4, "desc" ]],
                columns: [
                    { data: 'songwriter' },
                    {
                        data: 'docnum',
                        render: {
                            display: function(data, type, row) {
                                return data ? data : '-';
                            },
                            sort: function(data, type, row) {
                                return row.sort;
                            }
                        }
                    },
                    {
                        data: 'title',
                        render: {
                            display: function(data, type, row) {
                                return '<a href="' + "{{ route('admin.publishing.show', ':id') }}".replace(':id', row.requestid) + '" target="_blank">' + data + '</a>';
                            },
                            sort: function(data, type, row) {
                                return data;
                            }
                        }
                    },
                    { data: 'fullname' },
                    {
                        data: "created_at",
                        render: {
                            display: function(data, type, row) {
                                return data ? new Date(data).toLocaleDateString('default', { month: 'long', day: 'numeric', year: 'numeric' }) : '-';
                            },
                            sort: function(data, type, row) {
                                return data;
                            }
                        }
                    },
                    {
                        data: 'id',
                        render: function(data, type, row) {
                            if(data) {
                                var generate_url = "{{ route('admin.publishing.contract.view', ':id') }}".replace(':id', data);
                                var generate = `<a href="${generate_url}" target="_blank" class="btn btn-outline-primary btn-sm">View Contract</a>`;
                                var pdf_url = "{{ route('admin.publishing.contract.view_uploaded', ':id') }}".replace(':id', data);
                                var pdf = row.file ? `<a href="${pdf_url}" target="_blank" class="btn btn-outline-primary btn-sm">PDF</a>` : '';
                                return `<div class="d-flex justify-content-end" style="width: fit-content; gap: 5px">` + generate + pdf + `</div>`;
                            }
                        }
                    }
                ]
            });
        });
    </script>
@endpush

