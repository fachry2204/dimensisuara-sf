@extends('layouts.app')
@section('title')
View Publishing Request
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.publishing.index') }}">All Publishing</a></li>
                    <li class="breadcrumb-item active" aria-current="page"></li>
                </ol>
            </nav>
            <h4 class="mb-0 font-weight-bold fs-2">View Publishing Request</h4>
        </div>

        <div class="d-flex flex-row gap-2">
            
            {{-- @can('edit-releases')
            <a href="{{ route('admin.publishing.edit', $publishing->id) }}" class="btn btn-sm btn-outline-primary my-1">Edit Publishing</a>
            @endcan --}}
            @can('manage-releases-status')
                @if($publishing->status == 'review')
                    <div class="btn-group ms-auto">
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#verify-modal">Verify Publishing</button>
                        <button type="button" class="btn btn-sm btn-outline-success split-bg-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg-end" style="">
                            <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#reject-modal">Reject Publishing</button>
                            {{-- <a href="{{ route('admin.publishing.edit', $publishing->id) }}" class="dropdown-item">Edit</a> --}}
                        </div>
                    </div>
                @endif
            @endcan
        </div>
    </div>
    <div class="mb-3">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <span class="text-muted">by {{ $publishing->requestor->full_name }}</span>

            @include('components.badge-publishing', ['status' => $publishing->status])

            @if($publishing->status == 'pending')
                <span class="badge bg-grd-royal">Due by {{ \Carbon\Carbon::parse($publishing->due_date)->format('j F Y') }}</span>
            @elseif($publishing->finished_at)
                <span class="badge bg-grd-royal">Finished on {{ \Carbon\Carbon::parse($publishing->finished_at)->format('j F Y') }}</span>
            @endif
        </div>
        <div class="text-muted small mt-2">
            <span class="material-icons-outlined align-middle" style="font-size: 14px;">schedule</span>
            Created {{ \Carbon\Carbon::parse($publishing->created_at)->diffForHumans() }} ({{ \Carbon\Carbon::parse($publishing->created_at)->format('j F Y, H:i') }})
        </div>
    </div>
