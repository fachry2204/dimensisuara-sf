@extends('layouts.app')
@section('title')
View User
@endsection
@section('header')
    <div class="admin-user-hero">
        <nav aria-label="breadcrumb" class="admin-user-breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">User Management</a></li>
                <li class="breadcrumb-item active" aria-current="page">View User</li>
            </ol>
        </nav>

        <div class="admin-user-summary">
            <div class="admin-user-avatar" aria-hidden="true">
                {{ Str::upper(Str::substr($user->profile ? $user->profile->full_name : $user->full_name, 0, 1)) }}
            </div>
            <div class="admin-user-summary-copy">
                <h1>{{ $user->profile ? $user->profile->full_name : $user->full_name }}</h1>
                <div class="admin-user-contact">
                    <span>{{ $user->email }}</span>
                    @if($user->profile && $user->profile->phone)
                        <span class="admin-user-contact-separator">•</span>
                        <a href="https://wa.me/{{ $user->profile->phone[0] == '0' ? '62' . substr($user->profile->phone, 1) : $user->profile->phone }}" target="_blank" rel="noopener">{{ $user->profile->phone }} <i class='bx bxl-whatsapp'></i></a>
                    @endif
                </div>
                <div class="admin-user-statuses">
                    <x-account-email-status :account="$user" :action="route('admin.users.verify_email', $user->id)" :can-manage="auth()->user()->can('manage-user-status')" />
                    @if($user->is_admin)
                        <span class="admin-user-status admin-user-status--admin">Admin</span>
                    @endif
                    @if ($user->profile)
                        <span class="admin-user-status admin-user-status--{{ $user->profile->status }}">{{ Str::headline($user->profile->status) }}</span>
                    @endif
                </div>
            </div>
            @can('manage-user-status')
                @if($user->profile && $user->profile->status == 'pending')
                    <div class="btn-group admin-user-actions">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#verify-modal">Verify User</button>
                        <button type="button" class="btn btn-outline-success split-bg-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg-end" style="">
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="dropdown-item text-primary">Edit User</a>
                            <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#block-modal">Block User</button>
                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#notify-modal">Send Notification</button>
                            @can('delete-users')
                                <div class="dropdown-divider"></div>
                                <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#delete-modal">Delete</button>
                            @endcan
                        </div>
                    </div>
                @endif
            @endcan
            @if($user->profile && $user->profile->status != 'pending')
                <div class="admin-user-action-buttons">
                    @can('manage-user-status')
                        @if($user->profile->status == 'blocked')
                            <button type="button" class="btn admin-user-action-btn is-success" data-bs-toggle="modal" data-bs-target="#unblock-modal"><i class="material-icons-outlined">lock_open</i> Unblock</button>
                        @else
                            <button type="button" class="btn admin-user-action-btn is-warning" data-bs-toggle="modal" data-bs-target="#block-modal"><i class="material-icons-outlined">block</i> Block</button>
                        @endif
                        <button type="button" class="btn admin-user-action-btn" data-bs-toggle="modal" data-bs-target="#notify-modal"><i class="material-icons-outlined">send</i> Message</button>
                    @endcan
                    @can('impersonate-users')
                        <button type="button" class="btn admin-user-action-btn" data-bs-toggle="modal" data-bs-target="#impersonate-modal"><i class="material-icons-outlined">visibility</i> Impersonate</button>
                    @endcan
                    @can('edit-users')
                        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn admin-user-action-btn"><i class="material-icons-outlined">edit</i> Edit</a>
                    @endcan
                    @can('delete-users')
                        @if(auth()->user()->id != $user->id)
                            <button type="button" class="btn admin-user-action-btn is-danger" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="material-icons-outlined">delete</i> Delete</button>
                        @endif
                    @endcan
                </div>
            @endif
        </div>
    </div>
