@extends('layouts.app')
@section('title')
Log Activity
@endsection
@section('header')
    <div class="admin-list-header">
        <div>
            <h1>Log Activity</h1>
            <p>Pantau tindakan penting pada sistem dan buka baris untuk melihat detailnya.</p>
        </div>
        <span class="admin-header-note"><i class="material-icons-outlined">history</i> Diperbarui real-time</span>
    </div>
@endsection
@section('content')
    <div class="admin-summary-grid activity-summary-grid">
        <article><span class="admin-summary-icon blue"><i class="material-icons-outlined">today</i></span><div><small>Aktivitas hari ini</small><strong>{{ number_format($activitySummary['today']) }}</strong><em>Tercatat sejak 00.00</em></div></article>
        <article><span class="admin-summary-icon purple"><i class="material-icons-outlined">date_range</i></span><div><small>Minggu ini</small><strong>{{ number_format($activitySummary['this_week']) }}</strong><em>Sejak hari Senin</em></div></article>
        <article><span class="admin-summary-icon green"><i class="material-icons-outlined">storage</i></span><div><small>Total aktivitas</small><strong>{{ number_format($activitySummary['total']) }}</strong><em>Seluruh riwayat sistem</em></div></article>
        <article><span class="admin-summary-icon orange"><i class="material-icons-outlined">priority_high</i></span><div><small>Perlu perhatian</small><strong>{{ number_format($activitySummary['attention']) }}</strong><em>Error dan peringatan</em></div></article>
    </div>

    <section class="admin-data-panel log-data-panel">
        <header><div><h2>Riwayat aktivitas</h2><p>Gunakan pencarian untuk menemukan tindakan, pengguna, atau pesan tertentu.</p></div><span><i class="material-icons-outlined">touch_app</i> Klik baris untuk detail</span></header>
        <div class="table-responsive">
            <table class="table" id="logactivity-table">
                <thead>
                    <tr><th>Level</th><th>Aktivitas</th><th>Pengguna</th><th>Waktu</th></tr>
                </thead>
            </table>
        </div>
    </section>

    <!-- Log Details Modal -->
    <div class="modal fade" id="logDetailsModal" tabindex="-1" aria-labelledby="logDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logDetailsModalLabel">Log Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="logDetailsContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
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
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .sf-app .main-wrapper .main-content{max-width:1440px!important;padding:28px 32px 46px!important;background:#081225!important}.admin-list-header{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:22px}.admin-list-header h1{margin:0;color:#f6faff!important;font-size:1.9rem!important;font-weight:800!important}.admin-list-header p{margin:6px 0 0;color:#91afd0;font-size:.78rem}.admin-header-note{display:inline-flex;align-items:center;gap:5px;padding:8px 10px;border:1px solid #345a82;border-radius:8px;color:#9ec7ef;font-size:.69rem;font-weight:700;white-space:nowrap}.admin-header-note i{font-size:16px}.admin-summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:17px}.admin-summary-grid article{display:flex;align-items:center;gap:12px;min-height:105px;padding:16px;border:1px solid #294c71;border-radius:13px;background:linear-gradient(140deg,#0f223a,#0a192c)}.admin-summary-icon{display:grid;place-items:center;width:43px;height:43px;border-radius:11px}.admin-summary-icon i{font-size:22px}.admin-summary-icon.blue{background:rgba(88,158,255,.16);color:#79b5ff}.admin-summary-icon.purple{background:rgba(166,123,255,.16);color:#be9cff}.admin-summary-icon.green{background:rgba(73,219,155,.15);color:#65e2aa}.admin-summary-icon.orange{background:rgba(255,151,92,.15);color:#ffab7f}.admin-summary-grid small{display:block;color:#93b3d4;font-size:.68rem}.admin-summary-grid strong{display:block;margin:2px 0;color:#f5f9ff;font-size:1.43rem;line-height:1.05}.admin-summary-grid em{display:block;color:#789aba;font-size:.64rem;font-style:normal}.admin-data-panel{overflow:hidden;border:1px solid #294c71;border-radius:14px;background:linear-gradient(145deg,#0e2036,#0a192b);box-shadow:0 14px 28px rgba(0,0,0,.12)}.admin-data-panel>header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:17px 19px;border-bottom:1px solid rgba(75,116,157,.35)}.admin-data-panel h2{margin:0;color:#f4f9ff;font-size:1rem;font-weight:800}.admin-data-panel header p{margin:4px 0 0;color:#8faecc;font-size:.72rem}.admin-data-panel header>span{display:inline-flex;align-items:center;gap:5px;color:#9cc4e8;font-size:.67rem;font-weight:700;white-space:nowrap}.admin-data-panel header>span i{font-size:16px}.admin-data-panel .table-responsive{padding:0 12px 10px}.admin-data-panel .dataTables_wrapper{padding-top:12px;color:#a9c3dc;font-size:.72rem}.admin-data-panel .dataTables_filter input,.admin-data-panel .dataTables_length select{border:1px solid #365b83;border-radius:7px;background:#09192b;color:#edf6ff;padding:5px 8px;outline:0}.admin-data-panel .dataTables_filter input:focus{border-color:#72b4f4}.admin-data-panel table{margin:12px 0!important;color:#c4d6e8}.admin-data-panel table thead th{padding:10px 9px;border-bottom:1px solid #3a5b7d!important;color:#8eb3d7;font-size:.63rem;letter-spacing:.06em;text-transform:uppercase}.admin-data-panel table tbody td{padding:12px 9px;border-color:rgba(57,90,124,.48);vertical-align:middle;font-size:.75rem}.admin-data-panel table tbody tr{cursor:pointer;transition:background .16s}.admin-data-panel table tbody tr:hover{background:rgba(69,132,192,.12)!important}.log-level{display:inline-flex;padding:4px 7px;border-radius:6px;font-size:.62rem;font-weight:800;text-transform:uppercase}.log-level-error{background:rgba(244,99,104,.16);color:#ff999d}.log-level-warning{background:rgba(255,179,71,.16);color:#ffc66b}.log-level-info{background:rgba(100,181,246,.14);color:#8ec6ff}.log-level-default{background:rgba(147,171,198,.14);color:#b3c9de}.log-message{color:#eaf4ff;font-weight:700}.log-user{display:inline-flex;align-items:center;gap:6px;color:#b7d3ed}.log-user i{display:grid;place-items:center;width:24px;height:24px;border-radius:50%;background:#203b58;color:#87bfff;font-size:14px}.admin-data-panel .dataTables_paginate .paginate_button{border:1px solid #365b83!important;border-radius:6px!important;color:#b8d4ee!important;background:transparent!important}.admin-data-panel .dataTables_paginate .paginate_button.current{border-color:#ff7146!important;background:#ff7146!important;color:#fff!important}.admin-data-panel .dataTables_paginate .paginate_button:hover{border-color:#ff8b68!important;background:#1a3049!important;color:#fff!important}@media(max-width:900px){.admin-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:620px){.sf-app .main-wrapper .main-content{padding:18px!important}.admin-list-header,.admin-data-panel>header{align-items:flex-start;flex-direction:column}.admin-summary-grid{grid-template-columns:1fr}.admin-data-panel .table-responsive{padding:0 7px 8px}}
    </style>
@endpush
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            var table = $('#logactivity-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.logactivity.get_data') }}",
                // responsive: true,
                pageLength: 10,
                lengthChange: false,
                order: [[3, 'desc']],
                columns: [
                    {
                        data: 'level',
                        render: function(data) {
                            const level = String(data || 'info').toLowerCase();
                            const variant = ['emergency', 'alert', 'critical', 'error'].includes(level) ? 'error' : (level === 'warning' ? 'warning' : (['notice', 'info'].includes(level) ? 'info' : 'default'));
                            return `<span class="log-level log-level-${variant}">${escapeHtml(level)}</span>`;
                        }
                    },
                    {
                        data: 'message',
                        render: {
                            display: function(data, type, row) {
                                return `<span class="log-message">${escapeHtml(data || '-')}</span>`;
                            },
                            sort: function(data, type, row) {
                                return data;
                            }
                        }
                    },
                    {
                        data: 'user_name',
                        render: function(data) {
                            return `<span class="log-user"><i class="material-icons-outlined">person</i>${escapeHtml(data || 'SYSTEM')}</span>`;
                        }
                    },
                    {
                        data: "created_at",
                        render: {
                            display: function(data, type, row) {
                                return data ? new Date(data).toLocaleString('default', {
                                    year: 'numeric', month: 'long', day: 'numeric',
                                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                                }) : '-';

                            },
                            sort: function(data, type, row) {
                                return data;
                            }
                        }
                    },
                ]
            });

            // Handle row click
            $('#logactivity-table tbody').on('click', 'tr', function() {
                var data = table.row(this).data();
                if (data) {
                    showLogDetails(data.id);
                }
            });
        });

        function showLogDetails(logId) {
            // Show modal
            var modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
            modal.show();

            // Show loading spinner
            $('#logDetailsContent').html(`
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);

            // Fetch log details
            $.ajax({
                url: "{{ route('admin.logactivity.show', ':id') }}".replace(':id', logId),
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        displayLogDetails(response.data);
                    } else {
                        $('#logDetailsContent').html('<div class="alert alert-danger">Failed to load log details.</div>');
                    }
                },
                error: function(xhr) {
                    $('#logDetailsContent').html('<div class="alert alert-danger">Error loading log details: ' + xhr.statusText + '</div>');
                }
            });
        }

        function displayLogDetails(log) {
            var context = {};
            try {
                context = typeof log.context === 'string' ? JSON.parse(log.context) : log.context;
            } catch (e) {
                context = log.context || {};
            }

            var html = `
                <div class="row mb-3">
                    <div class="col-md-3"><strong>ID:</strong></div>
                    <div class="col-md-9">${log.id || '-'}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3"><strong>Level:</strong></div>
                    <div class="col-md-9">
                        <span class="badge bg-${getLevelBadgeClass(log.level)}">${log.level || '-'}</span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3"><strong>Message:</strong></div>
                    <div class="col-md-9">${log.message || '-'}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3"><strong>Date:</strong></div>
                    <div class="col-md-9">${log.created_at ? new Date(log.created_at).toLocaleString('default', {
                        year: 'numeric', month: 'long', day: 'numeric',
                        hour: '2-digit', minute: '2-digit', second: '2-digit'
                    }) : '-'}</div>
                </div>
            `;

            // Add context information if available
            if (Object.keys(context).length > 0) {
                html += '<hr><h6 class="mb-3">Additional Information:</h6>';

                // Display specific context fields in a nice format
                if (context.release_title) {
                    html += `
                        <div class="row mb-2">
                            <div class="col-md-3"><strong>Release Title:</strong></div>
                            <div class="col-md-9">${context.release_title}</div>
                        </div>
                    `;
                }

                if (context.model_id) {
                    html += `
                        <div class="row mb-2">
                            <div class="col-md-3"><strong>Model ID:</strong></div>
                            <div class="col-md-9">${context.model_id}</div>
                        </div>
                    `;
                }

                if (context.model_type) {
                    html += `
                        <div class="row mb-2">
                            <div class="col-md-3"><strong>Model Type:</strong></div>
                            <div class="col-md-9"><code>${context.model_type}</code></div>
                        </div>
                    `;
                }

                if (context.reason) {
                    html += `
                        <div class="row mb-2">
                            <div class="col-md-3"><strong>Reason:</strong></div>
                            <div class="col-md-9">${context.reason}</div>
                        </div>
                    `;
                }

                // Display error message if present
                if (context.message) {
                    html += `
                        <div class="row mb-2">
                            <div class="col-md-3"><strong>Error Message:</strong></div>
                            <div class="col-md-9"><div class="alert alert-danger mb-0">${context.message}</div></div>
                        </div>
                    `;
                }

                // Display stack trace if present
                if (context.stack) {
                    html += `
                        <div class="row mb-2">
                            <div class="col-md-3"><strong>Stack Trace:</strong></div>
                            <div class="col-md-9">
                                <pre class="bg-light p-2 rounded" style="max-height: 300px; overflow-y: auto; font-size: 0.85rem;">${escapeHtml(context.stack)}</pre>
                            </div>
                        </div>
                    `;
                }

                // Display all other context fields that weren't already shown
                var shownFields = ['release_title', 'model_id', 'model_type', 'reason', 'message', 'stack'];
                var hasOtherFields = false;

                for (var key in context) {
                    if (context.hasOwnProperty(key) && !shownFields.includes(key)) {
                        if (!hasOtherFields) {
                            html += '<hr><h6 class="mb-3">Other Context Data:</h6>';
                            hasOtherFields = true;
                        }

                        var value = context[key];
                        var displayValue = '';

                        if (typeof value === 'object' && value !== null) {
                            displayValue = '<pre class="bg-light p-2 rounded mb-0" style="font-size: 0.85rem;">' + JSON.stringify(value, null, 2) + '</pre>';
                        } else {
                            displayValue = escapeHtml(String(value));
                        }

                        html += `
                            <div class="row mb-2">
                                <div class="col-md-3"><strong>${escapeHtml(key)}:</strong></div>
                                <div class="col-md-9">${displayValue}</div>
                            </div>
                        `;
                    }
                }
            }

            $('#logDetailsContent').html(html);
        }

        function getLevelBadgeClass(level) {
            switch (level ? level.toLowerCase() : '') {
                case 'emergency':
                case 'alert':
                case 'critical':
                case 'error':
                    return 'danger';
                case 'warning':
                    return 'warning';
                case 'notice':
                case 'info':
                    return 'info';
                case 'debug':
                    return 'secondary';
                default:
                    return 'primary';
            }
        }

        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    </script>
@endpush
