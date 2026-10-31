@extends('layouts.app')
@section('content-class', 'main-content--wide')
@section('title')
Broadcast Messages
@endsection
@section('header')
    <div class="broadcast-page-heading">
        <div>
            <h4 class="mb-1 fs-2">Broadcast Messages</h4>
            <p class="mb-0">Send updates, announcements and important information to your {{ app_name() }} community.</p>
        </div>
        @can('broadcast')
            <a href="{{ route('admin.broadcast.create') }}" class="btn btn-primary broadcast-create-button">
                <span class="material-icons-outlined">add_circle_outline</span>
                Create Broadcast
            </a>
        @endcan
    </div>
@endsection
@section('content')
    <div class="broadcast-summary-grid">
        <article class="broadcast-summary-card broadcast-summary-card--total"><span class="broadcast-summary-icon"><i class="material-icons-outlined">campaign</i></span><span><small>Total Broadcasts</small><strong>{{ number_format($broadcastSummary['total']) }}</strong></span></article>
        <article class="broadcast-summary-card broadcast-summary-card--sent"><span class="broadcast-summary-icon"><i class="material-icons-outlined">send</i></span><span><small>Sent</small><strong>{{ number_format($broadcastSummary['sent']) }}</strong></span></article>
        <article class="broadcast-summary-card broadcast-summary-card--draft"><span class="broadcast-summary-icon"><i class="material-icons-outlined">draft</i></span><span><small>Drafts</small><strong>{{ number_format($broadcastSummary['draft']) }}</strong></span></article>
    </div>
    <div class="broadcast-table-shell">
        <div class="broadcast-table-titlebar"><i class="material-icons-outlined">campaign</i><span>Message history</span></div>
        <div class="table-responsive">
            <table class="table table-striped" id="broadcast-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Created By</th>
                        <th>Recipients</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .broadcast-page-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:1.5rem;margin-bottom:2rem}.broadcast-page-heading h4{color:#f5f8ff;font-weight:700;letter-spacing:-.025em}.broadcast-page-heading p{color:#8fb9e8;font-size:.94rem}.broadcast-create-button{display:inline-flex;align-items:center;gap:.42rem;padding:.52rem .82rem;border-radius:.5rem;font-size:.82rem;font-weight:700}.broadcast-create-button .material-icons-outlined{font-size:18px}.broadcast-summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;margin-bottom:1.45rem}.broadcast-summary-card{display:flex;align-items:center;gap:.9rem;min-height:110px;padding:1rem 1.15rem;border:1px solid #2871a8;border-radius:.75rem;background:linear-gradient(135deg,#123754,#0d2943);box-shadow:0 10px 24px rgba(0,0,0,.12)}.broadcast-summary-icon{display:grid;place-items:center;flex:0 0 48px;width:48px;height:48px;color:#79c2ff;background:rgba(38,136,235,.18);border-radius:.7rem}.broadcast-summary-icon .material-icons-outlined{font-size:25px}.broadcast-summary-card small{display:block;color:#cce2fa;font-size:.85rem;font-weight:700}.broadcast-summary-card strong{display:block;margin-top:.1rem;color:#fff;font-size:1.75rem;line-height:1.1}.broadcast-summary-card--sent .broadcast-summary-icon{color:#69e6ad;background:rgba(32,184,113,.18)}.broadcast-summary-card--draft .broadcast-summary-icon{color:#ffcb62;background:rgba(210,143,19,.18)}.broadcast-table-shell{overflow:hidden;border:1px solid #27618f;border-radius:.8rem;background:rgba(11,37,62,.8)}.broadcast-table-titlebar{display:flex;align-items:center;gap:.5rem;padding:.85rem 1rem;color:#edf7ff;background:#123758;border-bottom:1px solid #285f8d;font-size:.9rem;font-weight:700}.broadcast-table-titlebar .material-icons-outlined{color:#76bfff;font-size:20px}.broadcast-table-shell .dataTables_wrapper{padding:1rem}.broadcast-table-shell .dataTables_filter input{min-width:220px;color:#edf6ff;background:#091f34;border-color:#356e9f;border-radius:.4rem}.broadcast-table-shell .dataTables_length select{color:#edf6ff;background:#091f34;border-color:#356e9f;border-radius:.4rem}#broadcast-table{margin-top:.55rem!important;color:#eaf5ff;border-color:#234d73}#broadcast-table thead th{color:#d3e8ff;background:rgba(27,66,104,.76);border-bottom-color:#3972a0;font-size:.8rem;letter-spacing:.01em;white-space:nowrap}#broadcast-table td{padding:.78rem .55rem;vertical-align:middle;border-color:#234d73}#broadcast-table tbody tr:hover{background:rgba(37,94,143,.19)}#broadcast-table td:first-child a{color:#fff;font-weight:700;text-decoration:none}#broadcast-table td:first-child a:hover{color:#ffb068}#broadcast-table .badge{padding:.42em .65em;border-radius:999px;font-size:.73rem}#broadcast-table .btn{padding:.35rem .62rem;border-radius:.4rem;font-size:.78rem;font-weight:700}
        #broadcast-table td:nth-child(4),
        #broadcast-table td:nth-child(5),
        #broadcast-table td:last-child {
            white-space: nowrap;
        }
        @media(max-width:800px){.broadcast-summary-grid{grid-template-columns:1fr}.broadcast-page-heading{flex-direction:column}.broadcast-create-button{width:100%;justify-content:center}.broadcast-table-shell .dataTables_wrapper{padding:.65rem}}
    </style>
