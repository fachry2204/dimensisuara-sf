@extends('layouts.app')
@section('title')
Announcements
@endsection
@section('header')
    <div class="admin-list-header">
        <div>
            <h1>Announcements</h1>
            <p>Kelola pengumuman yang akan tampil kepada komunitas Soundfresh.</p>
        </div>
        <a href="{{ route('admin.announcement.create') }}" class="announcement-create-btn"><i class="material-icons-outlined">add</i> Buat pengumuman</a>
    </div>
@endsection
@section('content')
    <div class="admin-summary-grid announcement-summary-grid">
        <article><span class="admin-summary-icon blue"><i class="material-icons-outlined">campaign</i></span><div><small>Total pengumuman</small><strong>{{ number_format($announcementSummary['total']) }}</strong><em>Seluruh pengumuman</em></div></article>
        <article><span class="admin-summary-icon green"><i class="material-icons-outlined">check_circle</i></span><div><small>Sedang aktif</small><strong>{{ number_format($announcementSummary['active']) }}</strong><em>Tampil saat ini</em></div></article>
        <article><span class="admin-summary-icon purple"><i class="material-icons-outlined">schedule</i></span><div><small>Terjadwal</small><strong>{{ number_format($announcementSummary['scheduled']) }}</strong><em>Menunggu tanggal mulai</em></div></article>
        <article><span class="admin-summary-icon orange"><i class="material-icons-outlined">inventory_2</i></span><div><small>Sudah berakhir</small><strong>{{ number_format($announcementSummary['expired']) }}</strong><em>Perlu ditinjau ulang</em></div></article>
    </div>

    <section class="admin-data-panel announcement-data-panel">
        <header><div><h2>Daftar pengumuman</h2><p>Cari, edit, atau hapus pengumuman dari satu tempat.</p></div><span><i class="material-icons-outlined">manage_search</i> Pencarian tersedia</span></header>
        <div class="table-responsive">
            <table class="table" id="announcement-table">
                <thead>
                    <tr><th>Judul</th><th>Mulai</th><th>Berakhir</th><th>Dibuat oleh</th><th>Dibuat pada</th><th>Aksi</th></tr>
                </thead>
            </table>
        </div>
    </section>
@endsection

