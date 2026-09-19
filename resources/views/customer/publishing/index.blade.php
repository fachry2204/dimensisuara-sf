@extends('layouts.app')
@section('title')
    Publishing
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <h4 class="mb-0 fs-2">Publishing</h4>
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('publishing.create') }}" class="btn btn-primary btn-sm">Add Publishing Request</a>
        </div>
    </div>
@endsection
@section('content')
    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="pill" href="#tabs-requests" role="tab" aria-selected="true">
                <div class="d-flex align-items-center">
                    <div class="tab-icon"><i class="material-icons-outlined me-1">description</i>
                    </div>
                    <div class="tab-title">Publishing Requests</div>
                </div>
            </a>
        </li>
    </ul>
    <div class="tab-content" id="tab-content">
        <div class="tab-pane fade show active" id="tabs-requests" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-striped" id="requests-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Artist</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Due Date</th>
                            <th style="width: 20%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@push('modals')
    <!-- confirm deletion modal -->
    <div class="modal fade" id="confirm-delete-publishing-modal" tabindex="-1" aria-labelledby="deletePublishingLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deletePublishingLabel">Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this publishing request? This action cannot be undone, and all
                    songwriter entries on this request will be removed.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="delete-publishing-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete Publishing</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endpush
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script type="application/json" id="json-publishings">@json($publishings)</script>
    <script>
        function deletePublishing(event) {
            var url = $(event.target).data('url');
            $('#delete-publishing-form').attr('action', url);
            $('#confirm-delete-publishing-modal').modal('show');
        }

        function badge_publishing(status) {
            if (status == 'pending') {
                return `@include('components.badge-publishing', ['status' => 'pending'])`;
            } else if (status == 'review') {
                return `@include('components.badge-publishing', ['status' => 'review'])`;
            } else if (status == 'completed') {
                return `@include('components.badge-publishing', ['status' => 'completed'])`;
            } else if (status == 'rejected') {
                return `@include('components.badge-publishing', ['status' => 'rejected'])`;
            } else if (status == 'cancelled') {
                return `@include('components.badge-publishing', ['status' => 'cancelled'])`;
            } else if (status == 'draft') {
                return `@include('components.badge-publishing', ['status' => 'draft'])`;
            } else {
                return `<span class="badge bg-secondary">${status}</span>`;
            }
        }

        $(document).ready(function () {
            $('#requests-table').DataTable({
                data: JSON.parse(document.getElementById('json-publishings').textContent),
                columns: [
                    { data: 'title' },
                    { data: 'performer' },
                    {
                        data: 'status',
                        render: function (data, type, row) {
                            return badge_publishing(data);
                        }
                    },
                    {
                        data: 'created_at',
                        render: function (data, type, row) {
                            if (data) {
                                return new Date(data).toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' });
                            }

                            else {
                                return '-';
                            }
                        }
                    },
                    {
                        data: 'due_date',
                        render: function (data, type, row) {
                            if (data) {
                                return new Date(data).toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' });
                            }

                            else {
                                return '-';
                            }
                        }
                    },
                    {
                        data: 'id',
                        render: function (data, type, row) {
                            let url = '#';
                            let btn = 'View';
                            if (row.status == 'draft') {
                                url = '{{ route('publishing.edit', ':id') }}'.replace(':id', row.id);
                                btn = 'Edit';
                            }
                            else {
                                url = '{{ route('publishing.show', ':id') }}'.replace(':id', row.id);
                            }
                            let deleteUrl = '{{ route('publishing.destroy', ':id') }}'.replace(':id', row.id);
                            let actions = `<a href="${url}" class="btn btn-outline-primary btn-sm">${btn}</a>`;
                            if (row.status == 'draft' || row.status == 'rejected') {
                                actions += `<a href="javascript:void(0)" class="btn btn-outline-danger btn-sm ms-2" `
                                         + `data-url="${deleteUrl}" onclick="deletePublishing(event)">Delete</a>`;
                            }
                            return actions;
                        }
                    },
                ]
            });
        });
    </script>
@endpush