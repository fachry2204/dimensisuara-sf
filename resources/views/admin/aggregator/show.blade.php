@extends('layouts.app')
@section('title')
    View Release
@endsection
@section('header')
    <div class="admin-release-header d-flex flex-row align-items-center justify-content-between">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.releases.index') }}">All Releases</a></li>
                    <li class="breadcrumb-item active" aria-current="page"></li>
                </ol>
            </nav>
            <h4 class="mb-0 font-weight-bold fs-2">View Release</h4>
        </div>

        <div class="d-flex flex-row gap-2">
            @can('edit-releases')
                <a href="{{ route('admin.releases.edit', $release->id) }}" class="btn admin-release-action admin-release-action--edit"><i class="material-icons-outlined">edit</i> Edit Release</a>
            @endcan
            @can('manage-releases-status')

                {{-- For wide viewport --}}
                @if(in_array($release->status, ['pending', 'revision', 'review', 'uploading']))
                    <button type="button" class="d-none d-md-block btn admin-release-action admin-release-action--reject" data-bs-toggle="modal" data-bs-target="#reject-modal"><i class="material-icons-outlined">block</i> Reject Release</button>
                @endif

                @if($release->status == 'revision')
                    <button type="button" class="d-none d-md-block btn admin-release-action admin-release-action--review" data-bs-toggle="modal" data-bs-target="#accept-modal"><i class="material-icons-outlined">check</i> Accept Revision</button>
                @elseif($release->status == 'pending')
                    <button type="button" class="d-none d-md-block btn admin-release-action admin-release-action--review" data-bs-toggle="modal" data-bs-target="#review-modal"><i class="material-icons-outlined">check</i> Review Release</button>
                @elseif($release->status == 'review')
                    <button type="button" class="d-none d-md-block btn admin-release-action admin-release-action--review" data-bs-toggle="modal" data-bs-target="#uploading-modal"><i class="material-icons-outlined">cloud_upload</i> Upload Release</button>
                @elseif($release->status == 'uploading')
                    <button type="button" class="d-none d-md-block btn admin-release-action admin-release-action--review" data-bs-toggle="modal" data-bs-target="#verify-modal"><i class="material-icons-outlined">verified</i> Verify Release</button>
                @endif

                {{-- For narrow viewport --}}
                @if(in_array($release->status, ['pending', 'revision', 'review', 'uploading']))
                <div class="d-md-none btn-group ms-auto">
                    @if($release->status == 'revision')
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#accept-modal">Accept Revision</button>
                    @elseif($release->status == 'pending')
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#review-modal">Review Release</button>
                    @elseif($release->status == 'review')
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#uploading-modal">Upload Release</button>
                    @elseif($release->status == 'uploading')
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#verify-modal">Verify Release</button>
                    @endif

                    <button type="button" class="btn btn-sm btn-outline-success split-bg-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg-end" style="">
                        <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#reject-modal">Reject Release</button>
                    </div>
                </div>
                @endif
            @endcan
        </div>
    </div>
    <div class="mb-5">
        <span style="vertical-align: middle">by {{$release->profile->firstname}}
            {{$release->profile->lastname}}</span>@if($release->aggregator) <span
            class="badge bg-primary">{{ $release->aggregator }}</span>@endif
    </div>