@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .sf-app .main-wrapper .main-content{max-width:1440px!important;padding:28px 32px 46px!important;background:#081225!important}.admin-list-header{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:22px}.admin-list-header h1{margin:0;color:#f6faff!important;font-size:1.9rem!important;font-weight:800!important}.admin-list-header p{margin:6px 0 0;color:#91afd0;font-size:.78rem}.announcement-create-btn{display:inline-flex;align-items:center;gap:5px;padding:10px 13px;border-radius:8px;background:#ff7146;color:#fff!important;font-size:.72rem;font-weight:800;text-decoration:none;box-shadow:0 8px 20px rgba(255,113,70,.2)}.announcement-create-btn:hover{background:#ff875f}.announcement-create-btn i{font-size:17px}.admin-summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:17px}.admin-summary-grid article{display:flex;align-items:center;gap:12px;min-height:105px;padding:16px;border:1px solid #294c71;border-radius:13px;background:linear-gradient(140deg,#0f223a,#0a192c)}.admin-summary-icon{display:grid;place-items:center;width:43px;height:43px;border-radius:11px}.admin-summary-icon i{font-size:22px}.admin-summary-icon.blue{background:rgba(88,158,255,.16);color:#79b5ff}.admin-summary-icon.purple{background:rgba(166,123,255,.16);color:#be9cff}.admin-summary-icon.green{background:rgba(73,219,155,.15);color:#65e2aa}.admin-summary-icon.orange{background:rgba(255,151,92,.15);color:#ffab7f}.admin-summary-grid small{display:block;color:#93b3d4;font-size:.68rem}.admin-summary-grid strong{display:block;margin:2px 0;color:#f5f9ff;font-size:1.43rem;line-height:1.05}.admin-summary-grid em{display:block;color:#789aba;font-size:.64rem;font-style:normal}.admin-data-panel{overflow:hidden;border:1px solid #294c71;border-radius:14px;background:linear-gradient(145deg,#0e2036,#0a192b);box-shadow:0 14px 28px rgba(0,0,0,.12)}.admin-data-panel>header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:17px 19px;border-bottom:1px solid rgba(75,116,157,.35)}.admin-data-panel h2{margin:0;color:#f4f9ff;font-size:1rem;font-weight:800}.admin-data-panel header p{margin:4px 0 0;color:#8faecc;font-size:.72rem}.admin-data-panel header>span{display:inline-flex;align-items:center;gap:5px;color:#9cc4e8;font-size:.67rem;font-weight:700;white-space:nowrap}.admin-data-panel header>span i{font-size:16px}.admin-data-panel .table-responsive{padding:0 12px 10px}.admin-data-panel .dataTables_wrapper{padding-top:12px;color:#a9c3dc;font-size:.72rem}.admin-data-panel .dataTables_filter input,.admin-data-panel .dataTables_length select{border:1px solid #365b83;border-radius:7px;background:#09192b;color:#edf6ff;padding:5px 8px;outline:0}.admin-data-panel .dataTables_filter input:focus{border-color:#72b4f4}.admin-data-panel table{margin:12px 0!important;color:#c4d6e8}.admin-data-panel table thead th{padding:10px 9px;border-bottom:1px solid #3a5b7d!important;color:#8eb3d7;font-size:.63rem;letter-spacing:.06em;text-align:left;text-transform:uppercase}.admin-data-panel table tbody td{padding:12px 9px;border-color:rgba(57,90,124,.48);vertical-align:middle;font-size:.75rem}.announcement-title{display:block;color:#eef6ff;font-weight:800}.announcement-date{color:#a7c2db;white-space:nowrap}.announcement-action{padding:5px 8px!important;border-radius:6px!important;font-size:.66rem!important;font-weight:800!important}.admin-data-panel .dataTables_paginate .paginate_button{border:1px solid #365b83!important;border-radius:6px!important;color:#b8d4ee!important;background:transparent!important}.admin-data-panel .dataTables_paginate .paginate_button.current{border-color:#ff7146!important;background:#ff7146!important;color:#fff!important}.admin-data-panel .dataTables_paginate .paginate_button:hover{border-color:#ff8b68!important;background:#1a3049!important;color:#fff!important}@media(max-width:900px){.admin-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:620px){.sf-app .main-wrapper .main-content{padding:18px!important}.admin-list-header,.admin-data-panel>header{align-items:flex-start;flex-direction:column}.admin-summary-grid{grid-template-columns:1fr}.admin-data-panel .table-responsive{padding:0 7px 8px}}
    </style>
@endpush

@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#announcement-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.announcement.get_data') }}",
                pageLength: 10,
                lengthChange: false,
                order: [[0, 'asc']], // Sort by title ascending
                columns: [
                    {
                        data: 'title',
                        render: function(data) {
                            return `<span class="announcement-title">${escapeHtml(data || 'Tanpa judul')}</span>`;
                        }
                    },
                    {
                        data: 'start_date',
                        render: function(data, type, row) {
                            const value = data ? new Date(data).toLocaleDateString('default', {
                                year: 'numeric', month: 'short', day: 'numeric'
                            }) : '-';
                            return `<span class="announcement-date">${value}</span>`;
                        }
                    },
                    {
                        data: 'end_date',
                        render: function(data, type, row) {
                            const value = data ? new Date(data).toLocaleDateString('default', {
                                year: 'numeric', month: 'short', day: 'numeric'
                            }) : '-';
                            return `<span class="announcement-date">${value}</span>`;
                        }
                    },
                    { data: 'creator_name' },
                    {
                        data: "created_at",
                        render: function(data, type, row) {
                            const value = data ? new Date(data).toLocaleString('default', {
                                year: 'numeric', month: 'short', day: 'numeric',
                                hour: '2-digit', minute: '2-digit'
                            }) : '-';
                            return `<span class="announcement-date">${value}</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            let buttons = `<a href="/admin/announcement/${row.id}/edit" class="btn btn-sm btn-outline-secondary announcement-action">Edit</a>`;
                            buttons += ` <button class="btn btn-sm btn-outline-danger announcement-action" onclick="deleteAnnouncement(${row.id})">Hapus</button>`;
                            return buttons;
                        }
                    }
                ]
            });
        });

        function deleteAnnouncement(id) {
            if (confirm('Are you sure you want to delete this announcement?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/announcement/' + id;

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

        function escapeHtml(text) {
            return String(text).replace(/[&<>"']/g, function(character) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character];
            });
        }
    </script>
@endpush
