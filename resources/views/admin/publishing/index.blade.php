@extends('layouts.app')
@section('title')
All Publishing
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <h4 class="mb-0 fs-2">All Publishing</h4>
        {{-- @can('create-releases')
            <a href="{{ route('admin.publishing.create') }}" class="btn btn-primary btn-sm">Create Publishing for User</a>
        @endcan --}}
    </div>
@endsection
@section('content')
    @php
        $sections = [
            'pending' => [
                'name' => 'Pending',
                'icon' => 'hourglass_empty'
            ],
            'review' => [
                'name' => 'Under Review',
                'icon' => 'overview'
            ],
            'rejected' => [
                'name' => 'Rejected',
                'icon' => 'cancel_presentation'
            ],
            'all' => [
                'name' => 'All Publishing',
                'icon' => 'all_inclusive'
            ],
            'draft' => [
                'name' => 'Draft',
                'icon' => 'description'
            ]
        ];
    @endphp
    <ul class="nav nav-pills mb-4">
        @foreach ($sections as $key => $section)
            <li class="nav-item">
                @if ($loop->first)
                    <a class="nav-link active" data-bs-toggle="pill" href="#tabs-{{ $key }}" role="tab" aria-selected="true">
                @else
                    <a class="nav-link" data-bs-toggle="pill" href="#tabs-{{ $key }}" role="tab" aria-selected="false" tabindex="-1">
                @endif
                    <div class="d-flex align-items-center">
                        <div class="tab-icon"><i class="material-icons-outlined material-symbols-outlined me-1">{{ $section['icon'] }}</i>
                        </div>
                        <div class="tab-title">{{ $section['name'] }}</div>
                    </div>
                </a>
            </li>
        @endforeach
    </ul>
    <div class="tab-content" id="tab-content">
        @foreach ($sections as $key => $section)
            @if ($loop->first)
                <div class="tab-pane fade show active" id="tabs-{{ $key }}" role="tabpanel">
            @else
                <div class="tab-pane fade" id="tabs-{{ $key }}" role="tabpanel">
            @endif
                <table class="table albums table-striped" id="{{ $key }}" style="width: 100%">
                    <thead>
                        <tr>
                            <th>Track Title</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Finished At</th>
                            <th>Uploaded By</th>
                            <th>Uploaded At</th>
                            <th>Contract Expired At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endforeach
    </div>
@endsection

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .publishing-type {
            cursor: pointer;
        }
        img.cover {
            object-fit: cover;
        }
        .publishing-type {
            transition: background-color 0.3s, color 0.3s;
        }
        .publishing-type:hover {
            background-color: #f5f5f5;
            color: black;
        }
        .albums td {
            vertical-align: middle;
            width: fit-content;
        }
        .albums td .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: end;
        }
        .albums td .actions a {
            flex: 1 1 45%;
            box-sizing: border-box;
            padding: 10px;
            margin: 0px 5px;
        }
        @media (max-width: 768px) {
            #albums td .actions a {
                flex: 1 1 100%;
            }
        }
    </style>
@endpush
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        function badge_publishing(status) {
            if (status == 'pending') {
                return `@include('components.badge-publishing', [ 'status' => 'pending' ])`;
            } else if (status == 'review') {
                return `@include('components.badge-publishing', [ 'status' => 'review' ])`;
            } else if (status == 'completed') {
                return `@include('components.badge-publishing', [ 'status' => 'completed' ])`;
            } else if (status == 'rejected') {
                return `@include('components.badge-publishing', [ 'status' => 'rejected' ])`;
            } else if (status == 'cancelled') {
                return `@include('components.badge-publishing', [ 'status' => 'cancelled' ])`;
            } else if (status == 'draft') {
                return `@include('components.badge-publishing', [ 'status' => 'draft' ])`;
            }else {
                return `<span class="badge bg-secondary">${status}</span>`;
            }
        }

        $(document).ready(function() {

            // Initialize DataTable
            ['pending', 'review', 'rejected', 'draft', 'all'].forEach(element => {
                let url = "{{ route('admin.publishing.get_data', ':status') }}".replace(':status', element);
                $('#' + element).DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: url,
                    responsive: true,
                    pageLength: 10,
                    lengthChange: false,
                    columnDefs: [
                        { responsivePriority: 1, targets: 0 },
                        { responsivePriority: 2, targets: -1, orderable: false, searchable: false },
                        { responsivePriority: 3, targets: -3 },
                    ],
                    order: [[5, 'desc']],
                    columns: [
                        {
                            render: function(data, type, row) {
                            var artist = row.performer ?? '-';
                            var title = row.title ?? 'Untitled Track'
                            return `
                                <div class="d-flex flex-row align-items-center">
                                    <div class="ms-3">
                                        <p class="fw-bold mb-1">${title}</p>
                                        <p class="text-muted mb-0">${artist}</p>
                                    </div>
                                </div>
                            `;
                            }
                        },
                        {
                            render: function(data, type, row) {
                            return badge_publishing(row.status);
                            }
                        },
                        {
                            data: "due_date",
                            render: {
                                display: function(data, type, row) {
                                    return data ? new Date(data).toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' }) : '-';

                                },
                                sort: function(data, type, row) {
                                    return data;
                                }
                            }
                        },
                        {
                            data: "finished_at",
                            render: {
                                display: function(data, type, row) {
                                    return data ? new Date(data).toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' }) : '-';

                                },
                                sort: function(data, type, row) {
                                    return data;
                                }
                            }
                        },
                        {
                            data: "fullname",
                            defaultContent: "-",
                            render: function(data, type, row) {
                                return data;
                            }
                        },
                        {
                            data: "created_at",
                            render: {
                                display: function(data, type, row) {
                                    return data ? new Date(data).toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' }) : '-';

                                },
                                sort: function(data, type, row) {
                                    return data;
                                }
                            }
                        },
                        {
                            data: "contract_expired_at",
                            render: {
                                display: function(data, type, row) {
                                    return data ? new Date(data).toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' }) : '-';

                                },
                                sort: function(data, type, row) {
                                    return data;
                                }
                            }
                        },
                        {
                            data: "id",
                            render: function(data, type, row) {
                                var link = "{{ route('admin.publishing.show', ':id') }}".replace(':id', data);
                                return `<div class="actions"><a href="${link}" class="btn btn-sm btn-outline-primary">View</a>`;
                            }
                        }
                    ],
                });
            });
        });
    </script>
@endpush