@endsection
@push('css')
    <style>
        .admin-release-header .breadcrumb-item a{color:#78b6ff}.admin-release-header .breadcrumb-item.active{color:#b7cce2}.admin-release-header h4{color:#f6f9ff;letter-spacing:-.035em}.admin-release-header>div:last-child{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:9px}.admin-release-action{display:inline-flex;align-items:center;gap:7px;min-height:40px;padding:8px 13px;border:1px solid;border-radius:8px;font-size:.78rem;font-weight:800}.admin-release-action i{font-size:18px}.admin-release-action--edit{border-color:#f18b3a;background:rgba(230,129,39,.1);color:#ffac61}.admin-release-action--reject{border-color:#e53f84;background:#d91f70;color:#fff}.admin-release-action--review{border-color:#28c987;background:#16b978;color:#fff}.admin-release-action:hover{filter:brightness(1.1);color:#fff;transform:translateY(-1px)}
        /* Bootstrap reads these variables for hover, keyboard focus, and pressed states. */
        .admin-release-action {
            --bs-btn-hover-color: #fff;
            --bs-btn-active-color: #fff;
        }
        .admin-release-action--edit {
            --bs-btn-hover-bg: #c2410c;
            --bs-btn-hover-border-color: #c2410c;
            --bs-btn-active-bg: #9a3412;
            --bs-btn-active-border-color: #9a3412;
            --bs-btn-focus-shadow-rgb: 194, 65, 12;
        }
        .admin-release-action--reject {
            --bs-btn-hover-bg: #b91c5c;
            --bs-btn-hover-border-color: #b91c5c;
            --bs-btn-active-bg: #9d174d;
            --bs-btn-active-border-color: #9d174d;
            --bs-btn-focus-shadow-rgb: 185, 28, 92;
        }
        .admin-release-action--review {
            --bs-btn-hover-bg: #087f5b;
            --bs-btn-hover-border-color: #087f5b;
            --bs-btn-active-bg: #066649;
            --bs-btn-active-border-color: #066649;
            --bs-btn-focus-shadow-rgb: 8, 127, 91;
        }
    </style>
@endpush
@section('content')

    {{-- Release component --}}
    @include('components.release', ['type' => 'admin', 'canDownload' => auth()->user()->can('download-release-files')])

    {{-- Offcanvas display button --}}
    {{-- <button class="btn btn-grd btn-grd-primary position-fixed bottom-0 end-0 m-3 d-flex align-items-center gap-2"
        type="button" data-bs-toggle="offcanvas" data-bs-target="#staticBackdrop">
        <i class="material-icons-outlined">help</i>Help
    </button> --}}


@endsection
@push('modals')
    @if(in_array($release->status, ['pending']))
        <div class="modal fade" id="review-modal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-bottom-0 py-2">
                        <h5 class="modal-title">Review Release</h5>
                        <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                            <i class="material-icons-outlined">close</i>
                        </a>
                    </div>
                    <div class="modal-body">
                        <form id="review-form" action="{{ route('admin.releases.review', $release->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <p>Please select an aggregator to review this release:</p>
                            <select class="form-select" name="aggregator" id="aggregator">
                                @include('admin.aggregator.partials.aggregator-options', [
                                    'selectedAggregator' => old('aggregator', $release->aggregator),
                                    'legacyAggregator' => null,
                                ])
                            </select>
                        </form>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-success" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" form="review-form">Review Release</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(in_array($release->status, ['pending', 'revision', 'review', 'uploading']))
        <div class="modal fade" id="reject-modal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-bottom-0 py-2">
                        <h5 class="modal-title">Reject Release</h5>
                        <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                            <i class="material-icons-outlined">close</i>
                        </a>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.releases.reject', $release->id) }}" method="POST" id="reject-form">
                            @csrf
                            @method('PUT')
                            <p>Are you sure you want to reject this release? Rejecting release let the user submit revision
                                within 5 days.</p>
                            <textarea class="form-control" name="reason" id="" cols="30" rows="3"
                                placeholder="Provide your message to the user" required></textarea>
                        </form>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" form="reject-form">Reject Release</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($release->status == 'revision')
        <div class="modal fade" id="accept-modal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-bottom-0 py-2">
                        <h5 class="modal-title">Accept Revision</h5>
                        <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                            <i class="material-icons-outlined">close</i>
                        </a>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.releases.accept', $release->id) }}" method="POST" id="accept-form">
                            @csrf
                            @method('PUT')
                            <p>Accept revision and set status to @include('components.badge-release', ['status' => $release->previous_rejected_status ?? 'pending'])?</p>
                        </form>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-success" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" form="accept-form">Accept Revision</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($release->status == 'review')
        <div class="modal fade" id="uploading-modal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-bottom-0 py-2">
                        <h5 class="modal-title">Publish Release</h5>
                        <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                            <i class="material-icons-outlined">close</i>
                        </a>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.releases.uploading', $release->id) }}" method="POST" id="uploading-form">
                            @csrf
                            @method('PUT')
                            <p>Set release status to uploading?</p>
                        </form>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-success" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" form="uploading-form">Upload Release</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($release->status == 'uploading')
        <div class="modal fade" id="verify-modal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-bottom-0 py-2">
                        <h5 class="modal-title">Publish Release</h5>
                        <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                            <i class="material-icons-outlined">close</i>
                        </a>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.releases.verify', $release->id) }}" method="POST" id="verify-form">
                            @csrf
                            @method('PUT')
                            <p>Before you can verify this release, please make sure that the release UPC and tracks ISRCs are
                                correct.</p>
                            <div class="form-group mb-3">
                                <label for="upc" class="form-label">UPC</label>
                                <input type="text" name="upc" id="upc" class="form-control" value="{{ $release->upc }}"
                                    required>
                            </div>
                            @foreach($release->tracks as $index => $track)
                                <div class="form-group mb-3">
                                    <label for="isrc-{{ $track->id }}" class="form-label">Track {{ $index + 1 }} ISRC -
                                        {{ $track->title }}</label>
                                    <input type="text" name="isrc[{{ $track->id }}]" id="isrc-{{ $track->id }}" class="form-control"
                                        value="{{ $track->isrc }}" required>
                                </div>
                            @endforeach
                        </form>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-success" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" form="verify-form">Verify Release</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endpush
@section('offcanvas-title')
    <h5 class="mb-0">Release Help</h5>
@endsection

@section('offcanvas-content')
    <p>In this page, you can view the details of a release, such as the title, artist name, and track list. You can also
        edit and/or change the status of the release.</p>
    <p>Below you can see the status of the release and the description of each status.</p>
    <table class="table table-bordered">
        <thead>
            <th>Status</th>
            <th>Description</th>
        </thead>
        <tbody>
            <tr>
                <td>@include('components.badge-release', ['status' => 'draft'])</td>
                <td>
                    <p>The release is still in draft.</p>
                </td>
            </tr>
            <tr>
                <td>@include('components.badge-release', ['status' => 'pending'])</td>
                <td>
                    <p>Pending verification from the admin.</p>
                </td>
            </tr>
            <tr>
                <td>@include('components.badge-release', ['status' => 'review'])</td>
                <td>
                    <p>The release is being reviewed by the admin.</p>
                </td>
            </tr>
            <tr>
                <td>@include('components.badge-release', ['status' => 'released'])</td>
                <td>
                    <p>The release has been released to the public.</p>
                    <p>Admin have to input UPC and individual ISRCs for each track.</p>
                    <p>When the release is published, the user can no longer edit the release.</p>
                </td>
            </tr>
            <tr>
                <td>@include('components.badge-release', ['status' => 'rejected'])</td>
                <td>
                    <p>The release has been rejected by the admin. The user has 5 days to edit and resubmit the release.</p>
                    <p>Resubmitted release will have the status of pending, and the process will repeat until the release is
                        approved and published.</p>
                </td>
            </tr>
        </tbody>
    </table>
@endsection
