@extends('layouts.app')
@section('title')
My Transaction
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <h4 class="mb-0 fs-2">My Transactions</h4>
    </div>
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <table id="transactions-table" class="table table-striped">
                <thead>
                    <th>Year</th>
                    <th>Quarter</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Generated At</th>
                    <th>Action</th>
                </thead>
            </table>
        </div>
    </div>
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script type="application/json" id="json-transaction">@json($transactions)</script>
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

        $(document).ready(function() {
            $('#transactions-table').DataTable({
                data: JSON.parse(document.getElementById('json-transaction').textContent),
                columns: [
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
                            var url = "{{ route('transactions.show', ':id') }}".replace(':id', row.id);
                            return `<a href="${url}" class="btn btn-sm btn-outline-primary">Detail</a>`;
                        }
                    },
                ]
            });
        });
    </script>
@endpush