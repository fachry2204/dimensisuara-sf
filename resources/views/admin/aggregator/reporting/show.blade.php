@extends('layouts.app')
@section('title')
    @if($report->status == 'pending_review')
        Review Imported Report
    @else
        View Report
    @endif
@endsection
@section('header')
    {{-- Breadcrumbs --}}
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.releases.reporting.index') }}">Aggregator Reporting</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        @if($report->status == 'pending_review')
                            Review Imported Report
                        @else
                            View Report
                        @endif
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="mb-5">
        <h4 class="mb-0 font-weight-bold fs-2">{{ $report->original_filename }}</h4>
        <div>
            @component('components.badge-reporting', ['status' => $report->status])
            @endcomponent
        </div>
    </div>
@endsection
@section('content')
    @include('admin.aggregator.reporting.partials.process-import')
    @include('admin.aggregator.reporting.partials.calculate-manual')
    @if ($report->status == 'completed')
        <div class="alert alert-warning fade show">
            <div class="d-flex align-items-center">
                <div class="ms-3">
                    <h6 class="mb-0 text-warning">Warning!</h6>
                    <div>The revenue uploaded here might be different from the calculation in the excel sheet due to rounding. Please check it manually.</div>
                </div>
            </div>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-12 col-sm-6 col-lg-3 d-flex align-items-stretch">
            <div class="card rounded-4 flex-grow-1">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="wh-48 d-flex bg-warning text-warning bg-opacity-10 align-items-center justify-content-center rounded-circle">
                            <span class="material-icons-outlined">euro_symbol</span>
                        </div>
                        <div>
                            @if ($report->status == 'completed')
                                <h4 class="mb-0" style="height: 30px;">{{ number_format( $report->idr_rate , 0 , ',' , '.' ) }}</h4>
                                <div class="d-flex flex-row align-items-center justify-content-between">
                                    <p class="mb-0">EUR to IDR</p>
                                </div>
                            @else
                                @can('upload-reporting')
                                    <form action="{{ route('admin.releases.reporting.rate', $report->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <input class="form-control mb-2" value="{{ old('idr_rate', $report->idr_rate) }}" name="idr_rate" id="idr_rate" style="height: 30px; font-weight: bold; font-size: 1.2rem; width: 150px">
                                        <div class="d-flex flex-row align-items-center justify-content-between">
                                            <p class="mb-0">EUR to IDR</p>
                                            <button type="submit" class="badge text-dark border-0">Set Rate</button>
                                        </div>
                                    </form>
                                @else
                                    <h4 class="mb-0" style="height: 30px;">{{ number_format( $report->idr_rate , 0 , ',' , '.' ) }}</h4>
                                    <div class="d-flex flex-row align-items-center justify-content-between">
                                        <p class="mb-0">EUR to IDR</p>
                                    </div>
                                @endcan
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3 d-flex align-items-stretch">
            <div class="card rounded-4 flex-grow-1">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="wh-48 d-flex bg-success text-success bg-opacity-10 align-items-center justify-content-center rounded-circle">
                            <span class="material-icons-outlined">payments</span>
                        </div>
                        <div>
                            @if ($report->status == 'completed')
                                <h4 class="mb-0" style="height: 30px;">{{ number_format( $total_revenue , 0 , ',' , '.' ) }}</h4>
                            @else
                                <h4 class="mb-0" style="height: 30px; font-size: 1.2rem">Not Calculated</h4>
                            @endif
                            <p class="mb-0">Total Revenue</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3 d-flex align-items-stretch">
            <div class="card rounded-4 flex-grow-1">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="wh-48 d-flex bg-info text-info bg-opacity-10 align-items-center justify-content-center rounded-circle">
                            <span class="material-icons-outlined">play_circle</span>
                        </div>
                        <div>
                            <h4 class="mb-0" style="height: 30px;" id="sum-earnings">{{ number_format( $total_views , 0 , ',' , '.' ) }}</h4>
                            <p class="mb-0">Total Views</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($report->status == 'pending_review')
        <p>Make sure the ISRC in the report match the registered track's ISRC in this system to map the revenue to the respective artist.</p>
    @endif

    <div class="table-responsive">
        <table class="table table-sm table-striped">
            <thead>
                <th>Artist</th>
                <th>Title</th>
                <th>ISRC</th>
                <th>Release</th>
                <th>UPC</th>
                @if ($report->status == 'completed')
                    <th>Revenue</th>
                @endif
                <th>Status</th>
            </thead>
            <tbody>
                @foreach ($reps as $rep)
                    <tr>
                        <td style="white-space: nowrap">
                            @if($rep->profileid)
                                <a href="{{ route('admin.users.show', $rep->profile->user->id) }}" target="_blank">{{ $rep->artist_name }}</a>
                            @else
                                {{ $rep->artist_name }}
                            @endif
                        </td>
                        <td>
                            @if ($rep->trackid)
                                <a href="{{ route('admin.releases.track', $rep->trackid) }}" target="_blank">{{ $rep->track_title }}</a>
                            @else
                                {{ $rep->track_title }}
                            @endif
                        </td>
                        <td style="white-space: nowrap">{{ $rep->isrc }}</td>
                        <td>{{ $rep->release_title }}</td>
                        <td style="white-space: nowrap">{{ $rep->upc }}</td>
                        @if ($report->status == 'completed')
                            <td>{{ number_format( $rep->rupiah_exact , 0 , ',' , '.' ) }}</td>
                        @endif
                        <td style="white-space: nowrap">
                            @if($rep->status == 'incomplete')
                                <span class="text-danger">No account</span>
                            @elseif ($rep->status == 'finalized')
                                <span class="text-success">Finalized</span>
                            @else
                                <span class="text-warning">{{  ucfirst($rep->status) }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            @if ($report->status == 'completed')
                <tfoot>
                    <tr>
                        <td colspan="5" class="fw-bold text-end">Total Revenue</td>
                        <td class="fw-bold">{{ number_format( $reps->sum('rupiah_exact') , 0 , ',' , '.' ) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    
    @can('upload-reporting')
        @if (in_array($report->status, ['pending_review', 'pending_rate']))
            <form id="mapping-form" action="{{route('admin.releases.reporting.update', $report->id)}}" method="POST">
                @csrf
                @method('PUT')
                <div class="d-flex flex-row align-items-center justify-content-center">
                    <button type="submit" form="mapping-form" class="btn btn-primary">Finalize Report</button>
                </div>
            </form>
        @endif
    @endcan

@endsection
@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        button.badge:hover {
            cursor: pointer;
            background-color: #FF6A00 !important;
        }
    </style>
@endpush
@push('script')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.release-select').select2({
                width: '100%',
                ajax: {
                    url: "{{ route('admin.releases.reporting.get_release') }}",
                    dataType: 'json',
                }
            });
            $('.track-select').select2({
                width: '100%',
                ajax: {
                    url: "{{ route('admin.releases.reporting.get_track') }}",
                    dataType: 'json',
                }
            });
            $('.profile-select').select2({
                width: '100%',
                ajax: {
                    url: "{{ route('admin.releases.reporting.get_profile') }}",
                    dataType: 'json',
                }
            });
            // $('#idr_rate').on('keyup', function() {
            //     var idr_rate = $(this).val();
            //     var idr_rate_formatted = idr_rate.toString().replace(/\./g, '').replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            //      $(this).val(idr_rate_formatted);
            // });
        });
    </script>
@endpush