@endsection
@section('content')
    @if($publishing->status == 'rejected')
        <div class="alert alert-danger" role="alert">
            @if($publishing->rejectorid)
                <div>This publishing contract has been rejected by {{ $publishing->rejector->full_name }} ({{ $publishing->rejector->email }}).</div>
            @else
                <div>This publishing contract has been rejected.</div>
            @endif
            <div>Reason:</div>
            <div class="mx-2"><i>{{ $publishing->reason }}</i></div>
        </div>
    @endif

    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="pill" href="#tabs-request" role="tab" aria-selected="true">
                <div class="d-flex align-items-center">
                    <div class="tab-icon"><i class="material-icons-outlined material-symbols-outlined me-1">confirmation_number</i>
                    </div>
                    <div class="tab-title">Request</div>
                </div>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="pill" href="#tabs-contracts" role="tab" aria-selected="true">
                <div class="d-flex align-items-center">
                    <div class="tab-icon"><i class="material-icons-outlined material-symbols-outlined me-1">handshake</i>
                    </div>
                    <div class="tab-title">Contract & Statement</div>
                </div>
            </a>
        </li>
    </ul>

    <div class="tab-content" id="tab-content">
        <div class="tab-pane fade active show" id="tabs-request" role="tabpanel">
            <div class="card rounded-4">
                <div class="card-body p-4">
                    @include('components.publishing', [
                            'publishing' => $publishing,
                            'allowEdit' => true,
                            'allowGenerateContract' => true,
                            'resendRoute' => 'admin.publishing.signee.resend',
                            'contractRoute' => 'admin.publishing.contract.view',
                            'isDetailed' => true
                        ])
                    <div class="mt-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="accept_terms" id="accept_terms" @if($publishing->accept_terms) checked @endif disabled>
                            <label class="form-check-label" for="accept_terms">
                                <i>In the event the Author/Composer terminates the collaboration agreement prior to the expiration of the agreement term, such termination shall become effective <strong>one (1) year</strong> after the submission of the termination request by the Author/Composer, provided that it has been approved by all parties (<strong>Post Term Period</strong> applies to this agreement)</i>.
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="tabs-contracts" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Documents</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-details-2">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Doc No.</th>
                                    <th>PDF</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- 1. Publishing Contract (PKS Contract) -->
                                <tr>
                                    <td>Publishing Contract</td>
                                    <td>
                                        @if($publishing->publishingContract)
                                            {{ $publishing->publishingContract->docnum }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($publishing->publishingContract)
                                            <div class="mx-1" style="display: inline-block; vertical-align:middle">
                                                <a href="{{ route('admin.publishing.contract.view', ['id' => $publishing->publishingContract->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary my-1">Generate PDF</a>
                                                @if($publishing->publishingContract->file)
                                                    <a href="{{ route('admin.publishing.contract.view_uploaded', ['id' => $publishing->publishingContract->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary my-1">View Uploaded PDF</a>
                                                @endif
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($publishing->publishingContract)
                                            <div class="mx-1" style="display: inline-block; vertical-align:middle">
                                                <div>
                                                    <form action="{{ route('admin.publishing.contract.upload', ['id' => $publishing->publishingContract->id]) }}" method="POST" enctype="multipart/form-data">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="file" name="file" class="upload-file d-none" accept="application/pdf">
                                                        <a href="javascript:void(0)" class="upload-btn text-primary">Upload PDF</a>
                                                    </form>
                                                </div>
                                                @if($publishing->publishingContract->file)
                                                    <div>
                                                        <form action="{{ route('admin.publishing.contract.delete_pdf', ['id' => $publishing->publishingContract->id]) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <a href="javascript:void(0)" class="delete-btn text-danger">Delete PDF</a>
                                                        </form>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>

                                <!-- 2. Songwriter Statement -->
                                <tr>
                                    <td>Songwriter Statement</td>
                                    <td>
                                        @if($publishing->statementContract)
                                            {{ $publishing->statementContract->docnum }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <div class="mx-1" style="display: inline-block; vertical-align:middle">
                                            <a href="{{ route('admin.publishing.view_statement', ['id' => $publishing->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary my-1">Generate PDF</a>
                                            @if($publishing->statement)
                                                <a href="{{ route('admin.publishing.view_statement_pdf', ['id' => $publishing->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary my-1">View Uploaded PDF</a>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="mx-1" style="display: inline-block; vertical-align:middle">
                                            <div>
                                                <form action="{{ route('admin.publishing.upload', ['id' => $publishing->id, 'type' => 'statement']) }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="file" name="file" class="upload-file d-none" accept="application/pdf">
                                                    <a href="javascript:void(0)" class="upload-btn text-primary">Upload PDF</a>
                                                </form>
                                            </div>
                                            @if($publishing->statement)
                                                <div>
                                                    <form action="{{ route('admin.publishing.delete_pdf', ['id' => $publishing->id, 'type' => 'statement']) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <a href="javascript:void(0)" class="delete-btn text-danger">Delete PDF</a>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                <!-- 3. NUON Statement -->
                                <tr>
                                    <td>NUON Statement</td>
                                    <td>-</td>
                                    <td>
                                        <div class="mx-1" style="display: inline-block; vertical-align:middle">
                                            <a href="{{ route('admin.publishing.view_nuon', ['id' => $publishing->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary my-1">Generate PDF</a>
                                            @if($publishing->nuon)
                                                <a href="{{ route('admin.publishing.view_nuon_pdf', ['id' => $publishing->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary my-1">View Uploaded PDF</a>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="mx-1" style="display: inline-block; vertical-align:middle">
                                            <div>
                                                <form action="{{ route('admin.publishing.upload', ['id' => $publishing->id, 'type' => 'nuon']) }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="file" name="file" class="upload-file d-none" accept="application/pdf">
                                                    <a href="javascript:void(0)" class="upload-btn text-primary">Upload PDF</a>
                                                </form>
                                            </div>
                                            @if($publishing->nuon)
                                                <div>
                                                    <form action="{{ route('admin.publishing.delete_pdf', ['id' => $publishing->id, 'type' => 'nuon']) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <a href="javascript:void(0)" class="delete-btn text-danger">Delete PDF</a>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('modals')
    @if($publishing->status == 'review')
        <div class="modal fade" id="reject-modal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-bottom-0 py-2">
                        <h5 class="modal-title">Reject Publishing</h5>
                        <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                            <i class="material-icons-outlined">close</i>
                        </a>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.publishing.reject', $publishing->id) }}" method="POST" id="reject-form">
                            @csrf
                            @method('PUT')
                            <p>Are you sure you want to reject this publishing?</p>
                            <textarea class="form-control" name="reason" id="" cols="30" rows="3" placeholder="Provide your message to the user" required></textarea>
                        </form>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" form="reject-form">Reject Publishing</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="verify-modal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-bottom-0 py-2">
                        <h5 class="modal-title">Verify Publishing</h5>
                        <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                            <i class="material-icons-outlined">close</i>
                        </a>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.publishing.approve', $publishing->id) }}" method="POST" id="verify-form">
                            @csrf
                            @method('PUT')
                            <p>You are about to verify this publishing. Please provide the contract details below:</p>

                            <div class="card">
                                <div class="card-body">
                                    <div class="mb-3 fw-bold">Publishing Contract Number</div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <input type="number" name="contract_number" class="form-control form-control-sm" placeholder="Number" required>
                                        </div>
                                        <div class="col-md-5 mb-3">
                                            <select name="contract_month" class="form-control form-control-sm" required>
                                                <option value="" selected disabled>Month</option>
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
                                            <input type="number" name="contract_year" class="form-control form-control-sm" placeholder="Year" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-body">
                                    <div class="mb-3 fw-bold">Statement Document Number</div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <input type="number" name="statement_number" class="form-control form-control-sm" placeholder="Number" required>
                                        </div>
                                        <div class="col-md-5 mb-3">
                                            <select name="statement_month" class="form-control form-control-sm" required>
                                                <option value="" selected disabled>Month</option>
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
                                            <input type="number" name="statement_year" class="form-control form-control-sm" placeholder="Year" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" form="verify-form">Verify Publishing</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endpush
@push('css')
@endpush
@push('script')
    <script>
        $(document).ready(function() {
            $('.upload-btn').on('click', function(e) {
                e.preventDefault();
                var form = $(this).closest('form');
                var uploadFile = form.find('.upload-file');
                uploadFile.click();
            });

            $('.upload-file').on('change', function(e) {
                e.preventDefault();
                var form = $(this).closest('form');
                form.submit();
            });

            $('.delete-btn').on('click', function(e) {
                e.preventDefault();
                if(confirm('Are you sure you want to delete the uploaded PDF of this contract?')) {
                    var form = $(this).closest('form');
                    form.submit();
                }
            });

            // Copy to clipboard functionality
            $('.copy-btn').on('click', function(e) {
                e.preventDefault();
                var textToCopy = $(this).data('copy');

                // Create temporary input
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(textToCopy).select();

                try {
                    document.execCommand('copy');

                    // Show success feedback
                    var $icon = $(this).find('.material-icons-outlined');
                    var originalIcon = $icon.text();
                    $icon.text('check');
                    $(this).css('color', '#28a745');

                    // Reset after 2 seconds
                    setTimeout(() => {
                        $icon.text(originalIcon);
                        $(this).css('color', '');
                    }, 2000);
                } catch (err) {
                    console.error('Failed to copy text: ', err);
                }

                $temp.remove();
            });

            // Lightbox for document thumbnails
            $('.doc-thumbnail').on('click', function(e) {
                e.preventDefault();
                var imageSrc = $(this).attr('src');
                var imageAlt = $(this).attr('alt');

                // Create lightbox
                var lightbox = `
                    <div class="image-lightbox" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; cursor: zoom-out;">
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); max-width: 90%; max-height: 90%;">
                            <img src="${imageSrc}" alt="${imageAlt}" style="max-width: 100%; max-height: 90vh; display: block;">
                        </div>
                        <button style="position: absolute; top: 20px; right: 20px; background: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                            <span class="material-icons-outlined">close</span>
                        </button>
                    </div>
                `;

                var $lightbox = $(lightbox);
                $('body').append($lightbox);
                $lightbox.fadeIn(200);

                // Close on click
                $lightbox.on('click', function() {
                    $(this).fadeOut(200, function() {
                        $(this).remove();
                    });
                });
            });
        });
    </script>
@endpush
