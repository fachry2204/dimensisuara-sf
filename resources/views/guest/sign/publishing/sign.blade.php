@extends('layouts.guest')
@section('title')
    Review Publishing Contract
@endsection
@section('content')
    <div class="container">
        <div class="card d-block my-5 col-xl-9 col-xxl-8 mx-auto rounded-4 overflow-hidden border-3 p-4">
            <div class="card-body">
                <div class="mb-4">
                    <a href="{{ url('/') }}">
                        <img src="{{ URL::asset('build/images/logo-soundfresh-full-white.webp') }}" class="mb-4" width="145"
                            alt="SoundFresh Logo">
                    </a>
                    <h4 class="fw-bold">Review Publishing Contract</h4>
                    <div>Requested by {{ $signee->publishing->requestor->full_name }} on {{
        \Carbon\Carbon::parse($signee->publishing->published_at)->format('j F Y') }}</div>
                    @if($signee->publishing->status == 'pending' && $signee->status == 'pending')
                        <div>Available until {{ \Carbon\Carbon::parse($signee->publishing->due_date)->format('j F Y') }}</div>
                    @endif
                    @include('components.badge-publishing', ['status' => $signee->publishing->status])
                </div>

                {{-- Errors and Success Messages --}}
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible">
                        <div class="d-flex align-items-center">
                            <div class="ms-3">
                                <h6 class="mb-0 text-danger">You have error(s) in your request!</h6>
                                <div>
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible">
                        <div class="d-flex align-items-center">
                            <div class="ms-3">
                                <h6 class="mb-0 text-success">{{ session('success') }}</h6>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- View only display: publishing is not pending, signing status is not pending, or due date has passed
                --}}
                @if($signee->publishing->status != 'pending' || $signee->status != 'pending' || $signee->publishing->due_date <= now()->subDay())

                    {{-- Display publishing status and detail --}}
                    @if($signee->publishing->status == 'rejected')
                        <div class="alert alert-danger" role="alert">
                            @if($signee->publishing->rejectorid)
                                <div>This publishing contract has been rejected by {{
                                $signee->publishing->rejector->songwriter->full_name }} ({{
                                $signee->publishing->rejector->songwriter->email }}).</div>
                            @else
                                <div>This publishing contract has been rejected.</div>
                            @endif
                            <div>Reason:</div>
                            <div class="mx-2"><i>{{ $signee->publishing->reason }}</i></div>
                        </div>
                    @elseif($signee->publishing->status == 'completed')
                        <div class="alert alert-success" role="alert">
                            <div>This publishing contract has been completed.</div>
                        </div>
                    @endif

                    {{-- Display publishing details --}}
                    @include('components.publishing-signee', ['signee' => $signee])

                    {{-- Review display, display stepper --}}
                @else
                    <div style="min-height: 580px">
                        <div class="mb-4">
                            <p class="mb-0">Hello {{ $signee->full_name }}!</p>
                            <p>You are invited by {{ $signee->publishing->requestor->full_name }} to sign a publishing
                                contract for the track with the following details:</p>
                        </div>
                        <div>
                            @include('components.publishing-signee', ['displayAgreement' => false, 'signee' => $signee])
                        </div>
                    </div>

                    <form action="{{ route('sign.publishing.approve', $signee->token) }}" method="POST"
                        id="inline-approval-form" enctype="multipart/form-data">
                        @csrf

                        {{-- Terms and Conditions Checkbox --}}
                        <div class="my-4 p-4 border rounded-4 bg-light-subtle shadow-sm">
                            <h6 class="fw-bold mb-3 d-flex align-items-center">
                                <i class="material-icons-outlined me-2 text-primary">gavel</i>Terms of Agreement
                            </h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="accept_terms" id="accept_terms" required>
                                <label class="form-check-label text-start" for="accept_terms">
                                    <i>In the event the Author/Composer terminates the collaboration agreement prior to the
                                        expiration of the agreement term, such termination shall become effective
                                        <strong>one (1) year</strong> after the submission of the termination request by the
                                        Author/Composer, provided that it has been approved by all parties (<strong>Post
                                            Term Period</strong> applies to this agreement)</i>. <span
                                        class="text-danger">*</span>
                                </label>
                            </div>
                        </div>

                        {{-- Signature Section --}}
                        <div class="my-4 p-4 border rounded-4 bg-light-subtle shadow-sm">
                            <h6 class="fw-bold mb-2 d-flex align-items-center">
                                <i class="material-icons-outlined me-2 text-primary">draw</i>Signature
                            </h6>
                            <p class="text-muted small mb-3">By signing, the signee acknowledges that all request data is
                                correct.</p>

                            <input type="hidden" name="signature" id="signature">
                            <input type="file" class="form-control" name="upload-signature" id="upload-signature"
                                accept=".jpg, .jpeg, .png, .pdf" style="display: none;">

                            <div class="d-flex flex-column align-items-center mb-3">
                                <div class="signature-container">
                                    <canvas id="sig-canvas" width="450" height="160">
                                        {{__('Your browser doesn\'t support canvas. Please update your browser.')}}
                                    </canvas>
                                </div>

                                <div class="d-flex justify-content-center gap-2 mt-3">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="sig-clearBtn">
                                        <i class="material-icons-outlined align-middle fs-6 me-1">clear</i>{{__('Clear
                                            Signature')}}
                                    </button>
                                    <label for="upload-signature" class="btn btn-outline-primary btn-sm mb-0"
                                        style="cursor: pointer">
                                        <i class="material-icons-outlined align-middle fs-6 me-1">file_upload</i>Upload
                                        Signature File
                                    </label>
                                </div>
                                <div id="file-name-display" class="text-success mt-2 small"
                                    style="display: none; font-weight: 500;"></div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-end gap-3 mt-4">
                            <button type="button" class="btn btn-outline-danger px-4" data-bs-toggle="modal"
                                data-bs-target="#confirm-rejection-modal">
                                Reject Contract
                            </button>
                            <button type="submit" class="btn btn-success px-5 py-2 fw-semibold" id="btn-approve-submit">
                                Approve & Sign Contract
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
    </div>
@endsection
@push('modals')
    {{-- Confirm Rejection Modal --}}
    <div class="modal fade" id="confirm-rejection-modal" tabindex="-1" aria-labelledby="confirm-rejection-modal-title"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="confirm-rejection-modal-title">Confirm Rejection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reject this publishing contract? Please provide a reason.</p>
                    <form action="{{ route('sign.publishing.reject', $signee->token) }}" method="POST"
                        id="confirm-rejection-form">
                        @csrf
                        <textarea class="form-control" name="reason" id="reason" cols="30" rows="3"
                            placeholder="Provide your reason for rejection" required></textarea>
                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" form="confirm-rejection-form">Reject Contract</button>
                </div>
            </div>
        </div>
    </div>
@endpush
@push('css')
    <link href="{{ URL::asset('build/plugins/notifications/css/lobibox.css') }}" rel="stylesheet">
    <style>
        .document {
            width: 200px;
            height: 150px;
            margin: 5px;
            max-height: 100%;
        }

        div.document {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        img.document {
            object-fit: cover;
        }

        .signature-container {
            position: relative;
            background: #ffffff;
            border: 2px dashed var(--bs-border-color);
            border-radius: 12px;
            padding: 8px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.04);
            display: inline-block;
            max-width: 100%;
            transition: border-color 0.25s ease;
        }

        .signature-container:hover {
            border-color: var(--bs-primary);
        }

        #sig-canvas {
            cursor: crosshair;
            display: block;
            background-color: #FFFFFF;
            border-radius: 8px;
            max-width: 100%;
        }
    </style>
@endpush
@push('script')
    <script src="{{ URL::asset('build/plugins/notifications/js/lobibox.js') }}"></script>
    <script src="{{ URL::asset('build/js/canvas.js') }}" type="text/javascript"></script>
    <script>
        $(document).ready(function () {
            // Check if file is uploaded or drawn
            $('#upload-signature').on('change', function () {
                let file = this.files[0];
                if (file) {
                    $('#file-name-display').text('Uploaded file: ' + file.name).show();

                    // Preview image if applicable
                    if (file.type.match('image.*')) {
                        let reader = new FileReader();
                        reader.onload = function (e) {
                            let image = new Image();
                            image.src = e.target.result;
                            image.onload = function () {
                                let canvas = document.getElementById('sig-canvas');
                                let ctx = canvas.getContext('2d');
                                canvas.width = canvas.width; // Clear/reset canvas
                                ctx.strokeStyle = "black";
                                ctx.lineWidth = 2;

                                let widthRatio = canvas.width / image.width;
                                let heightRatio = canvas.height / image.height;
                                let ratio = Math.min(widthRatio, heightRatio);
                                let centerShiftX = (canvas.width - image.width * ratio) / 2;
                                let centerShiftY = (canvas.height - image.height * ratio) / 2;
                                ctx.drawImage(image, 0, 0, image.width, image.height, centerShiftX, centerShiftY, image.width * ratio, image.height * ratio);
                            }
                        }
                        reader.readAsDataURL(file);
                    } else {
                        // If PDF, clear canvas and display placeholder text
                        let canvas = document.getElementById('sig-canvas');
                        let ctx = canvas.getContext('2d');
                        canvas.width = canvas.width;
                        ctx.fillStyle = "#888888";
                        ctx.font = "14px sans-serif";
                        ctx.textAlign = "center";
                        ctx.fillText("PDF Document Uploaded (" + file.name + ")", canvas.width / 2, canvas.height / 2);
                    }
                } else {
                    $('#file-name-display').hide();
                }
            });

            // Clear button functionality
            $('#sig-clearBtn').on('click', function () {
                $('#upload-signature').val('');
                $('#file-name-display').hide();
            });

            // Form Submit and Validation
            $('#inline-approval-form').on('submit', function (e) {
                e.preventDefault();

                const canvas = document.getElementById('sig-canvas');
                const signatureData = canvas.toDataURL('image/png');
                $('input[name="signature"]').val(signatureData);

                // Helper to check if canvas is blank
                const blank = document.createElement('canvas');
                blank.width = canvas.width;
                blank.height = canvas.height;
                const isCanvasEmpty = signatureData === blank.toDataURL('image/png');

                const hasUpload = document.getElementById('upload-signature').files.length > 0;

                if (isCanvasEmpty && !hasUpload) {
                    Lobibox.notify('error', {
                        pauseDelayOnHover: true,
                        size: 'mini',
                        rounded: true,
                        position: 'top right',
                        icon: 'bi bi-exclamation-triangle',
                        msg: 'Please draw a signature or upload a signature file to approve the contract.'
                    });
                    return false;
                }

                if (!document.getElementById('accept_terms').checked) {
                    Lobibox.notify('error', {
                        pauseDelayOnHover: true,
                        size: 'mini',
                        rounded: true,
                        position: 'top right',
                        icon: 'bi bi-exclamation-triangle',
                        msg: 'You must accept the terms of the agreement.'
                    });
                    return false;
                }

                this.submit();
            });
        });
    </script>
@endpush