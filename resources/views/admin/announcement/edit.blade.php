@extends('layouts.app')
@section('title')
Edit Announcement
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.announcement.index') }}">Announcements</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Announcement</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <h4 class="mb-0 fs-2">Edit Announcement</h4>
    </div>
@endsection
@section('content')
<form action="{{ route('admin.announcement.update', $announcement->id) }}" method="POST" id="announcement-form">
    @csrf
    @method('PUT')

    <div class="row mb-3">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Announcement Details</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                               id="title" name="title" value="{{ old('title', $announcement->title) }}" maxlength="255" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                   id="start_date" name="start_date"
                                   value="{{ old('start_date', $announcement->start_date->format('Y-m-d')) }}" required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                   id="end_date" name="end_date"
                                   value="{{ old('end_date', $announcement->end_date->format('Y-m-d')) }}" required>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                        <div id="quill-editor" style="height: 300px;"></div>
                        <input type="hidden" name="content" id="content" value="{{ old('content', json_encode($announcement->content)) }}">
                        @error('content')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#previewModal">
            Preview
        </button>
        <a href="{{ route('admin.announcement.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            Update Announcement
        </button>
    </div>
</form>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">

        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="preview-title-display">Untitled Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="preview-content-display" class="preview-content">
                    <p class="text-muted">No content yet. Start typing to see the preview.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .ql-editor {
            min-height: 250px;
        }
        .ql-container {
            font-family: inherit;
        }
        .preview-content {
            min-height: 100px;
        }
        .preview-content p {
            margin-bottom: 0.5rem;
        }
        .preview-content h1, .preview-content h2, .preview-content h3,
        .preview-content h4, .preview-content h5, .preview-content h6 {
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
        }
    </style>
@endpush

@push('script')
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Quill editor
            var quill = new Quill('#quill-editor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'align': [] }],
                        ['link', 'image'],
                        ['clean']
                    ]
                },
                placeholder: 'Write your announcement content here...'
            });

            // Load existing content
            try {
                var existingContent = {!! json_encode(old('content', $announcement->content)) !!};
                if (typeof existingContent === 'string') {
                    existingContent = JSON.parse(existingContent);
                }
                quill.setContents(existingContent);
            } catch(e) {
                console.error('Error loading content:', e);
            }

            // Form submission handler
            $('#announcement-form').on('submit', function(e) {
                // Validate title
                var title = $('#title').val().trim();
                if (!title) {
                    e.preventDefault();
                    alert('Please enter announcement title.');
                    return false;
                }

                // Get the Quill content as Delta JSON
                var content = quill.getContents();

                // Check if content is empty
                if (quill.getText().trim().length === 0) {
                    e.preventDefault();
                    alert('Please enter announcement content.');
                    return false;
                }

                // Store as JSON string
                $('#content').val(JSON.stringify(content));

                // Validate dates
                var startDate = new Date($('#start_date').val());
                var endDate = new Date($('#end_date').val());

                if (endDate < startDate) {
                    e.preventDefault();
                    alert('End date must be equal to or after the start date.');
                    return false;
                }

                return confirm('Are you sure you want to update this announcement?');
            });

            // Update end_date minimum when start_date changes
            $('#start_date').on('change', function() {
                $('#end_date').attr('min', $(this).val());
            });

            // Set initial minimum for end_date
            $('#end_date').attr('min', $('#start_date').val());

            // Real-time preview update function
            function updatePreview() {
                // Update title
                var title = $('#title').val().trim();
                $('#preview-title-display').text(title || 'Untitled Announcement');

                // Update content - convert Quill Delta to HTML
                var htmlContent = quill.root.innerHTML;
                if (quill.getText().trim().length === 0) {
                    $('#preview-content-display').html('<p class="text-muted">No content yet. Start typing to see the preview.</p>');
                } else {
                    $('#preview-content-display').html(htmlContent);
                }

                // Update dates
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();

                $('#preview-start-date').text(startDate ? new Date(startDate).toLocaleDateString('default', {
                    year: 'numeric', month: 'long', day: 'numeric'
                }) : 'Not set');

                $('#preview-end-date').text(endDate ? new Date(endDate).toLocaleDateString('default', {
                    year: 'numeric', month: 'long', day: 'numeric'
                }) : 'Not set');
            }

            // Listen for changes in title
            $('#title').on('input', function() {
                updatePreview();
            });

            // Listen for changes in Quill editor
            quill.on('text-change', function() {
                updatePreview();
            });

            // Listen for date changes
            $('#start_date, #end_date').on('change', function() {
                updatePreview();
            });

            // Update preview when modal is opened
            $('#previewModal').on('show.bs.modal', function() {
                updatePreview();
            });

            // Initial preview update
            updatePreview();
        });
    </script>
@endpush