@endsection
@section('content')

    {{-- Profile Details --}}
    @if($user->profile)

        {{-- Display identity documents --}}
        <section class="admin-user-documents">
            <div class="admin-user-section-heading">
                <div>
                    <h2>Identity Documents</h2>
                    <p>Review the verification files submitted by this user.</p>
                </div>
                <span class="admin-user-document-count">{{ $user->profile->identities->count() }} uploaded</span>
            </div>
        <div class="row g-3 justify-content-center">
            @if ($user->profile->identities->count() > 0)
                @foreach ($user->profile->identities->pluck('type')->unique() as $req)
                    @php
                        $identity = $user->profile->identities->where('type', $req)->first();
                    @endphp
                    @if ($identity)
                        @if(Str::startsWith($identity->mime_type, 'image/'))
                            <div class="col-lg-4 col-md-6">
                                <a href="{{ $identity->url }}" target="_blank" rel="noopener" class="identity-document-card">
                                    <div class="identity-document-preview">
                                    <img alt="{{ $identity->type }}" class="document d-none" id="{{ $identity->type }}" data-url="{{ $identity->url }}" @if($identity->type == 'signature') style="background-color: #ffffff" @endif>
                                    <div class="document" id="{{ $identity->type }}-spinner">
                                        <div class="spinner-grow " style="width: 3rem; height: 3rem;">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="identity-document-meta"><span>{{ $identitymap[$identity->type] ?? ($identity->type === 'legacy_contract' ? 'Kontrak dari website lama' : $identity->type) }}</span><i class="material-icons-outlined">open_in_new</i></div>
                                </a>
                            </div>
                        @else
                            <div class="col-lg-4 col-md-6">
                                <a href="{{ $identity->url }}" target="_blank" rel="noopener" class="identity-document-card">
                                    <div class="identity-document-preview document flex-column justify-content-end" id="{{ $identity->type }}-spinner">
                                        <div class="d-flex flex-grow-1">
                                            <i class="material-icons-outlined" style="margin: auto; font-size: 6.5rem">file_present</i>
                                        </div>
                                    </div>
                                    <div class="identity-document-meta"><span>{{ $identitymap[$identity->type] ?? ($identity->type === 'legacy_contract' ? 'Kontrak dari website lama' : $identity->type) }}</span><i class="material-icons-outlined">open_in_new</i></div>
                                </a>
                            </div>
                        @endif
                    @endif
                @endforeach
            @else
                <div class="d-flex align-items-center justify-content-center w-100">
                    <h6 class="text-muted">No identity documents found</h6>
                </div>
            @endif
        </div>
        </section>

        {{-- Display blocked reason --}}
        @if($user->profile->reason && $user->profile->status == 'blocked')
            <div class="alert alert-danger">
                <div class="d-flex align-items-center">
                    <div class="ms-3">
                        <h6 class="mb-0 text-danger">Blocked Reason</h6>
                        <div>
                            {{ $user->profile->reason }}
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Display profile details --}}
        <ul class="nav nav-pills admin-user-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="pill" href="#tabs-details" role="tab" aria-selected="true">
                    <div class="d-flex align-items-center">
                        <div class="tab-icon"><i class="material-icons-outlined me-1">info</i>
                        </div>
                        <div class="tab-title">Profile Details</div>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="pill" href="#tabs-contracts" role="tab" aria-selected="true">
                    <div class="d-flex align-items-center">
                        <div class="tab-icon"><i class="material-icons-outlined me-1">description</i>
                        </div>
                        <div class="tab-title">Contracts</div>
                    </div>
                </a>
            </li>
        </ul>
        <div class="tab-content">

            {{-- Registration Info --}}
            <div class="tab-pane fade show active pb-5" id="tabs-details" role="tabpanel" aria-labelledby="tabs-details-tab">
                <div class="row detail-blocks">

                    {{-- Address --}}
                    <div class="col-lg-6">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="fw-bold">Personal Info</h5>
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr><td class="text-muted">First Name</td><td>{{ $user->profile->firstname }}</td></tr>
                                            <tr><td class="text-muted">Last Name</td><td>{{ $user->profile->lastname }}</td></tr>
                                            <tr><td class="text-muted">Email</td><td>{{ $user->email }}</td></tr>
                                            <tr>
                                                <td class="text-muted">Phone</td>
                                                <td>
                                                    {{ $user->profile->phone }}
                                                    <a href="https://wa.me/{{ $user->profile->phone[0] == '0' ? '62' . substr($user->profile->phone, 1) : $user->profile->phone }}" target="_blank">
                                                        <i class='bx bxl-whatsapp'></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="fw-bold">Address</h5>
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr><td class="text-muted">Address 1</td><td>{{ $user->profile->address1 }}</td></tr>
                                            <tr><td class="text-muted">Address 2</td><td>{{ $user->profile->address2 }}</td></tr>

                                            @if($user->profile->country == 'ID')
                                                <tr><td class="text-muted">Kecamatan</td><td>{{ $user->profile->kecamatan }}</td></tr>
                                                <tr><td class="text-muted">Kelurahan</td><td>{{ $user->profile->kelurahan }}</td></tr>
                                                <tr><td class="text-muted">Kota</td><td>{{ $user->profile->kota }}</td></tr>
                                                <tr><td class="text-muted">Provinsi</td><td>{{ $user->profile->provinsi }}</td></tr>
                                                <tr><td class="text-muted">Kode Pos</td><td>{{ $user->profile->zip }}</td></tr>
                                            @else
                                                <tr><td class="text-muted">City</td><td>{{ $user->profile->city }}</td></tr>
                                                <tr><td class="text-muted">State</td><td>{{ $user->profile->state }}</td></tr>
                                                <tr><td class="text-muted">Zip</td><td>{{ $user->profile->zip }}</td></tr>
                                            @endif
                                            <tr><td class="text-muted">Country</td><td>{{ $user->profile->countryname }}</td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="fw-bold">Bank Account</h5>
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr><td class="text-muted">Nama Bank</td><td>{{ $user->profile->bankname }}</td></tr>
                                            <tr><td class="text-muted">Cabang Bank</td><td>{{ $user->profile->bankbranch }}</td></tr>
                                            <tr><td class="text-muted">Nama Pemilik Rekening</td><td>{{ $user->profile->accountname }}</td></tr>
                                            <tr><td class="text-muted">Nomor Rekening</td><td>{{ $user->profile->accountnumber }}</td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="fw-bold">Registration Info</h5>
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <td class="text-muted">Type</td><td>{{ $user->profile->is_company ? 'Company' : 'Personal' }}</td>
                                            </tr>
                                            @if($user->profile->is_company)
                                                <tr><td class="text-muted">Company Name</td><td>{{ $user->profile->companyname }}</td></tr>
                                            @endif
                                            <tr>
                                                <td class="text-muted">Features</td>
                                                <td>
                                                    <ul class="list-unstyled">
                                                        @if($user->profile->feature_aggregator)
                                                            <li>Aggregator</li>
                                                        @endif
                                                        @if($user->profile->feature_publishing)
                                                            <li>Publishing</li>
                                                        @endif
                                                        @if($user->profile->feature_mcn)
                                                            <li>Multi-Channel Network</li>
                                                        @endif
                                                    </ul>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class="text-muted">Whitelabel</td>
                                                <td>
                                                    <span class="badge {{ $user->profile->whitelabel_enabled ? 'bg-success' : 'bg-secondary' }}">{{ $user->profile->whitelabel_enabled ? 'Aktif' : 'Nonaktif' }}</span>
                                                    <p class="small text-muted mt-2 mb-2">{{ $user->profile->whitelabel_enabled ? 'Pengguna dapat mengisi Nama Label sendiri pada rilis.' : 'Nama Label dikunci menjadi Dimensi Suara pada pengisian rilis.' }}</p>
                                                    @can('manage-user-features')
                                                        <form method="POST" action="{{ route('admin.users.whitelabel.update', $user->id) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="whitelabel_enabled" value="{{ $user->profile->whitelabel_enabled ? '0' : '1' }}">
                                                            <button type="submit" class="btn btn-sm btn-outline-primary">{{ $user->profile->whitelabel_enabled ? 'Nonaktifkan Whitelabel' : 'Aktifkan Whitelabel' }}</button>
                                                        </form>
                                                    @endcan
                                                </td>
                                            </tr>
                                            @can('manage-user-features')
                                                @if(!$user->profile->feature_aggregator)
                                                    <tr><td colspan="2"><a href="javascript:;" data-bs-toggle="modal" data-bs-target="#enable-aggregator-modal">Enable aggregator feature</a></td></tr>
                                                @endif
                                                @if($user->profile->feature_aggregator)
                                                    <tr><td colspan="2"><a href="javascript:;" data-bs-toggle="modal" data-bs-target="#disable-aggregator-modal">Disable aggregator feature</a></td></tr>
                                                @endif
                                                @if(!$user->profile->feature_publishing)
                                                    <tr><td colspan="2"><a href="javascript:;" data-bs-toggle="modal" data-bs-target="#enable-publishing-modal">Enable publishing feature</a></td></tr>
                                                @endif
                                                @if($user->profile->feature_publishing)
                                                    <tr><td colspan="2"><a href="javascript:;" data-bs-toggle="modal" data-bs-target="#disable-publishing-modal">Disable publishing feature</a></td></tr>
                                                @endif
                                                @if(!$user->profile->feature_mcn)
                                                    <tr><td colspan="2"><a href="javascript:;" data-bs-toggle="modal" data-bs-target="#enable-mcn-modal">Enable Multi-Channel Network feature</a></td></tr>
                                                @endif
                                                @if($user->profile->feature_mcn)
                                                    <tr><td colspan="2"><a href="javascript:;" data-bs-toggle="modal" data-bs-target="#disable-mcn-modal">Disable Multi-Channel Network feature</a></td></tr>
                                                @endif
                                            @endcan
                                        </table>
                                    </div>
                                </div>
                            </div>
                            {{-- <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="fw-bold">Documents</h5>
                                        <table class="table table-sm table-borderless mb-0">
                                            @if($user->profile->dir_root)
                                                <tr><td><a href="https://drive.google.com/drive/folders/{{ $user->profile->dir_root }}" target="_blank">Google Drive Folder</a></td></tr>
                                                @if($user->profile->dir_aggregator)
                                                    <tr><td><a href="https://drive.google.com/drive/folders/{{ $user->profile->dir_aggregator }}" target="_blank">Surat Perjanjian Aggregator</a></td></tr>
                                                @endif
                                                @if($user->profile->dir_publishing)
                                                    <tr><td><a href="https://drive.google.com/drive/folders/{{ $user->profile->dir_publishing }}" target="_blank">Surat Perjanjian Publishing</a></td></tr>
                                                @endif
                                                @if($user->profile->dir_mcn)
                                                    <tr><td><a href="https://drive.google.com/drive/folders/{{ $user->profile->dir_mcn }}" target="_blank">Surat Perjanjian MCN</a></td></tr>
                                                @endif
                                            @endif
                                        </table>
                                    </div>
                                </div>
                            </div> --}}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Contracts --}}
            <div class="tab-pane fade pb-5" id="tabs-contracts" role="tabpanel" aria-labelledby="tabs-contracts-tab">
                <div class="row admin-user-contract-list">
                    {{-- Aggregator Contracts --}}
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="fw-bold">Aggregator Contracts</h5>
                                <div class="table-responsive" style="height: 200px">
                                    @if ($user->profile->aggregator_contracts->count() > 0)
                                        <table class="table table-borderless table-sm">
                                            <tbody>
                                                @foreach ($user->profile->aggregator_contracts as $aggregator_contract)
                                                    <tr>
                                                        <td>{{ $aggregator_contract->docnum }}</td>
                                                        <td>
                                                            <div class="contract-progress">
                                                                <span class="contract-progress-badge {{ $aggregator_contract->generated_html ? 'is-complete' : '' }}">Generate Kontrak: {{ $aggregator_contract->generated_html ? 'Sudah digenerate' : 'Belum digenerate' }}</span>
                                                                <span class="contract-progress-badge {{ $aggregator_contract->digitalStamp ? 'is-complete' : '' }}">Kontrak bermaterai Digital: {{ $aggregator_contract->digitalStamp ? 'Sudah diupload' : 'Belum diupload' }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="text-end">{{ $aggregator_contract->created_at }}</td>
                                                        <td class="text-center">
                                                            
                                                            <i class="bx bx-dots-vertical-rounded" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 1.5em; cursor: pointer"></i>
                                                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg-end">
                                                                @can('view-contracts')
                                                                    @if ($aggregator_contract->digitalStamp)
                                                                        <a class="dropdown-item" href="{{ route('admin.releases.contract.stamp_view', $aggregator_contract->id) }}" target="_blank" rel="noopener">Lihat PDF bermaterai digital</a>
                                                                    @endif
                                                                    <a class="dropdown-item" href="{{ route('admin.releases.contract.view_raw', ['id' => $aggregator_contract->id]) }}" target="_blank">Lihat PDF hasil generate</a>
                                                                    @if ($aggregator_contract->file)
                                                                        <a class="dropdown-item" href="{{ route('admin.releases.contract.view_uploaded', ['id' => $aggregator_contract->id]) }}" target="_blank">Lihat PDF upload manual</a>
                                                                    @endif
                                                                @endcan
                                                                @can('upload-contracts')
                                                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#stamp-upload-{{ $aggregator_contract->id }}">{{ $aggregator_contract->digitalStamp ? 'Ganti' : 'Upload' }} Kontrak Bermaterai</button>
                                                                    @include('admin.aggregator.partials.contract-stamp-modal', ['contract' => $aggregator_contract])
                                                                    <form action="{{ route('admin.releases.contract.pdf_upload',['id' => $aggregator_contract->id]) }}" method="POST" enctype="multipart/form-data" class="upload-pdf-form">
                                                                        @csrf
                                                                        <label for ="upload-pdf-{{ $aggregator_contract->id }}" class="dropdown-item text-primary">Upload document</label>
                                                                        <input name="file" id="upload-pdf-{{ $aggregator_contract->id }}" type="file" class="d-none upload-pdf-input" accept=".pdf">
                                                                        <input type="hidden" name="type" value="aggregator">
                                                                    </form>
                                                                @endcan
                                                                @can('delete-contracts')
                                                                    <div class="dropdown-divider"></div>
                                                                    <form action="{{ route('admin.releases.contract.destroy', ['id' => $aggregator_contract->id]) }}" method="POST">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="dropdown-item text-danger">Delete</button>
                                                                    </form>
                                                                @endcan
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="d-flex align-items-center justify-content-center w-100 h-100">
                                            <h6 class="text-muted">No contract found</h6>
                                        </div>
                                    @endif
                                </div>
                                @can('generate-contracts')
                                    <div class="d-flex align-items-center justify-content-center w-100">
                                        @if($user->profile->status === 'verified' && $user->profile->feature_aggregator)
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#aggregator-contract-modal">Generate Kontrak</button>
                                        @else
                                            <p class="text-secondary mb-0">Verifikasi user dan aktifkan fitur Aggregator untuk generate kontrak.</p>
                                        @endif
                                    </div>
                                @endcan
                            </div>
                        </div>
                    </div>

                    {{-- Publishing Contracts --}}
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="fw-bold">Publishing Contracts</h5>
                                <div class="table-responsive" style="height: 200px">
                                    @if ($user->profile->publishing_contracts->count() > 0)
                                        <table class="table table-borderless table-sm">
                                            <tbody>
                                                @foreach ($user->profile->publishing_contracts as $publishing_contract)
                                                    <tr>
                                                        <td>{{ $publishing_contract->docnum }}</td>
                                                        <td class="text-end">{{ $publishing_contract->created_at }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="d-flex align-items-center justify-content-center w-100 h-100">
                                            <h6 class="text-muted">No contract found</h6>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Multi-Channel Network Contracts --}}
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="fw-bold">Multi-Channel Network Contracts</h5>
                                <div class="table-responsive" style="height: 200px">
                                    @if ($user->profile->mcn_contracts->count() > 0)
                                        <table class="table table-borderless table-sm">
                                            <tbody>
                                                @foreach ($user->profile->mcn_contracts as $mcn_contract)
                                                    <tr>
                                                        <td>{{ $mcn_contract->docnum }}</td>
                                                        <td class="text-end">{{ $mcn_contract->created_at }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="d-flex align-items-center justify-content-center w-100 h-100">
                                            <h6 class="text-muted">No contract found</h6>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
@push('modals')
    @if($user->profile)
        @can('generate-contracts')
            {{-- Aggregator Contract Modal --}}
            <div class="modal fade" id="aggregator-contract-modal">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-bottom-0 py-2">
                            <h5 class="modal-title">Aggregator Contract</h5>
                            <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                                <i class="material-icons-outlined">close</i>
                            </a>
                        </div>
                        <div class="modal-body">
                            <p>Draft {{ $user->profile->is_company ? 'PT' : 'Non PT' }} diisi otomatis dari profil user. Atur pembagian yang akan dimasukkan ke kontrak.</p>
                            <form id="aggregator-contract-form" action="{{ route('admin.releases.contract.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="profileid" value="{{ $user->profile->id }}">
                                <input type="hidden" name="type" value="aggregator">
                                @include('admin.aggregator.partials.contract-date', ['datePrefix' => 'user'])
                                @include('admin.aggregator.partials.contract-rates', ['initialUserRate' => $user->profile->aggregator_rate ?? 70, 'ratePrefix' => 'user-contract'])
                                <div class="row">
                                    <div class="col-md-5 mb-3">
                                        <label for="number">Document Number <span class="text-danger">*</span></label>
                                        <input type="number" name="number" class="form-control" min="1" value="{{ old('number') }}" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="month">Month <span class="text-danger">*</span></label>
                                        <select type="text" name="month" class="form-control" required>
                                            <option value="1" @selected((int) old('month', now()->month) === 1)>January (I)</option>
                                            <option value="2" @selected((int) old('month', now()->month) === 2)>February (II)</option>
                                            <option value="3" @selected((int) old('month', now()->month) === 3)>March (III)</option>
                                            <option value="4" @selected((int) old('month', now()->month) === 4)>April (IV)</option>
                                            <option value="5" @selected((int) old('month', now()->month) === 5)>May (V)</option>
                                            <option value="6" @selected((int) old('month', now()->month) === 6)>June (VI)</option>
                                            <option value="7" @selected((int) old('month', now()->month) === 7)>July (VII)</option>
                                            <option value="8" @selected((int) old('month', now()->month) === 8)>August (VIII)</option>
                                            <option value="9" @selected((int) old('month', now()->month) === 9)>September (IX)</option>
                                            <option value="10" @selected((int) old('month', now()->month) === 10)>October (X)</option>
                                            <option value="11" @selected((int) old('month', now()->month) === 11)>November (XI)</option>
                                            <option value="12" @selected((int) old('month', now()->month) === 12)>December (XII)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="year">Year <span class="text-danger">*</span></label>
                                        <input type="number" name="year" class="form-control" min="2000" max="2100" value="{{ old('year', now()->year) }}" required>
                                    </div>
                                    <div class="col-12 mb-3">
                                        @can('upload-contracts')
                                        <label for="pdf">Upload PDF manual (opsional)</label>
                                        <input type="file" name="pdf" class="form-control" accept="application/pdf">
                                        @endcan
                                        <div class="form-text">Tanpa upload, PDF otomatis tersedia dari draft di Setting System.</div>
                                    </div>
                                </div>

                            </form>
                        </div>
                        <div class="modal-footer border-top-0">
                            <button class="btn btn-primary" type="submit" form="aggregator-contract-form">Generate Kontrak</button>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        @can('manage-user-status')
            {{-- Verify Modal --}}
            @if($user->profile->status == 'pending')
                <div class="modal fade" id="verify-modal">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-bottom-0 py-2">
                                <h5 class="modal-title">Verify User</h5>
                                <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                                    <i class="material-icons-outlined">close</i>
                                </a>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('admin.users.verify', $user->id) }}" method="POST" id="verify-form">
                                    @csrf
                                    @method('PUT')
                                    <p>Verifikasi user dan tetapkan pembagian penghasilan Aggregator.</p>
                                    @include('admin.user.partials.aggregator-rates')
                                </form>
                            </div>
                            <div class="modal-footer border-top-0">
                                <button type="button" class="btn btn-outline-success" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success" form="verify-form">Verify User</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Block Modal --}}
            @if($user->profile->status != 'blocked')
                <div class="modal fade" id="block-modal">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-bottom-0 py-2">
                                <h5 class="modal-title">Block User</h5>
                                <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                                    <i class="material-icons-outlined">close</i>
                                </a>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('admin.users.block', $user->id) }}" method="POST" id="block-form">
                                    @csrf
                                    @method('PUT')
                                    <p>Are you sure you want to block this user?</p>
                                    <textarea class="form-control" name="reason" id="" cols="30" rows="3" placeholder="Provide your message to the user" required></textarea>
                                </form>
                            </div>
                            <div class="modal-footer border-top-0">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger" form="block-form">Block User</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="impersonate-modal">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-bottom-0 py-2">
                                <h5 class="modal-title">Impersonate User</h5>
                                <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                                    <i class="material-icons-outlined">close</i>
                                </a>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('admin.impersonate', $user->id) }}" method="POST" id="impersonate-form">
                                    @csrf
                                    <p>Do you want to impersonate this user?</p>
                                </form>
                            </div>
                            <div class="modal-footer border-top-0">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger" form="impersonate-form">Impersonate User</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Notify User Modal --}}
            <div class="modal fade" id="notify-modal">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-bottom-0 py-2">
                            <h5 class="modal-title">Notify User</h5>
                            <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                                <i class="material-icons-outlined">close</i>
                            </a>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('admin.users.notify', $user->id) }}" method="POST" id="notify-form">
                                @csrf
                                @method('PUT')
                                <textarea class="form-control" name="reason" id="" cols="30" rows="3" placeholder="Provide your message to the user" required></textarea>
                            </form>
                        </div>
                        <div class="modal-footer border-top-0">
                            <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" form="notify-form">Notify User</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Unblock Modal --}}
            @if($user->profile->status == 'blocked')
                <div class="modal fade" id="unblock-modal">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-bottom-0 py-2">
                                <h5 class="modal-title">Unblock User</h5>
                                <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                                    <i class="material-icons-outlined">close</i>
                                </a>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('admin.users.unblock', $user->id) }}" method="POST" id="unblock-form">
                                    @csrf
                                    @method('PUT')
                                    <p>Are you sure you want to unblock this user?</p>
                                    <textarea class="form-control" name="reason" id="" cols="30" rows="3" placeholder="Provide your message to the user" required></textarea>
                                </form>
                            </div>
                            <div class="modal-footer border-top-0">
                                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger" form="unblock-form">Unblock User</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endcan

        @can('manage-user-features')
            {{-- Enable aggregator modal --}}
            @if(!$user->profile->feature_aggregator)
                <div class="modal fade" id="enable-aggregator-modal">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-bottom-0 py-2">
                                <h5 class="modal-title">Enable aggregator feature</h5>
                                <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                                    <i class="material-icons-outlined">close</i>
                                </a>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('admin.users.aggregator.enable', [$user->id]) }}" method="POST" id="enable-aggregator-form">
                                    @csrf
                                    @method('PUT')
                                    <p>Are you sure you want to enable aggregator feature for this user?</p>
                                </form>
                            </div>
                            <div class="modal-footer border-top-0">
                                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" form="enable-aggregator-form">Enable aggregator</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- disable aggregator modal --}}
            @if($user->profile->feature_aggregator)
                <div class="modal fade" id="disable-aggregator-modal">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-bottom-0 py-2">
                                <h5 class="modal-title">Disable aggregator feature</h5>
                                <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                                    <i class="material-icons-outlined">close</i>
                                </a>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('admin.users.aggregator.disable', [$user->id]) }}" method="POST" id="disable-aggregator-form">
                                    @csrf
                                    @method('PUT')
                                    <p>Are you sure you want to disable aggregator feature for this user?</p>
                                </form>
                            </div>
                            <div class="modal-footer border-top-0">
                                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger" form="disable-aggregator-form">Disable feature</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Publishing Enable Modal --}}
            @if(!$user->profile->feature_publishing)
                <div class="modal fade" id="enable-publishing-modal">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-bottom-0 py-2">
                                <h5 class="modal-title">Enable publishing feature</h5>
                                <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                                    <i class="material-icons-outlined">close</i>
                                </a>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('admin.users.publishing.enable', [$user->id]) }}" method="POST" id="enable-publishing-form">
                                    @csrf
                                    @method('PUT')
                                    <p>Are you sure you want to enable publishing feature for this user?</p>
                                </form>
                            </div>
                            <div class="modal-footer border-top-0">
                                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" form="enable-publishing-form">Enable publishing</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Publishing Disable Modal --}}
            @if($user->profile->feature_publishing)
                <div class="modal fade" id="disable-publishing-modal">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-bottom-0 py-2">
                                <h5 class="modal-title">Disable publishing feature</h5>
                                <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                                    <i class="material-icons-outlined">close</i>
                                </a>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('admin.users.publishing.disable', [$user->id]) }}" method="POST" id="disable-publishing-form">
                                    @csrf
                                    @method('PUT')
                                    <p>Are you sure you want to disable publishing feature for this user?</p>
                                </form>
                            </div>
                            <div class="modal-footer border-top-0">
                                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger" form="disable-publishing-form">Disable feature</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Multi-Channel Network Enable Modal --}}
            @if(!$user->profile->feature_mcn)
                <div class="modal fade" id="enable-mcn-modal">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-bottom-0 py-2">
                                <h5 class="modal-title">Enable Multi-Channel Network feature</h5>
                                <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                                    <i class="material-icons-outlined">close</i>
                                </a>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('admin.users.mcn.enable', [$user->id]) }}" method="POST" id="enable-mcn-form">
                                    @csrf
                                    @method('PUT')
                                    <p>Are you sure you want to enable Multi-Channel Network feature for this user?</p>
                                </form>
                            </div>
                            <div class="modal-footer border-top-0">
                                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" form="enable-mcn-form">Enable mcn</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Multi-Channel Network Disable Modal --}}
            @if($user->profile->feature_mcn)
                <div class="modal fade" id="disable-mcn-modal">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-bottom-0 py-2">
                                <h5 class="modal-title">Disable Multi-Channel Network feature</h5>
                                <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                                    <i class="material-icons-outlined">close</i>
                                </a>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('admin.users.mcn.disable', [$user->id]) }}" method="POST" id="disable-mcn-form">
                                    @csrf
                                    @method('PUT')
                                    <p>Are you sure you want to disable Multi-Channel Network feature for this user?</p>
                                </form>
                            </div>
                            <div class="modal-footer border-top-0">
                                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger" form="disable-mcn-form">Disable feature</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endcan

        @can('delete-users')
            {{-- Delete Modal --}}
            @if(auth()->user()->id != $user->id)
                <div class="modal fade" id="delete-modal">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-bottom-0 py-2">
                                <h5 class="modal-title">Delete User</h5>
                                <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                                    <i class="material-icons-outlined">close</i>
                                </a>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" id="delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <p>Are you sure you want to delete this user?</p>
                                </form>
                            </div>
                            <div class="modal-footer border-top-0">
                                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger" form="delete-form">Delete User</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endcan
    @endif
