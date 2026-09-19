@php
    /**
     * $user
     **/
@endphp

@if($user->profile)
    <div class="card shadow-sm border-0">
        <div class="card-header p-4 bg-light border-0">
            <div class="d-flex align-items-center">
                <div class="icon-box me-3 bg-body-secondary">
                    <i class="material-icons-outlined text-primary">account_circle</i>
                </div>
                <div>
                    <h5 class="mb-1 fw-bold">{{ $user->profile->firstname }} {{ $user->profile->lastname }}</h5>
                    <p class="mb-0 small">
                        @if($user->profile->is_company)
                            <i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">business</i>
                            {{ $user->profile->companyname }}
                        @else
                            <i class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">person</i>
                            Personal Account
                        @endif
                    </p>
                </div>
                <div class="ms-auto">
                    @if($user->profile->status == 'verified')
                        <span class="badge bg-success text-white px-3 py-2 rounded-pill">
                            <i class="material-icons-outlined me-1" style="font-size: 14px; vertical-align: middle;">verified</i>
                            Verified
                        </span>
                    @elseif($user->profile->status == 'pending')
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                            <i class="material-icons-outlined me-1" style="font-size: 14px; vertical-align: middle;">schedule</i>
                            Pending
                        </span>
                    @elseif($user->profile->status == 'blocked')
                        <span class="badge bg-danger text-white px-3 py-2 rounded-pill">
                            <i class="material-icons-outlined me-1" style="font-size: 14px; vertical-align: middle;">block</i>
                            Blocked
                        </span>
                    @else
                        <span class="badge bg-secondary px-3 py-2 rounded-pill">Unknown</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row">
                @if($user->profile->is_company)
                    <div class="col-lg-6 mb-4">
                        <h6 class="section-title">
                            <i class="material-icons-outlined me-2">business_center</i>
                            Company Information
                        </h6>
                        <table class="table table-borderless profile-info-table">
                            <tr>
                                <td class="text-muted"><i class="material-icons-outlined me-2">business</i>Nama Perusahaan</td>
                                <td>{{ $user->profile->companyname ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><i class="material-icons-outlined me-2">badge</i>Nomor Induk Berusaha (NIB)</td>
                                <td>{{ $user->profile->nibcompany_num ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><i class="material-icons-outlined me-2">description</i>NPWP Perusahaan</td>
                                <td>{{ $user->profile->npwpcompany_num ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><i class="material-icons-outlined me-2">work</i>Jabatan PIC</td>
                                <td>
                                    @if($user->profile->position == 'direktur_utama')
                                        Direktur Utama
                                    @elseif($user->profile->position == 'direktur')
                                        Direktur
                                    @else
                                        {{ $user->profile->position ?? '-' }}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                @endif

                <div class="col-lg-6 mb-4">
                    <h6 class="section-title">
                        <i class="material-icons-outlined me-2">person</i>
                        Contact Information
                    </h6>
                    <table class="table table-borderless profile-info-table">
                        <tr>
                            <td class="text-muted"><i class="material-icons-outlined me-2">person_outline</i>Nama Lengkap</td>
                            <td>{{ $user->profile->firstname }} {{ $user->profile->lastname }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="material-icons-outlined me-2">email</i>Alamat Email</td>
                            <td>{{ $user->email ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="material-icons-outlined me-2">phone</i>Nomor Handphone</td>
                            <td>{{ $user->profile->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="material-icons-outlined me-2">credit_card</i>NIK</td>
                            <td>{{ $user->profile->ktp_num ?? '-' }}</td>
                        </tr>
                        @if(!$user->profile->is_company)
                            <tr>
                                <td class="text-muted"><i class="material-icons-outlined me-2">receipt_long</i>NPWP Pribadi</td>
                                <td>
                                    {{ $user->profile->npwppersonal_num ?? '-' }}
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>

                <div class="col-lg-6 mb-4">
                    <h6 class="section-title">
                        <i class="material-icons-outlined me-2">location_on</i>
                        Address Information
                    </h6>
                    <table class="table table-borderless profile-info-table">
                        <tr>
                            <td class="text-muted"><i class="material-icons-outlined me-2">public</i>Negara</td>
                            <td>
                                @if($user->profile->country)
                                    {{ Monarobase\CountryList\CountryListFacade::getOne($user->profile->country, app()->getLocale()) }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @if($user->profile->country == 'ID')
                            <tr>
                                <td class="text-muted"><i class="material-icons-outlined me-2">map</i>Provinsi</td>
                                <td>{{ $user->profile->provinsi ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><i class="material-icons-outlined me-2">location_city</i>Kabupaten/Kota</td>
                                <td>{{ $user->profile->kota ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><i class="material-icons-outlined me-2">place</i>Kecamatan</td>
                                <td>{{ $user->profile->kecamatan ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><i class="material-icons-outlined me-2">home</i>Kelurahan</td>
                                <td>{{ $user->profile->kelurahan ?? '-' }}</td>
                            </tr>
                        @else
                            <tr>
                                <td class="text-muted"><i class="material-icons-outlined me-2">map</i>Provinsi</td>
                                <td>{{ $user->profile->state ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><i class="material-icons-outlined me-2">location_city</i>Kota</td>
                                <td>{{ $user->profile->city ?? '-' }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="text-muted"><i class="material-icons-outlined me-2">markunread_mailbox</i>Kode Pos</td>
                            <td>{{ $user->profile->zip ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="material-icons-outlined me-2">home_work</i>Alamat Lengkap</td>
                            <td>
                                {{ $user->profile->address1 ?? '-' }}
                                @if($user->profile->address2)
                                    <br>{{ $user->profile->address2 }}
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="col-lg-6 mb-4">
                    <h6 class="section-title">
                        <i class="material-icons-outlined me-2">account_balance</i>
                        Bank Account Information
                    </h6>
                    <table class="table table-borderless profile-info-table">
                        <tr>
                            <td class="text-muted"><i class="material-icons-outlined me-2">account_balance</i>Nama Bank</td>
                            <td>{{ $user->profile->bankname ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="material-icons-outlined me-2">store</i>Cabang Bank</td>
                            <td>{{ $user->profile->bankbranch ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="material-icons-outlined me-2">person</i>Nama Pemilik Rekening</td>
                            <td>{{ $user->profile->accountname ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="material-icons-outlined me-2">credit_card</i>Nomor Rekening</td>
                            <td>{{ $user->profile->accountnumber ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header p-4 bg-light border-0">
            <div class="d-flex align-items-center">
                <div class="icon-box me-3 bg-body-secondary">
                    <i class="material-icons-outlined text-primary">folder_open</i>
                </div>
                <h5 class="mb-0 fw-bold">Identity Documents</h5>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                @if ($user->profile->identities->count() > 0)
                    @foreach ($required as $req)
                        @php
                            $identity = $user->profile->identities->where('type', $req)->first();
                        @endphp
                        @if ($identity)
                            <div class="col-md-6 col-lg-4 col-xl-3">
                                <div class="document-card bg-body border border-2">
                                    <a href="{{ $identity->url }}" target="_blank" class="document-link">
                                        @if(Str::startsWith($identity->mime_type, 'image/'))
                                            {{-- Image preview --}}
                                            <div class="document-preview image-preview">
                                                <img alt="{{ $identity->type }}" class="document-img d-none" id="{{ $identity->type }}" data-url="{{ $identity->url }}" @if($identity->type == 'signature') style="background-color: #ffffff; padding: 10px;" @endif>
                                                <div class="document-loading" id="{{ $identity->type }}-spinner">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                                <div class="document-overlay text-white">
                                                    <i class="material-icons-outlined">visibility</i>
                                                    <span>View Image</span>
                                                </div>
                                            </div>
                                        @else
                                            {{-- File preview (PDF or other) --}}
                                            @php
                                                $fileExtension = strtolower(pathinfo($identity->original_name, PATHINFO_EXTENSION));
                                                $isPdf = $fileExtension === 'pdf';
                                            @endphp
                                            <div class="document-preview file-preview @if($isPdf) pdf-preview @endif">
                                                <div class="file-icon-wrapper">
                                                    @if($isPdf)
                                                        <i class="material-icons-outlined file-icon text-danger">picture_as_pdf</i>
                                                        <span class="file-badge badge bg-secondary-subtle text-secondary">PDF</span>
                                                    @else
                                                        <i class="material-icons-outlined file-icon text-secondary">insert_drive_file</i>
                                                        <span class="file-badge badge bg-secondary-subtle text-secondary">{{ strtoupper($fileExtension) }}</span>
                                                    @endif
                                                </div>
                                                <div class="file-name" title="{{ $identity->original_name }}">
                                                    {{ Str::limit($identity->original_name, 30) }}
                                                </div>
                                                <div class="document-overlay text-white">
                                                    <i class="material-icons-outlined">download</i>
                                                    <span>Open File</span>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="document-footer bg-body">
                                            <div class="document-title">
                                                <i class="material-icons-outlined me-1 success-icon text-success">check_circle</i>
                                                <span class="document-name">{{ $identitymap[$identity->type] }}</span>
                                            </div>
                                            <span class="document-status badge bg-success-subtle text-success">Uploaded</span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        @endif
                    @endforeach
                @else
                    <div class="col-12">
                        <div class="empty-state bg-body-secondary">
                            <i class="material-icons-outlined text-muted" style="font-size: 4rem;">folder_off</i>
                            <p class="text-muted mt-3 mb-0 fw-medium">No documents found</p>
                            <p class="text-muted small">Upload your identity documents below</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="document-actions mt-4 pt-4 border-top bg-body-secondary">
                <form action="{{ route('profile.identity.update') }}" id="identity-form" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('put')
                    <input type="file" name="file" id="identity-upload-file" accept=".jpg, .jpeg, .png, .pdf" class="d-none" maxlength="5242880">
                    <input type="hidden" name="type" id="identity-upload-type">
                    <input type="hidden" name="userid" value="{{ $user->id }}">
                </form>

                <h6 class="fw-bold mb-3">
                    <i class="material-icons-outlined me-2" style="font-size: 20px; vertical-align: middle;">upload_file</i>
                    Document Actions
                </h6>
                <div class="action-list">
                    @foreach ($required as $doc)
                        <div class="action-item bg-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="action-info">
                                    <i class="material-icons-outlined me-2 text-muted">description</i>
                                    <span class="fw-medium">{{ $identitymap[$doc] }}</span>
                                </div>
                                <div class="action-buttons">
                                    @if ($doc == 'signature')
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#signature-modal">
                                            <i class="material-icons-outlined me-1" style="font-size: 16px; vertical-align: middle;">draw</i>
                                            Upload
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-primary identity-upload-btn" data-type="{{ $doc }}">
                                            <i class="material-icons-outlined me-1" style="font-size: 16px; vertical-align: middle;">cloud_upload</i>
                                            Upload
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header p-4 bg-light border-0">
            <div class="d-flex align-items-center">
                <div class="icon-box me-3 bg-body-secondary">
                    <i class="material-icons-outlined text-primary">handshake</i>
                </div>
                <h5 class="mb-0 fw-bold">Contracts</h5>
            </div>
        </div>
        <div class="card-body p-4">
            <ul class="nav nav-pills mb-3" role="tablist">
                @if ($user->profile->feature_aggregator)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" data-bs-toggle="pill" href="#tabs-aggregator" role="tab" aria-selected="true">
                            <div class="d-flex align-items-center">
                                <div class="tab-icon"><i class="material-icons-outlined me-1">music_note</i>
                                </div>
                                <div class="tab-title">Aggregator</div>
                            </div>
                        </a>
                    </li>
                @endif
                @if ($user->profile->feature_publishing)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="pill" href="#tabs-publishing" role="tab" aria-selected="false" tabindex="-1">
                            <div class="d-flex align-items-center">
                                <div class="tab-icon"><i class="material-icons-outlined me-1">captive_portal</i>
                                </div>
                                <div class="tab-title">Publishing</div>
                            </div>
                        </a>
                    </li>
                @endif
                @if ($user->profile->feature_mcn)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="pill" href="#tabs-mcn" role="tab" aria-selected="false" tabindex="-1">
                            <div class="d-flex align-items-center">
                                <div class="tab-icon"><i class="material-icons-outlined me-1">live_tv</i>
                                </div>
                                <div class="tab-title">Multi-Channel Network</div>
                            </div>
                        </a>
                    </li>
                @endif
            </ul>
            <div class="tab-content">
                @if ($user->profile->feature_aggregator)
                    <div class="tab-pane fade show active" id="tabs-aggregator" role="tabpanel" aria-labelledby="tabs-aggregator-tab" style="height: 200px">
                        @if ($user->profile->aggregator_contracts->count() > 0)
                            <ul>
                                @foreach ($user->profile->aggregator_contracts as $aggregator_contract)
                                    <li><a href="{{ route('profile.contract.retrieve', ['id' => $aggregator_contract->id]) }}" target="_blank">{{ $aggregator_contract->docnum }}</a></li>
                                @endforeach
                            </ul>
                        @else
                            <div class="d-flex align-items-center justify-content-center w-100" style="height: 200px">
                                <h6 class="text-muted">No documents found</h6>
                            </div>
                        @endif
                    </div>
                @endif
                @if ($user->profile->feature_publishing)
                    <div class="tab-pane fade" id="tabs-publishing" role="tabpanel" aria-labelledby="tabs-publishing-tab" style="height: 200px">
                        @if ($user->profile->publishing_contracts->count() > 0)
                            <ul>
                                @foreach ($user->profile->publishing_contracts as $publishing_contract)
                                    <li><a href="{{ route('profile.contract.retrieve', ['id' => $publishing_contract->id]) }}" target="_blank">{{ $publishing_contract->docnum }}</a></li>
                                @endforeach
                            </ul>
                        @else
                            <div class="d-flex align-items-center justify-content-center w-100" style="height: 200px">
                                <h6 class="text-muted">No documents found</h6>
                            </div>
                        @endif
                    </div>
                @endif
                @if ($user->profile->feature_mcn)
                    <div class="tab-pane fade" id="tabs-mcn" role="tabpanel" aria-labelledby="tabs-mcn-tab" style="height: 200px">
                        <div class="d-flex justify-content-center">
                            
                            
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
@push('modals')
    <div class="modal fade" id="signature-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 py-2">
                    <h5 class="modal-title">Upload Signature</h5>
                    <a href="javascript:;" class="primaery-menu-close" data-bs-dismiss="modal">
                        <i class="material-icons-outlined">close</i>
                    </a>
                </div>
                <div class="modal-body">
                    <form action="{{ route('profile.identity.update') }}" id="signature-form" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('put')
                        <p>You can <label for="signature-upload" class="text-primary" style="cursor: pointer">upload a digital signature image</label> or directly sign below</p>
                        <input type="file" class="form-control" name="signature-upload" id="signature-upload" accept=".jpg, .jpeg, .png" style="display: none;">
                        <input type="hidden" name="signature" id="signature">
                        <input type="hidden" name="type" value="signature">
                        <input type="hidden" name="userid" value="{{ $user->id }}">
                        <div class="d-flex flex-column justify-content-center">
                            <canvas id="sig-canvas" class="my-2 mx-auto" width="450" height="160" style="border: 1px solid #000000;border-radius: 15px;cursor: crosshair; display: block; background-color: #FFFFFF">
                                {{__('Your browser doesn\'t support canvas. Please update your browser.')}}
                            </canvas>
                            <button type="button" type="button" class="btn btn-secondary mx-auto" style="width: fit-content" id="sig-clearBtn">{{__('Clear Signature')}}</button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" form="signature-form">Save Signature</button>
                </div>
            </div>
        </div>
    </div>
@endpush
@push('css')
    <style>
        /* Section Title Styles */
        .section-title {
            display: flex;
            align-items: center;
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--bs-border-color);
        }

        .section-title i {
            font-size: 1.25rem;
            vertical-align: middle;
        }

        /* Table Styles */
        .profile-info-table {
            font-size: 1em;
        }

        .profile-info-table tr:last-child {
            border-bottom: none;
        }

        .profile-info-table td {
            vertical-align: top;
        }

        .profile-info-table td:first-child {
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .profile-info-table td:first-child i {
            font-size: 1.1rem;
            vertical-align: middle;
            white-space: nowrap;
            opacity: 0.7;
        }

        .profile-info-table td:last-child {
            font-weight: 700;
        }

        /* Icon Box Styles */
        .icon-box {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 24px;
        }

        /* Document Card Styles */
        .document-card {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
        }

        .document-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(var(--bs-body-color-rgb), 0.12);
        }

        .document-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .document-preview {
            position: relative;
            width: 100%;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .document-preview.image-preview {
            background: var(--bs-body-bg);
        }

        .document-preview.file-preview {
            background: var(--bs-secondary-bg);
        }

        .document-preview.pdf-preview {
            background: var(--bs-danger-bg-subtle);
        }

        .document-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .document-card:hover .document-img {
            transform: scale(1.05);
        }

        .document-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }

        /* File Icon Styles */
        .file-icon-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .file-icon {
            font-size: 4.5rem;
            margin-bottom: 0.5rem;
        }

        .file-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-top: 0.5rem;
        }

        .file-name {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 0.5rem;
            background: var(--bs-body-bg);
            opacity: 0.95;
            font-size: 0.75rem;
            text-align: center;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Document Overlay */
        .document-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(var(--bs-primary-rgb), 0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .document-card:hover .document-overlay {
            opacity: 1;
        }

        .document-overlay i {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .document-overlay span {
            font-size: 0.875rem;
            font-weight: 600;
        }

        /* Document Footer */
        .document-footer {
            padding: 0.75rem 1rem;
            border-top: 2px solid var(--bs-border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .document-title {
            display: flex;
            align-items: center;
            flex: 1;
            min-width: 0;
        }

        .document-name {
            font-size: 0.875rem;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .success-icon {
            font-size: 1rem;
            flex-shrink: 0;
        }

        .document-status {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            font-weight: 600;
            flex-shrink: 0;
        }

        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            border-radius: 12px;
            border: 2px dashed var(--bs-border-color);
        }

        /* Action List Styles */
        .document-actions {
            border-radius: 12px;
            padding: 1.5rem;
        }

        .action-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .action-item {
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--bs-border-color);
            transition: all 0.2s ease;
        }

        .action-item:hover {
            box-shadow: 0 2px 8px rgba(var(--bs-primary-rgb), 0.1);
        }

        .action-info {
            display: flex;
            align-items: center;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-info-table td:first-child {
                width: 100%;
                display: block;
                padding-bottom: 0.25rem;
            }

            .profile-info-table td:last-child {
                display: block;
                padding-top: 0.25rem;
                padding-left: 1.8rem;
            }

            .action-item .d-flex {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 0.75rem;
            }

            .action-buttons {
                width: 100%;
                justify-content: flex-start;
            }

            .document-preview {
                height: 160px;
            }
        }
    </style>
@endpush
@push('script')
    <script src="{{ URL::asset('build/js/canvas.js') }}" type="text/javascript"></script>
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

        function signatureUpload() {
            let file = document.getElementById('signature-upload').files[0];
            if (file) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    let image = new Image();
                    image.src = e.target.result;
                    image.onload = function() {
                        let canvas = document.getElementById('sig-canvas');
                        let ctx = canvas.getContext('2d');
                        let widthRatio = canvas.width / image.width;
                        let heightRatio = canvas.height / image.height;
                        let ratio = Math.min(widthRatio, heightRatio);
                        let centerShiftX = (canvas.width - image.width * ratio) / 2;
                        let centerShiftY = (canvas.height - image.height * ratio) / 2;
                        ctx.drawImage(image, 0, 0, image.width, image.height, centerShiftX, centerShiftY, image.width * ratio, image.height * ratio);
                    }
                }
                reader.readAsDataURL(file);
            }
        }

        $(document).ready(function() {
            // Load all document images asynchronously
            var documents = ['#ktp','#npwppersonal','#npwpcompany','#nibcompany','#deed','#establishment','#signature'];
            documents.forEach(document => {
                loadImageAsync(document)
            });

            // Signature upload handler
            $('#signature-upload').on('change', function() {
                signatureUpload();
            });

            // Identity document upload handler
            $('.identity-upload-btn').on('click', function() {
                $('#identity-upload-type').val($(this).data('type'));
                $('#identity-upload-file').click();
            });

            // Submit form when file is selected
            $('#identity-upload-file').on('change', function() {
                if(this.files && this.files[0]) {
                    $('#identity-form').submit();
                }
            });

            // Signature form submission
            $('#signature-form').on('submit', function(e) {
                e.preventDefault();

                // Convert canvas to image
                $('input[name="signature"]').val(document.getElementById('sig-canvas').toDataURL('image/png'));

                // Submit form
                this.submit();
            });

            // Add smooth scroll to top after successful upload
            @if(session('success'))
                window.scrollTo({ top: 0, behavior: 'smooth' });
            @endif
        });
    </script>
@endpush
