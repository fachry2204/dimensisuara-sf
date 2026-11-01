@extends('layouts.app')
@section('content-class', 'main-content--wide')
@section('title')
User Management
@endsection
@section('header')
    <div class="user-management-heading">
        <div>
            <h4 class="mb-1 fs-2">User Management</h4>
            <p class="mb-0">Manage and monitor {{ app_name() }} users from sign up to verified creators.</p>
        </div>
        <form action="{{ route('admin.users.export') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-primary user-export-button">
                <i class="material-icons-outlined">download</i>
                Export Users
            </button>
        </form>
    </div>
@endsection
@section('content')
    <div class="user-summary-grid" id="user-summary-cards">
        <button type="button" class="user-summary-card user-summary-card--total is-active" data-user-filter="all">
            <span class="user-summary-icon"><i class="material-icons-outlined">groups</i></span><span><small>Total Users</small><strong>{{ number_format($userSummary['total']) }}</strong><em>All registered users</em></span><i class="material-icons-outlined user-summary-arrow">chevron_right</i>
        </button>
        <button type="button" class="user-summary-card user-summary-card--pending" data-user-filter="pending">
            <span class="user-summary-icon"><i class="material-icons-outlined">schedule</i></span><span><small>Pending Verification</small><strong>{{ number_format($userSummary['pending']) }}</strong><em>Awaiting verification</em></span><i class="material-icons-outlined user-summary-arrow">chevron_right</i>
        </button>
        <button type="button" class="user-summary-card user-summary-card--verified" data-user-filter="verified">
            <span class="user-summary-icon"><i class="material-icons-outlined">task_alt</i></span><span><small>Verified</small><strong>{{ number_format($userSummary['verified']) }}</strong><em>Fully verified users</em></span><i class="material-icons-outlined user-summary-arrow">chevron_right</i>
        </button>
        <button type="button" class="user-summary-card user-summary-card--incomplete" data-user-filter="incomplete">
            <span class="user-summary-icon"><i class="material-icons-outlined">assignment_late</i></span><span><small>Incomplete Profile</small><strong>{{ number_format($userSummary['incomplete']) }}</strong><em>Required identity incomplete</em></span><i class="material-icons-outlined user-summary-arrow">chevron_right</i>
        </button>
    </div>
    <div class="user-table-shell">
        <div class="user-table-titlebar">
            <div><i class="material-icons-outlined">group</i><strong id="user-table-title">All Users</strong><span id="user-table-description">Showing all registered users.</span></div>
        </div>
        @if(auth()->user()->can('manage-user-status') || auth()->user()->can('manage-user-features'))
        <form id="bulk-user-form" class="p-3 border-bottom" aria-label="Ubah user terpilih">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <strong id="bulk-user-count" aria-live="polite">0 user dipilih</strong>
                <button type="button" id="select-filtered-users" class="btn btn-sm btn-outline-primary">Pilih semua hasil filter</button>
                <button type="button" id="clear-selected-users" class="btn btn-sm btn-outline-secondary">Batalkan pilihan</button>
            </div>
            <p class="small text-body-secondary">Pilihan berlaku di semua halaman tabel sesuai pencarian dan filter aktif. Mengganti pencarian atau filter akan mengosongkan pilihan.</p>
            <div class="row g-3">
                @can('manage-user-status')
                <div class="col-sm-6 col-xl-2"><label for="bulk-status" class="form-label">Status Profil</label><select id="bulk-status" name="status" class="form-select"><option value="">Tidak diubah</option><option value="pending">Pending</option><option value="verified">Verified</option><option value="blocked">Blocked</option></select></div>
                @endcan
                @can('manage-user-features')
                @foreach(['feature_aggregator'=>'Aggregator','feature_publishing'=>'Publishing','feature_mcn'=>'MCN','whitelabel_enabled'=>'Whitelabel'] as $field=>$label)
                <div class="col-sm-6 col-xl-2"><label for="bulk-{{ $field }}" class="form-label">{{ $label }}</label><select id="bulk-{{ $field }}" name="{{ $field }}" class="form-select"><option value="">Tidak diubah</option><option value="1">Aktifkan</option><option value="0">Nonaktifkan</option></select></div>
                @endforeach
                @endcan
                <div id="bulk-reason-group" class="col-12" hidden><label for="bulk-reason" class="form-label">Alasan pemblokiran</label><input id="bulk-reason" class="form-control" maxlength="255"><p class="form-text">Pemblokiran dari sini tidak menjadwalkan penghapusan akun.</p></div>
                <div class="col-12"><button id="apply-bulk-users" type="submit" class="btn btn-primary" disabled>Terapkan ke user terpilih</button></div>
            </div>
            <div id="bulk-user-feedback" class="mt-3" role="status" aria-live="polite" hidden></div>
        </form>
        @endif
        <div class="table-responsive">
        <table class="table table-striped" id="customer-table" style="width:100%; vertical-align: middle">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all-users" class="form-check-input" aria-label="Pilih semua user sesuai filter"></th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Registration Date</th>
                    <th>Status Profil</th>
                    <th>Status Email</th>
                    <th>Fitur</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
        </div>
    </div>
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .user-management-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:1.5rem;margin-bottom:2rem}.user-management-heading h4{color:#f5f8ff;font-weight:700;letter-spacing:-.025em}.user-management-heading p{color:#8fb9e8;font-size:.94rem}.user-export-button{display:inline-flex;align-items:center;gap:.4rem;padding:.48rem .76rem;border-radius:.48rem;font-size:.82rem;font-weight:700}.user-export-button .material-icons-outlined{font-size:17px}.user-summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;margin-bottom:1.5rem}.user-summary-card{position:relative;display:flex;align-items:center;gap:.8rem;width:100%;min-height:116px;padding:1rem;text-align:left;color:#d9eaff;background:linear-gradient(135deg,#123654,#0d2943);border:1px solid #2970a8;border-radius:.75rem;transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease}.user-summary-card:hover{transform:translateY(-2px);border-color:#6caee3}.user-summary-card.is-active{border-color:#ff9a3d;box-shadow:0 0 0 1px rgba(255,123,58,.28),0 12px 25px rgba(0,0,0,.18)}.user-summary-card .user-summary-icon{display:grid;place-items:center;flex:0 0 45px;width:45px;height:45px;color:#75bfff;background:rgba(38,136,235,.17);border-radius:50%}.user-summary-card .user-summary-icon .material-icons-outlined{font-size:24px}.user-summary-card--pending .user-summary-icon{color:#ffc759;background:rgba(210,143,19,.2)}.user-summary-card--verified .user-summary-icon{color:#65e5aa;background:rgba(35,185,117,.18)}.user-summary-card--incomplete .user-summary-icon{color:#a9ccf2;background:rgba(112,157,203,.18)}.user-summary-card span:nth-child(2){min-width:0}.user-summary-card small,.user-summary-card em{display:block;font-size:.78rem;font-style:normal;line-height:1.3}.user-summary-card small{color:#cce2fa;font-weight:700}.user-summary-card strong{display:block;margin:.12rem 0;color:#fff;font-size:1.65rem;line-height:1.06}.user-summary-card em{color:#93b9df}.user-summary-arrow{margin-left:auto;color:#8ab9e5;font-size:22px}.user-table-shell{overflow:hidden;border:1px solid #27618f;border-radius:.8rem;background:rgba(11,37,62,.8)}.user-table-titlebar{display:flex;align-items:center;justify-content:space-between;padding:.85rem 1rem;border-bottom:1px solid #285f8d;background:#123758}.user-table-titlebar>div{display:flex;align-items:center;gap:.55rem;color:#f2f8ff}.user-table-titlebar .material-icons-outlined{color:#75bdff;font-size:20px}.user-table-titlebar strong{font-size:.92rem}.user-table-titlebar span{color:#9fc5e9;font-size:.78rem}.user-table-shell .dataTables_wrapper{padding:1rem}.user-table-shell .dataTables_filter input,.user-table-shell .dataTables_length select{color:#edf6ff;background:#091f34;border-color:#356e9f;border-radius:.4rem}.user-table-shell .dataTables_filter input{min-width:220px}#customer-table{margin-top:.55rem!important;color:#eaf5ff;border-color:#234d73}#customer-table thead th{color:#d3e8ff;background:rgba(27,66,104,.76);border-bottom-color:#3972a0;font-size:.8rem;letter-spacing:.01em;white-space:nowrap}#customer-table td{padding:.78rem .55rem;vertical-align:middle;border-color:#234d73}#customer-table tbody tr:hover{background:rgba(37,94,143,.19)}#customer-table td:first-child{font-weight:700;color:#fff}#customer-table .badge{padding:.42em .65em;border-radius:999px;font-size:.73rem}#customer-table .btn{padding:.35rem .65rem;border-radius:.4rem;font-size:.78rem;font-weight:700}@media(max-width:1000px){.user-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:700px){.user-management-heading{flex-direction:column}.user-export-button{width:100%;justify-content:center}.user-summary-grid{grid-template-columns:1fr}.user-table-titlebar span{display:none}.user-table-shell .dataTables_wrapper{padding:.65rem}}
    </style>
@endpush
@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>
    <script type="application/json" id="json-customers">@json($customers)</script>
    <script>
        $(document).ready(function() {
            const canVerifyEmail = @json(auth()->user()->can('manage-user-status'));
            const verifyEmailUrl = @json(route('admin.users.verify_email', ':id'));
            const verificationCsrf = @json(csrf_token());
            const selectedUsers = new Set();
            const canBulkEdit = @json(auth()->user()->can('manage-user-status') || auth()->user()->can('manage-user-features'));
            let activeUserFilter = 'all';
            const filterLabels = {
                all: ['All Users', 'Showing all registered users.'],
                pending: ['Pending Verification', 'Showing users awaiting verification.'],
                verified: ['Verified Users', 'Showing fully verified users.'],
                incomplete: ['Incomplete Profile', 'Showing users with incomplete required identity.']
            };

            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'customer-table' || activeUserFilter === 'all') return true;
                const row = settings.aoData[dataIndex]._aData;
                if (activeUserFilter === 'incomplete') return !row.profile.is_profile_complete;
                return row.profile.status === activeUserFilter;
            });

            const customerTable = $('#customer-table').DataTable({
                data: JSON.parse(document.getElementById('json-customers').textContent),
                order: [[4, 'desc']],
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: function(id, type) {
                        if (type !== 'display' || !canBulkEdit) return '';
                        return `<input type="checkbox" class="form-check-input select-user" value="${Number(id)}" aria-label="Pilih user ${Number(id)}" ${selectedUsers.has(String(id)) ? 'checked' : ''}>`;
                    }},
                    { render: function(data, type, row) {
                        return row.profile.full_name
                    }},
                    { data: 'email' },
                    { render: function(data, type, row) {
                        const phone = row.profile.phone || '-';
                        const link = phone.startsWith('08') ? '62' + phone.slice(1) : phone;
                        const whatsapp = phone !== '-' ? `<a href="https://wa.me/${link}" class="fs-4" target="_blank" rel="noopener"><i class='bx bxl-whatsapp'></i></a>` : '';
                        return `<div class="text-nowrap">${phone} ${whatsapp}</div>`;
                    }},
                    {
                        data: 'created_at',
                        render: function(data, type, row) {
                            if (type === 'display' || type === 'filter') {
                                return dayjs(data).format('DD MMMM YYYY');
                            }
                            return data; // for sorting and type !== 'display'
                        }
                    },
                    {
                        render: function(data, type, row) {
                        switch (row.profile.status) {
                            case 'verified':
                                return `<span class="badge bg-success">Verified</span>`;
                            case 'pending':
                                return `<span class="badge bg-warning">Pending</span>`;
                            case 'blocked':
                                return `<span class="badge bg-danger">Blocked</span>`;
                            default:
                                return `<span class="badge bg-grd-royal">Unknown</span>`;
                        }
                    }},
                    { render: function(data, type, row) {
                        const verified = Boolean(row.email_verified_at);
                        const label = verified ? 'Sudah Verifikasi' : 'Belum Verifikasi';
                        if (type !== 'display') return label;
                        const badge = `<span class="badge ${verified ? 'bg-success' : 'bg-warning text-dark'}">${label}</span>`;
                        if (verified || !canVerifyEmail) return badge;
                        const action = verifyEmailUrl.replace(':id', encodeURIComponent(row.id));
                        return `<div class="d-flex flex-wrap align-items-center gap-2">${badge}<form method="POST" action="${action}" class="m-0"><input type="hidden" name="_token" value="${verificationCsrf}"><input type="hidden" name="_method" value="PUT"><button type="submit" class="btn btn-sm btn-outline-primary">Verifikasi Email</button></form></div>`;
                    }},
                    { orderable: false, render: function(data, type, row) {
                        const features = [['feature_aggregator','Aggregator'],['feature_publishing','Publishing'],['feature_mcn','MCN'],['whitelabel_enabled','Whitelabel']];
                        const active = features.filter(([field]) => Number(row.profile[field]) === 1).map(([,label]) => label);
                        if (type !== 'display') return active.join(', ');
                        return active.length ? active.map(label => `<span class="badge bg-primary me-1 mb-1">${label}</span>`).join('') : '<span class="text-body-secondary">—</span>';
                    }},
                    { render: function(data, type, row) {
                        var link = "{{ route('admin.users.show', ':id') }}".replace(':id', row.id);
                        return `<div class="actions"><a href="${link}" class="btn btn-sm btn-outline-primary">View</a>`;
                            // <a href="#" class="btn btn-sm btn-outline-danger">Delete</a></div>
                    }}
                ]
            });

            function filteredUsers() { return customerTable.rows({search: 'applied'}).data().toArray(); }
            function syncSelection() {
                const rows = filteredUsers();
                const count = rows.filter(row => selectedUsers.has(String(row.id))).length;
                $('#select-all-users').prop('disabled', !canBulkEdit || !rows.length).prop('checked', rows.length > 0 && count === rows.length).prop('indeterminate', count > 0 && count < rows.length);
                $('#bulk-user-count').text(`${selectedUsers.size} user dipilih`);
                $('#apply-bulk-users').prop('disabled', !selectedUsers.size);
                $('#customer-table .select-user').each(function() { this.checked = selectedUsers.has(this.value); });
            }
            customerTable.on('draw', syncSelection);
            let previousSearch = customerTable.search();
            customerTable.on('search', function() {
                if (previousSearch === customerTable.search()) return;
                previousSearch = customerTable.search();
                selectedUsers.clear();
                syncSelection();
            });
            $('#customer-table').on('change', '.select-user', function() {
                if (this.checked) selectedUsers.add(this.value); else selectedUsers.delete(this.value);
                syncSelection();
            });
            function selectFiltered(checked) {
                filteredUsers().forEach(row => checked ? selectedUsers.add(String(row.id)) : selectedUsers.delete(String(row.id)));
                syncSelection();
            }
            $('#select-all-users').on('change', function() { selectFiltered(this.checked); });
            $('#select-filtered-users').on('click', function() { selectFiltered(true); });
            $('#clear-selected-users').on('click', function() { selectedUsers.clear(); syncSelection(); });
            $('#bulk-status').on('change', function() {
                const blocked = this.value === 'blocked';
                $('#bulk-reason-group').prop('hidden', !blocked);
                $('#bulk-reason').prop('required', blocked);
            });
            $('#bulk-user-form').on('submit', async function(event) {
                event.preventDefault();
                const changes = {};
                const descriptions = [];
                $(this).find('select').each(function() {
                    if (this.value === '') return;
                    changes[this.name] = this.name === 'status' ? this.value : Number(this.value);
                    descriptions.push(`${$(this).prev('label').text()}: ${this.options[this.selectedIndex].text}`);
                });
                const feedback = $('#bulk-user-feedback');
                const showMessage = (message, failed = true) => feedback.prop('hidden', false).attr('class', `alert ${failed ? 'alert-danger' : 'alert-success'} mt-3`).text(message);
                if (!selectedUsers.size || !Object.keys(changes).length) { showMessage('Pilih user dan minimal satu perubahan.'); return; }
                if (!window.confirm(`Terapkan perubahan ke ${selectedUsers.size} user?\n\n${descriptions.join('\n')}`)) return;
                $('#apply-bulk-users').prop('disabled', true);
                try {
                    const response = await fetch(@json(route('admin.users.bulk-update')), {
                        method: 'PUT', headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':verificationCsrf},
                        body: JSON.stringify({ids: Array.from(selectedUsers, Number), changes, reason: $('#bulk-reason').val() || null})
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.errors ? Object.values(result.errors).flat().join(' ') : (result.message || 'Perubahan gagal disimpan.'));
                    showMessage(result.message, false);
                    window.location.reload();
                } catch (error) { showMessage(error.message || 'Koneksi gagal. Coba kembali.'); syncSelection(); }
            });
            syncSelection();

            $('#user-summary-cards').on('click', '.user-summary-card', function() {
                selectedUsers.clear();
                activeUserFilter = this.dataset.userFilter;
                $('.user-summary-card').removeClass('is-active');
                $(this).addClass('is-active');
                $('#user-table-title').text(filterLabels[activeUserFilter][0]);
                $('#user-table-description').text(filterLabels[activeUserFilter][1]);
                customerTable.draw();
            });
            $('#admin-table').DataTable();
        });
    </script>
@endpush