@endpush
@push('css')
    <style>
        :root {
            --user-detail-surface: var(--sf-surface);
            --user-detail-surface-strong: var(--sf-surface-soft);
            --user-detail-border: var(--sf-border);
            --user-detail-text: var(--sf-text);
            --user-detail-muted: var(--sf-muted);
            --user-detail-accent: var(--sf-accent);
        }

        .admin-user-hero {
            margin-bottom: 2.25rem;
        }

        .admin-user-breadcrumb .breadcrumb-item,
        .admin-user-breadcrumb .breadcrumb-item.active {
            color: var(--user-detail-muted);
            font-size: .86rem;
            font-weight: 600;
        }

        .admin-user-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
            color: var(--user-detail-muted);
        }

        .admin-user-breadcrumb a { color: var(--user-detail-accent); }

        .admin-user-summary {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.45rem 1.55rem;
            margin-top: .85rem;
            color: var(--user-detail-text);
            background: var(--user-detail-surface);
            border: 1px solid var(--user-detail-border);
            border-radius: 16px;
            box-shadow: 0 14px 32px rgba(0, 0, 0, .18);
        }

        .admin-user-avatar {
            display: grid;
            flex: 0 0 58px;
            width: 58px;
            height: 58px;
            place-items: center;
            border-radius: 50%;
            color: #fff;
            background: #ff7048;
            box-shadow: 0 0 0 5px rgba(255, 112, 72, .12);
            font-size: 1.35rem;
            font-weight: 800;
        }

        .admin-user-summary-copy { min-width: 0; }
        .admin-user-summary h1 {
            margin: 0 0 .3rem;
            color: var(--user-detail-text);
            font-size: clamp(1.45rem, 2.2vw, 2rem);
            font-weight: 800;
            letter-spacing: -.025em;
        }

        .admin-user-contact,
        .admin-user-statuses {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .45rem;
        }

        .admin-user-contact { color: var(--user-detail-muted); font-size: .92rem; }
        .admin-user-contact a { color: var(--user-detail-accent); }
        .admin-user-contact-separator { color: var(--user-detail-muted); }
        .admin-user-statuses { margin-top: .7rem; }

        .admin-user-status,
        .admin-user-document-count {
            display: inline-flex;
            align-items: center;
            min-height: 25px;
            padding: .22rem .62rem;
            border-radius: 999px;
            font-size: .73rem;
            font-weight: 800;
            letter-spacing: .01em;
        }

        .admin-user-status--pending { color: #4e3400; background: #ffd15c; }
        .admin-user-status--verified { color: #063f30; background: #50dfaa; }
        .admin-user-status--blocked { color: #fff; background: #ef5c6d; }
        .admin-user-status--admin { color: #0a3152; background: #dceeff; }
        .admin-user-status--unknown { color: #fff; background: #537795; }

        .admin-user-actions { margin-left: auto; }
        .admin-user-actions .btn {
            min-height: 39px;
            padding: .55rem .95rem;
            border-radius: 9px 0 0 9px;
            font-size: .86rem;
            font-weight: 800;
        }
        .admin-user-actions .dropdown-toggle-split { border-radius: 0 9px 9px 0; }

        .admin-user-action-buttons {
            display: flex;
            flex: 0 1 auto;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: .45rem;
            margin-left: auto;
        }
        .admin-user-action-btn {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            min-height: 34px;
            padding: .42rem .65rem;
            border: 1px solid #407aa5;
            border-radius: 8px;
            color: #d9edff;
            background: rgba(20, 64, 98, .55);
            font-size: .76rem;
            font-weight: 800;
            line-height: 1;
        }
        .admin-user-action-btn:hover { color: #fff; border-color: #75bfff; background: #1a5683; }
        .admin-user-action-btn .material-icons-outlined { font-size: .95rem; }
        .admin-user-action-btn.is-warning { color: #ffd09a; border-color: #a56735; background: rgba(112, 61, 29, .42); }
        .admin-user-action-btn.is-success { color: #a7ebcc; border-color: #3f9475; background: rgba(23, 102, 74, .38); }
        .admin-user-action-btn.is-danger { color: #ffc0c8; border-color: #a64d5d; background: rgba(117, 38, 52, .4); }

        .admin-user-documents {
            padding: 1.45rem;
            margin: 0 0 1.65rem;
            background: var(--user-detail-surface);
            border: 1px solid var(--user-detail-border);
            border-radius: 16px;
        }

        .admin-user-section-heading {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .admin-user-section-heading h2 {
            margin: 0;
            color: var(--user-detail-text);
            font-size: 1.08rem;
            font-weight: 800;
        }
        .admin-user-section-heading p { margin: .25rem 0 0; color: var(--user-detail-muted); font-size: .83rem; }
        .admin-user-document-count { color: var(--user-detail-muted); background: var(--user-detail-surface-strong); white-space: nowrap; }

        .identity-document-card {
            display: block;
            overflow: hidden;
            color: var(--user-detail-text);
            background: var(--user-detail-surface-strong);
            border: 1px solid var(--user-detail-border);
            border-radius: 12px;
            text-decoration: none;
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }
        .identity-document-card:hover {
            color: var(--user-detail-text);
            border-color: var(--user-detail-accent);
            box-shadow: 0 12px 22px rgba(0, 0, 0, .2);
            transform: translateY(-2px);
        }
        .identity-document-preview {
            display: grid;
            width: 100%;
            height: 168px;
            place-items: center;
            overflow: hidden;
            background: var(--user-detail-surface-strong);
        }
        .identity-document-preview .document { width: 100%; height: 100%; }
        .identity-document-preview .spinner-grow { color: var(--user-detail-accent); }
        .identity-document-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            min-height: 47px;
            padding: .7rem .85rem;
            color: var(--user-detail-text);
            font-size: .84rem;
            font-weight: 700;
        }
        .identity-document-meta i { color: var(--user-detail-accent); font-size: 1.05rem; }

        .admin-user-tabs {
            gap: .55rem;
            padding: .38rem;
            width: fit-content;
            border: 1px solid var(--user-detail-border);
            border-radius: 11px;
            background: var(--user-detail-surface-strong);
        }
        .admin-user-tabs .nav-link {
            padding: .55rem .85rem;
            border-radius: 8px;
            color: var(--user-detail-muted);
            font-size: .86rem;
            font-weight: 800;
        }
        .admin-user-tabs .nav-link.active { color: #fff; background: var(--user-detail-accent); box-shadow: 0 6px 15px rgba(255, 112, 72, .22); }
        .admin-user-tabs .tab-icon { display: inline-flex; }

        .detail-blocks { --bs-gutter-x: 1rem; --bs-gutter-y: 1rem; }
        .detail-blocks > div > .row { --bs-gutter-y: 1rem; }
        .detail-blocks .card,
        #tabs-contracts .card {
            height: 100%;
            color: var(--user-detail-text);
            background: var(--user-detail-surface);
            border: 1px solid var(--user-detail-border);
            border-radius: 14px;
            box-shadow: none;
        }
        .detail-blocks .card-body,
        #tabs-contracts .card-body { padding: 1.25rem; }
        .detail-blocks h5,
        #tabs-contracts h5 { margin-bottom: 1rem; color: var(--user-detail-text); font-size: 1rem; }
        .detail-blocks .table,
        #tabs-contracts .table { --bs-table-bg: transparent; --bs-table-color: var(--user-detail-text); margin: 0; }
        .detail-blocks .table tr + tr td,
        #tabs-contracts .table tr + tr td { border-top: 1px solid var(--user-detail-border); }
        .detail-blocks .table td,
        #tabs-contracts .table td { padding: .55rem .25rem; vertical-align: top; }
        .detail-blocks .table td:first-child { color: var(--user-detail-muted) !important; width: 43%; }
        .detail-blocks .table td:last-child { font-weight: 600; }
        .detail-blocks .table a,
        #tabs-contracts .table a { color: var(--user-detail-accent); }
        .detail-blocks .list-unstyled { margin: 0; }
        .detail-blocks .list-unstyled li + li { margin-top: .25rem; }

        /* Contracts are one vertical list instead of separate oversized cards. */
        .admin-user-contract-list {
            overflow: hidden;
            margin: 0;
            border: 1px solid var(--user-detail-border);
            border-radius: 14px;
            background: var(--user-detail-surface);
        }
        .admin-user-contract-list > .col-12 { padding: 0; }
        .admin-user-contract-list > .col-12 + .col-12 { border-top: 1px solid var(--user-detail-border); }
        .admin-user-contract-list .card {
            height: auto !important;
            background: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
        }
        .admin-user-contract-list .card-body {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: .7rem 1.15rem;
            padding: 1.05rem 1.25rem !important;
        }
        .admin-user-contract-list h5 { grid-column: 1; grid-row: 1; margin: 0 !important; font-size: .95rem !important; }
        .admin-user-contract-list .table-responsive {
            grid-column: 1 / -1;
            grid-row: 2;
            height: auto !important;
            min-height: 0 !important;
            overflow: visible !important;
        }
        .admin-user-contract-list .table { width: 100%; }
        .contract-progress { display: flex; flex-wrap: wrap; gap: .4rem; }
        .contract-progress-badge { display: inline-flex; padding: .3rem .55rem; border: 1px solid var(--sf-border); border-radius: 6px; background: var(--sf-surface-soft); color: var(--sf-muted); font-size: .75rem; white-space: nowrap; }
        html[data-bs-theme] body.sf-app .contract-progress-badge.is-complete { color: var(--sf-valid) !important; border-color: currentColor; }
        .admin-user-contract-list .table td { padding-top: .35rem !important; padding-bottom: .35rem !important; }
        .admin-user-contract-list .table td:last-child { white-space: nowrap; }
        .admin-user-contract-list .table-responsive > .d-flex {
            min-height: 0 !important;
            justify-content: flex-start !important;
        }
        .admin-user-contract-list .table-responsive h6 { margin: 0; color: var(--user-detail-muted) !important; font-size: .84rem; }
        .admin-user-contract-list .card-body > .d-flex {
            grid-column: 2;
            grid-row: 1;
            width: auto !important;
        }
        .admin-user-contract-list .btn { min-width: 128px; padding: .48rem .75rem; font-size: .8rem; font-weight: 800; white-space: nowrap; }

        @media (max-width: 767.98px) {
            .admin-user-contract-list .card-body { display: block; padding: 1rem !important; }
            .admin-user-contract-list .table-responsive { margin-top: .7rem; }
            .admin-user-contract-list .btn { margin-top: .75rem; }
        }

        @media (min-width: 992px) {
            body:has(.admin-user-hero) .main-wrapper .main-content { max-width: 1440px; }

            .detail-blocks {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 1rem;
                margin: 0;
            }

            .detail-blocks > .col-lg-6,
            .detail-blocks > .col-lg-6 > .row { display: contents; }

            .detail-blocks > .col-lg-6 > .row > .col-12 {
                width: auto;
                padding: 0;
            }

            /* Personal and registration are balanced on the first row. */
            .detail-blocks > .col-lg-6:first-child > .row > .col-12:nth-child(1) { grid-column: 1; grid-row: 1; }
            .detail-blocks > .col-lg-6:nth-child(2) > .row > .col-12:nth-child(1) { grid-column: 2; grid-row: 1; }

            /* Address and bank details share the second row. */
            .detail-blocks > .col-lg-6:first-child > .row > .col-12:nth-child(2) { grid-column: 1; }
            .detail-blocks > .col-lg-6:first-child > .row > .col-12:nth-child(3) { grid-column: 2; }
            .detail-blocks > .col-lg-6:nth-child(2) > .row > .col-12:nth-child(2) { grid-column: 1 / -1; }
            .detail-blocks > .col-lg-6:first-child > .row > .col-12:nth-child(2) { grid-row: 2; }
            .detail-blocks > .col-lg-6:first-child > .row > .col-12:nth-child(3) { grid-row: 2; }
            .detail-blocks > .col-lg-6:nth-child(2) > .row > .col-12:nth-child(2) { grid-row: 3; }
        }

        .detail-blocks table tr td:first-child {
            text-wrap: nowrap;
            padding-left: 0;
            max-width: fit-content;
        }
        .document {
            width: 300px;
            height: 200px;
            max-width: 100%;
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

        @media (max-width: 767.98px) {
            .admin-user-summary { align-items: flex-start; padding: 1.1rem; }
            .admin-user-avatar { flex-basis: 46px; width: 46px; height: 46px; font-size: 1.05rem; }
            .admin-user-actions { margin-left: 0; }
            .admin-user-summary { flex-wrap: wrap; }
            .admin-user-summary-copy { width: calc(100% - 62px); }
            .admin-user-actions { width: 100%; }
            .admin-user-action-buttons { width: 100%; justify-content: flex-start; margin-left: 0; }
            .admin-user-actions .btn:first-child { width: calc(100% - 42px); }
            .admin-user-section-heading { flex-direction: column; }
            .identity-document-preview { height: 150px; }
            .admin-user-tabs { width: 100%; }
            .admin-user-tabs .nav-item { flex: 1; }
            .admin-user-tabs .nav-link { justify-content: center; }
        }
    </style>
@endpush
@push('script')
    <script>
        function loadImageAsync(target) {
            if (!$(target).data('url')) {
                return;
            }
            const url = $(target).data('url');
            const downloadingImage  = new Image();
            downloadingImage.crossOrigin = "anonymous";
            downloadingImage.onload = function() {
                const canvas = document.createElement('canvas');
                canvas.width = this.width;
                canvas.height = this.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(this, 0, 0);

                canvas.toBlob(blob => {
                    if (blob) {
                        const objectURL = URL.createObjectURL(blob);
                        $(target).attr('src', objectURL);
                        $(target).removeClass('d-none');
                        $(target + '-spinner').addClass('d-none');
                    }
                }, 'image/png');
            };
            downloadingImage.src = url;
        }

        $(document).ready(function() {
            var documents = ['#ktp','#npwppersonal','#npwpcompany','#nibcompany','#deed','#establishment','#signature'];
            documents.forEach(document => {
                loadImageAsync(document)
            });

            $('.upload-pdf-input').on('change', function(e) {
                var form = $(this).closest('form');
                form.submit();
            });
            @if(session('contract_stamp_id') || old('stamp_contract_id'))
                const contractsTab = document.querySelector('[href="#tabs-contracts"]');
                if (contractsTab) bootstrap.Tab.getOrCreateInstance(contractsTab).show();
                @if($errors->has('stamped_pdf'))
                    const stampModal = document.getElementById('stamp-upload-{{ (int) old('stamp_contract_id', session('contract_stamp_id')) }}');
                    if (stampModal) bootstrap.Modal.getOrCreateInstance(stampModal).show();
                @endif
            @endif
        });
    </script>
@endpush
