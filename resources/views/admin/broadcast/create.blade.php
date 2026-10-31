@extends('layouts.app')
@section('title')
Create Broadcast Message
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.broadcast.index') }}">Broadcast Messages</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <h4 class="mb-0 fs-2">Create Broadcast Message</h4>
    </div>
@endsection
@section('content')
<form action="{{ route('admin.broadcast.store') }}" method="POST" id="broadcast-form">
    @csrf

    <div class="row mb-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Message Details</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('subject') is-invalid @enderror"
                               id="subject" name="subject" value="{{ old('subject') }}" required>
                        @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('message') is-invalid @enderror"
                                  id="message" name="message" rows="8" required>{{ old('message') }}</textarea>
                        <div class="form-text">Maximum 5000 characters</div>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Preview</h6>
                </div>
                <div class="card-body">
                    <div id="preview-container" class="alert alert-info" style="display: none;">
                        <h6 id="preview-subject" class="fw-bold"></h6>
                        <p id="preview-message" class="mb-2"></p>
                        <hr>
                        <small class="text-muted">
                            <strong>Recipients:</strong> <span id="preview-recipients">0</span> users
                            <span id="preview-mode-text"></span>
                        </small>
                    </div>
                    <button type="button" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center" id="preview-btn">
                        <span class="material-icons-outlined me-1">visibility</span>
                        <span id="preview-btn-text">Preview</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">
                        Recipients
                        <span class="badge bg-secondary ms-2" id="total-users-badge">{{ count($users) }} total users</span>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Selection Mode</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="selection_mode" id="mode_exclude" value="exclude" @checked(old('selection_mode', 'exclude') === 'exclude')>
                            <label class="btn btn-outline-primary d-flex align-items-center justify-content-center" for="mode_exclude">
                                <span class="material-icons-outlined align-middle me-1" style="font-size: 18px;">block</span>
                                Exclude Mode
                            </label>
                            <input type="radio" class="btn-check" name="selection_mode" id="mode_include" value="include" @checked(old('selection_mode', 'exclude') === 'include')>
                            <label class="btn btn-outline-primary d-flex align-items-center justify-content-center" for="mode_include">
                                <span class="material-icons-outlined align-middle me-1" style="font-size: 18px;">check_circle</span>
                                Include Mode
                            </label>
                        </div>
                    </div>

                    <div class="alert alert-info" id="mode-info-exclude">
                        <span class="material-icons-outlined align-middle me-1">info</span>
                        <strong>Exclude Mode:</strong> The broadcast will be sent to ALL users except those selected below.
                    </div>
                    <div class="alert alert-success" id="mode-info-include" style="display: none;">
                        <span class="material-icons-outlined align-middle me-1">info</span>
                        <strong>Include Mode:</strong> The broadcast will be sent ONLY to the users selected below.
                    </div>

                    <div class="mb-3">
                        <label for="excluded_user_ids" class="form-label" id="user-select-label">Exclude Users (Optional)</label>
                        <select class="form-select" id="excluded_user_ids" name="excluded_user_ids[]" multiple>
                            @foreach($users as $user)
                                <option value="{{ $user['id'] }}">
                                    {{ $user['full_name'] }} ({{ $user['email'] }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text" id="user-select-help">
                            Select users who should NOT receive this broadcast.
                            Leave empty to send to all users.
                        </div>
                    </div>
                    <div class="alert alert-warning" id="recipient-summary" style="display: none;">
                        <strong>Summary:</strong> This broadcast will be sent to
                        <strong><span id="recipient-count">0</span></strong> users.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('admin.broadcast.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary d-flex align-items-center">
            <span class="material-icons-outlined me-1">check_circle</span>
            Review Broadcast
        </button>
    </div>
</form>
@endsection

@push('css')
    <link href="{{ URL::asset('build/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('build/plugins/select2/css/select2-bootstrap4.css') }}" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        .select2-container--bootstrap-5 .select2-selection {
            background-color: var(--bs-body-bg);
            border-color: var(--bs-border-color);
            color: var(--bs-body-color);
        }
        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
            color: white;
        }
        .select2-dropdown {
            background-color: var(--bs-body-bg);
            border-color: var(--bs-border-color);
        }
        .select2-container--bootstrap-5 .select2-results__option {
            color: var(--bs-body-color);
        }
        .select2-container--bootstrap-5 .select2-results__option--highlighted {
            background-color: var(--bs-primary);
            color: white;
        }
        #preview-message {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
@endpush

@push('script')
    <script src="{{ URL::asset('build/plugins/select2/js/select2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            const totalUsers = {{ count($users) }};
            let currentMode = 'exclude';

            // Initialize Select2 with Bootstrap 5 theme
            $('#excluded_user_ids').select2({
                theme: "bootstrap-5",
                width: '100%',
                placeholder: "Select users to include/exclude...",
                closeOnSelect: false,
                allowClear: true,
            });

            // Handle mode toggle
            $('input[name="selection_mode"]').on('change', function() {
                currentMode = $(this).val();
                updateModeUI();
                updateRecipientCount();
            });

            function updateModeUI() {
                if (currentMode === 'exclude') {
                    $('#mode-info-exclude').show();
                    $('#mode-info-include').hide();
                    $('#user-select-label').text('Exclude Users (Optional)');
                    $('#user-select-help').text('Select users who should NOT receive this broadcast. Leave empty to send to all users.');
                } else {
                    $('#mode-info-exclude').hide();
                    $('#mode-info-include').show();
                    $('#user-select-label').html('Include Users <span class="text-danger">*</span>');
                    $('#user-select-help').text('Select users who WILL receive this broadcast. You must select at least one user.');
                }
            }

            // Update recipient count when exclusion list changes
            $('#excluded_user_ids').on('change', function() {
                updateRecipientCount();
            });

            function updateRecipientCount() {
                const selectedCount = $('#excluded_user_ids').val() ? $('#excluded_user_ids').val().length : 0;
                let recipientCount;

                if (currentMode === 'exclude') {
                    recipientCount = totalUsers - selectedCount;
                } else {
                    recipientCount = selectedCount;
                }

                $('#recipient-count').text(recipientCount);
                $('#recipient-summary').show();

                // Update badge
                if (currentMode === 'exclude') {
                    if (selectedCount > 0) {
                        $('#total-users-badge').removeClass('bg-secondary bg-success').addClass('bg-warning');
                        $('#total-users-badge').text(recipientCount + ' recipients (' + selectedCount + ' excluded)');
                    } else {
                        $('#total-users-badge').removeClass('bg-warning bg-success').addClass('bg-secondary');
                        $('#total-users-badge').text(totalUsers + ' recipients (all users)');
                    }
                } else {
                    if (selectedCount > 0) {
                        $('#total-users-badge').removeClass('bg-secondary bg-warning').addClass('bg-success');
                        $('#total-users-badge').text(recipientCount + ' recipients (selected)');
                    } else {
                        $('#total-users-badge').removeClass('bg-warning bg-success').addClass('bg-secondary');
                        $('#total-users-badge').text('0 recipients (none selected)');
                    }
                }
            }

            // Preview functionality
            $('#preview-btn').on('click', function() {
                const isVisible = $('#preview-container').is(':visible');

                if (isVisible) {
                    // Collapse preview
                    $('#preview-container').slideUp();
                    $('#preview-btn-text').text('Preview');
                } else {
                    // Show preview
                    const subject = $('#subject').val();
                    const message = $('#message').val();
                    const selectedCount = $('#excluded_user_ids').val() ? $('#excluded_user_ids').val().length : 0;
                    let recipientCount, modeText;

                    if (!subject || !message) {
                        alert('Please fill in subject and message fields first.');
                        return;
                    }

                    if (currentMode === 'include') {
                        recipientCount = selectedCount;
                        modeText = selectedCount > 0 ? `(<span id="preview-selected-count">${selectedCount}</span> included)` : '';
                    } else {
                        recipientCount = totalUsers - selectedCount;
                        modeText = selectedCount > 0 ? `(<span id="preview-excluded-count">${selectedCount}</span> excluded)` : '';
                    }

                    $('#preview-subject').text(subject);
                    $('#preview-message').html(message.replace(/\n/g, '<br>'));
                    $('#preview-recipients').text(recipientCount);
                    $('#preview-mode-text').html(modeText);

                    $('#preview-container').slideDown();
                    $('#preview-btn-text').text('Hide Preview');
                }
            });

            // Form validation
            $('#broadcast-form').on('submit', function(e) {
                const subject = $('#subject').val();
                const message = $('#message').val();

                if (!subject || !message) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return false;
                }

                const selectedCount = $('#excluded_user_ids').val() ? $('#excluded_user_ids').val().length : 0;
                let recipientCount;

                if (currentMode === 'exclude') {
                    recipientCount = totalUsers - selectedCount;
                    if (recipientCount === 0) {
                        e.preventDefault();
                        alert('No recipients! You have excluded all users. Please adjust your exclusion list.');
                        return false;
                    }
                } else {
                    recipientCount = selectedCount;
                    if (recipientCount === 0) {
                        e.preventDefault();
                        alert('No recipients selected! Please select at least one user to include.');
                        return false;
                    }
                }
            });

            // Initialize recipient count
            updateRecipientCount();
        });
    </script>
@endpush
