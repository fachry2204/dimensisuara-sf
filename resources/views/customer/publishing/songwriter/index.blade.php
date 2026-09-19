@extends('layouts.app')
@section('title')
Songwriter List
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <h4 class="mb-0 fs-2">Songwriter List</h4>
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('publishing.songwriters.create') }}" class="btn btn-primary btn-sm">Add Songwriter</a>
        </div>
    </div>
@endsection
@section('content')
    <p>You can use the songwriters in the table below for your publishing requests.</p>
    <div class="table-responsive">
        <table class="table table-striped" id="songwriters-table" style="width:100%">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th style="width: 20%">Action</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
@endsection
@push('modals')
    <!-- confirm deletion modal -->
    <div class="modal fade" id="confirm-delete-songwriter-modal" tabindex="-1" aria-labelledby="deleteSongwriterLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSongwriterLabel">Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this songwriter? Deleting songwriter will cancel all pending publishing request assigned to this songwriter.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="delete-songwriter-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete Songwriter</button>
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
    <script type="application/json" id="json-songwriters">@json($songwriters)</script>
    <script>
        function deleteSongwriter(event) {
            var url = $(event.target).data('url');
            $('#delete-songwriter-form').attr('action', url);
            $('#confirm-delete-songwriter-modal').modal('show');
        }

        $(document).ready(function() {
            $('#songwriters-table').DataTable({
                data: JSON.parse(document.getElementById('json-songwriters').textContent),
                columns: [
                    { data: 'full_name' },
                    { data: 'email' },
                    { data: 'phone' },
                    {
                        data: 'id' ,
                        render: function(data, type, row){
                            let url = '{{ route('publishing.songwriters.edit', ':id') }}'.replace(':id', row.id);
                            let deleteUrl = '{{ route('publishing.songwriters.destroy', ':id') }}'.replace(':id', row.id);
                            // return `<a href="${url}" class="btn btn-outline-primary btn-sm">Edit</a><a href="#" class="btn btn-outline-danger btn-sm ms-2" data-url="${deleteUrl}" onclick="deleteSongwriter(event)">Delete (temp)</a>`;
                            return `<a href="${url}" class="btn btn-outline-primary btn-sm">Edit</a>`;
                        }
                    },
                ]
            });
        });
    </script>
@endpush
