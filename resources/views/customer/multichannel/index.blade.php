@extends('layouts.app')
@section('title')
Multi-Channel Network (MCN)
@endsection
@section('content')
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <h4 class="mb-0 fs-2">Multi-Channel Network (MCN)</h4>
        <a href="{{ route('mcn.create') }}" class="btn btn-primary btn-sm">Add MCN Request</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped" id="mcn-table" style="width:100%">
            <thead>
                <tr>
                    <th>Channel Name</th>
                    <th>Channel Link</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
@endpush
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#mcn-table').DataTable();
        });
    </script>
@endpush