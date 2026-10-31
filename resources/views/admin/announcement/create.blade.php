@extends('layouts.app')
@section('title')
Create Announcement
@endsection
@section('header')
    <div class="announcement-form-header"><a href="{{ route('admin.announcement.index') }}"><i class="material-icons-outlined">arrow_back</i> Kembali ke Announcements</a><h1>Create Announcement</h1><p>Bagikan informasi penting kepada pengguna {{ app_name() }}.</p></div>
@endsection
@section('content')
<form action="{{ route('admin.announcement.store') }}" method="POST" id="announcement-form">
    @csrf

    <div class="row mb-3">
        <div class="col-lg-7">
            <div class="card announcement-editor-card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Announcement Details</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                               id="title" name="title" value="{{ old('title') }}" maxlength="255" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                   id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                   id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                        <div id="quill-editor" style="height: 300px;"></div>
                        <input type="hidden" name="content" id="content" value="{{ old('content') }}">
                        @error('content')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <aside class="announcement-live-preview">
                <header><h2>Live preview</h2><p>Tampilan pengumuman untuk pengguna.</p></header>
                <div class="announcement-preview-card"><i class="material-icons-outlined">campaign</i><div><h3 id="inline-preview-title">Judul pengumuman akan tampil di sini</h3><span id="inline-preview-dates">Pilih tanggal aktif</span></div><hr><div id="inline-preview-content"><p>Konten pengumuman akan tampil di sini saat Anda mulai menulis.</p></div></div>
                <div class="announcement-period-note"><i class="material-icons-outlined">calendar_month</i><div><strong>Periode aktif</strong><span id="inline-preview-period">Belum ditentukan</span></div></div>
            </aside>
        </div>
    </div>

    <div class="announcement-form-actions">
        <a href="{{ route('admin.announcement.index') }}">Cancel</a>
        <button type="submit">Publish Announcement</button>
    </div>
