@extends('layouts.app')
@section('title')
Notification
@endsection
@section('content')
    {{-- Breadcrumbs --}}
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <h4 class="mb-0 fs-2">Notification</h4>
    </div>

    <div class="table-responsive">
        <table class="table" id="notification-table">
            <thead>
                <th>Subject</th>
                <th>Message</th>
                <th>Date</th>
            </thead>
            <tbody>
                @foreach (current_user()->notifications as $notification)
                    <tr>
                        <td>{{ $notification->data['subject'] }}</td>
                        <td>{{ $notification->data['message'] }}</td>
                        <td>{{ $notification->created_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
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
            $('#notification-table').DataTable()
        });
    </script>
@endpush