@endpush

@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#broadcast-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.broadcast.get_data') }}",
                pageLength: 10,
                lengthChange: false,
                order: [[4, 'desc']], // Sort by date descending
                columns: [
                    {
                        data: 'subject',
                        render: function(data, type, row) {
                            return '<a href="/admin/broadcast/' + row.id + '">' + data + '</a>';
                        }
                    },
                    { data: 'creator_name' },
                    {
                        data: 'recipient_count',
                        render: function(data, type, row) {
                            let text = data + ' users';
                            if (row.excluded_count > 0) {
                                if (row.selection_mode === 'include') {
                                    text += ' (' + row.excluded_count + ' included)';
                                } else {
                                    text += ' (' + row.excluded_count + ' excluded)';
                                }
                            }
                            return text;
                        }
                    },
                    {
                        data: 'status',
                        render: function(data, type, row) {
                            if (data === 'sent') {
                                return '<span class="badge bg-success">Sent</span>';
                            } else if (data === 'pending') {
                                return '<span class="badge bg-warning">Sending...</span>';
                            } else {
                                return '<span class="badge bg-secondary">Draft</span>';
                            }
                        }
                    },
                    {
                        data: "created_at",
                        render: function(data, type, row) {
                            return data ? new Date(data).toLocaleString('default', {
                                year: 'numeric', month: 'long', day: 'numeric',
                                hour: '2-digit', minute: '2-digit'
                            }) : '-';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            let buttons = `<a href="/admin/broadcast/${row.id}" class="btn btn-sm btn-outline-primary">View</a>`;

                            @can('broadcast')
                                if (row.status === 'draft') {
                                    buttons += ` <a href="/admin/broadcast/${row.id}/edit" class="btn btn-sm btn-outline-secondary">Edit</a>`;
                                    buttons += ` <button class="btn btn-sm btn-outline-danger" onclick="deleteBroadcast(${row.id})">Delete</button>`;
                                }
                            @endcan

                            return buttons;
                        }
                    }
                ]
            });
        });

        function deleteBroadcast(id) {
            if (confirm('Are you sure you want to delete this broadcast?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/broadcast/' + id;

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';

                form.appendChild(csrfInput);
                form.appendChild(methodInput);

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
@endpush