</form>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Announcement Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info" role="alert">
                    <h4 class="alert-heading" id="preview-title-display">Untitled Announcement</h4>
                    <hr>
                    <div id="preview-content-display" class="preview-content">
                        <p class="text-muted">No content yet. Start typing to see the preview.</p>
                    </div>
                    <hr>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <small class="text-muted"><strong>Start Date:</strong> <span id="preview-start-date">Not set</span></small>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted"><strong>End Date:</strong> <span id="preview-end-date">Not set</span></small>
                        </div>
                    </div>
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
        .sf-app .main-wrapper .main-content{max-width:1440px!important;padding:28px 32px 46px!important;background:#081225!important}.announcement-form-header{margin-bottom:20px}.announcement-form-header>a{display:inline-flex;align-items:center;gap:5px;color:#8ebdf0;font-size:.74rem;font-weight:700;text-decoration:none}.announcement-form-header>a i{font-size:17px}.announcement-form-header h1{margin:12px 0 4px;color:#f5f9ff!important;font-size:1.85rem!important;font-weight:800!important}.announcement-form-header p{margin:0;color:#91afd0;font-size:.78rem}.announcement-editor-card{height:100%;border:1px solid #294c71!important;border-radius:14px!important;background:linear-gradient(145deg,#0e2036,#0a192b)!important}.announcement-editor-card .card-header{padding:18px 20px;border-color:rgba(75,116,157,.35)!important;background:transparent!important}.announcement-editor-card .card-body{padding:20px}.announcement-editor-card .form-label{color:#d5e6f6;font-size:.74rem;font-weight:800}.announcement-editor-card .form-control{border-color:#3a628b;background:#091a2d;color:#edf6ff;font-size:.76rem}.announcement-editor-card .form-control:focus{border-color:#70b5ff;box-shadow:0 0 0 3px rgba(112,181,255,.12)}.ql-toolbar.ql-snow,.ql-container.ql-snow{border-color:#3a628b!important;background:#091a2d;color:#edf6ff}.ql-toolbar.ql-snow{border-radius:8px 8px 0 0}.ql-container.ql-snow{border-radius:0 0 8px 8px}.ql-editor{min-height:250px;color:#edf6ff}.ql-container{font-family:inherit}.announcement-live-preview{height:100%;border:1px solid #294c71;border-radius:14px;background:linear-gradient(145deg,#0e2036,#0a192b);padding:20px}.announcement-live-preview header{margin-bottom:16px}.announcement-live-preview h2{margin:0;color:#f3f8ff;font-size:1.05rem;font-weight:800}.announcement-live-preview header p{margin:4px 0 0;color:#8faecc;font-size:.72rem}.announcement-preview-card{padding:16px;border:1px solid #345a82;border-radius:11px;background:#0a1b30;color:#c6dcf1}.announcement-preview-card>i{float:left;margin-right:11px;padding:9px;border-radius:50%;background:#1e4772;color:#7db7ff}.announcement-preview-card h3{margin:0;color:#eff7ff;font-size:.9rem;font-weight:800}.announcement-preview-card span{display:block;margin-top:4px;color:#87acd0;font-size:.68rem}.announcement-preview-card hr{clear:both;border-color:#294c71;margin:16px 0}.announcement-preview-card #inline-preview-content{min-height:120px;color:#b7cee5;font-size:.75rem;line-height:1.55}.announcement-period-note{display:flex;gap:10px;margin-top:16px;padding-top:16px;border-top:1px solid #294c71;color:#a9c8e6}.announcement-period-note i{color:#79b6ff;font-size:22px}.announcement-period-note strong,.announcement-period-note span{display:block}.announcement-period-note strong{font-size:.76rem}.announcement-period-note span{margin-top:3px;color:#83a4c4;font-size:.7rem}.announcement-form-actions{display:flex;justify-content:flex-end;gap:10px}.announcement-form-actions a,.announcement-form-actions button{height:39px;padding:0 14px;border-radius:8px;font-size:.74rem;font-weight:800;text-decoration:none;display:inline-flex;align-items:center}.announcement-form-actions a{border:1px solid #476b91;color:#c2d9ef}.announcement-form-actions button{border:0;background:#ff7146;color:#fff}@media(max-width:991px){.announcement-live-preview{margin-top:16px}}@media(max-width:620px){.sf-app .main-wrapper .main-content{padding:18px!important}.announcement-form-actions{justify-content:stretch}.announcement-form-actions a,.announcement-form-actions button{justify-content:center;flex:1}}
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

            // Load old content if validation fails
            @if(old('content'))
                try {
                    var oldContent = {!! json_encode(old('content')) !!};
                    if (typeof oldContent === 'string') {
                        oldContent = JSON.parse(oldContent);
                    }
                    quill.setContents(oldContent);
                } catch(e) {
                    console.error('Error loading old content:', e);
                }
            @endif

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
            });

            // Set minimum date for start_date to today
            var today = new Date().toISOString().split('T')[0];
            $('#start_date').attr('min', today);

            // Update end_date minimum when start_date changes
            $('#start_date').on('change', function() {
                $('#end_date').attr('min', $(this).val());
            });

            // Real-time preview update function
            function updatePreview() {
                // Update title
                var title = $('#title').val().trim();
                $('#preview-title-display').text(title || 'Untitled Announcement');
                $('#inline-preview-title').text(title || 'Judul pengumuman akan tampil di sini');

                // Update content - convert Quill Delta to HTML
                var htmlContent = quill.root.innerHTML;
                if (quill.getText().trim().length === 0) {
                    $('#preview-content-display').html('<p class="text-muted">No content yet. Start typing to see the preview.</p>');
                    $('#inline-preview-content').html('<p>Konten pengumuman akan tampil di sini saat Anda mulai menulis.</p>');
                } else {
                    $('#preview-content-display').html(htmlContent);
                    $('#inline-preview-content').html(htmlContent);
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

                var period = startDate && endDate ? new Date(startDate).toLocaleDateString('default', { year: 'numeric', month: 'short', day: 'numeric' }) + ' – ' + new Date(endDate).toLocaleDateString('default', { year: 'numeric', month: 'short', day: 'numeric' }) : 'Pilih tanggal aktif';
                $('#inline-preview-dates, #inline-preview-period').text(period);
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
