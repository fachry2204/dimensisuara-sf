@props([
    'publishing',
    'allowEdit' => false,
    'allowGenerateContract' => false,
    'resendRoute' => '', 
    'contractRoute' => '', 
    'isDetailed' => false
])

<div class="row justify-content-center">
    <div class="col-lg-5 p-2 flex-grow-1">
        <table class="table table-sm table-borderless table-details">
            <tr>
                <td>Song Title</td>
                <td>{{ $publishing->title }}</td>
            </tr>
            <tr>
                <td>Other Title</td>
                <td>{{ $publishing->other_title }}</td>
            </tr>
            <tr>
                <td>Sample</td>
                <td><a href="{{ $publishing->sample }}" target="_blank">{{ $publishing->sample }}</a></td>
            </tr>
            <tr>
                <td>ISRC</td>
                <td>{{ $publishing->isrc }}</td>
            </tr>
            <tr>
                <td>ISWC</td>
                <td>{{ $publishing->iswc }}</td>
            </tr>
            <tr>
                <td>Performer/Artist</td>
                <td>{{ $publishing->performer }}</td>
            </tr>
            <tr>
                <td>Duration</td>
                <td>
                    {{ $publishing->duration_minute . ':' . $publishing->duration_second }}
                </td>
            </tr>
            <tr>
                <td>Language</td>
                <td>{{ $publishing->language_name }}</td>
            </tr>
            <tr>
                <td>Genre</td>
                <td>{{ $publishing->genre_name }}</td>
            </tr>
        </table>
    </div>
    <div class="col-lg-5 p-2 flex-grow-1">
        <div class="alert alert-info">
            <small>
                This request automatically includes the following rights:
                <ul class="mb-0">
                    <li>Synchronization</li>
                    <li>Mechanical</li>
                    <li>Performing</li>
                    <li>Print</li>
                    <li>Other</li>
                </ul>
            </small>
        </div>

        <div class="fw-normal">Synchronization Licensing Type:</div>
        <div>
            @if($publishing->sync_license_type === 'exclusive')
                <span class="fw-bold">Exclusive</span></br>
                <small class="fw-normal">Only the Publisher shall have the exclusive right to negotiate synchronization licensing of the Composition with third parties, subject to written notice to the Author/Composer.</small>
            @else
                <span class="fw-bold">Non-Exclusive</span></br>
                <small class="fw-normal">The Author/Composer may negotiate synchronization licensing of the Composition with third parties, subject to written notice to the Publisher.</small>
            @endif
        </div>
    </div>
</div>
@if($isDetailed)
    {{-- Admin view: Bootstrap accordion --}}
    <div class="accordion mb-3" id="songwriter-accordion">
        @foreach($publishing->signees as $index => $signee)
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading-{{ $signee->id }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $signee->id }}" aria-expanded="false">
                        <div class="songwriter-header-content">
                            <div class="d-flex align-items-center w-100">
                                <div class="songwriter-number">{{ $index + 1 }}</div>
                                <div class="d-flex flex-column flex-grow-1 ms-3">
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <span class="songwriter-name">{{ $signee->full_name }}</span>
                                        <span class="songwriter-email text-muted">{{ $signee->email }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-3 mt-1">
                                        <span class="songwriter-role"><span class="text-muted me-1">Role:</span>{{ $signee->role_name }}</span>
                                        <span class="songwriter-share"><span class="text-muted me-1">Share:</span>{{ $signee->share % 1 === 0 ? number_format($signee->share, 0, ',', '.') : number_format($signee->share, 2, ',', '.') }}%</span>
                                        @if($allowEdit)
                                            <span class="songwriter-status">@include('components.badge-publishing-signee', ['status' => $signee->status])</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </button>
                </h2>
                @if($signee)
                    <div id="collapse-{{ $signee->id }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $signee->id }}" data-bs-parent="#songwriter-accordion">
                        <div class="songwriter-detail accordion-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <strong class="d-block mb-2 text-primary">Personal & Publishing Details</strong>
                                    <div class="mb-2"><strong>Name:</strong> {{ $signee->full_name }}</div>
                                    <div class="mb-2"><strong>Email:</strong> {{ $signee->email }}</div>
                                    @if($signee->ktp_num)<div class="mb-2"><strong>NIK:</strong> {{ $signee->ktp_num }}</div>@endif
                                    @if($signee->phone)<div class="mb-2"><strong>Phone:</strong> {{ $signee->phone }}</div>@endif
                                    <div class="mb-2"><strong>Role:</strong> {{ $signee->role_name }}</div>
                                    <div class="mb-2"><strong>Share:</strong> {{ $signee->share }}%</div>
                                    @if($signee->publisher)<div class="mb-2"><strong>Publisher:</strong> {{ $signee->publisher }}</div>@endif
                                    @if($signee->lmk)<div class="mb-2"><strong>LMK:</strong> {{ $signee->lmk }}</div>@endif

                                    <div class="mt-3">
                                        <strong class="d-block mb-2 text-primary">Signature</strong>
                                        @if(!$signee->signature)
                                            <div class="text-muted small">None Provided</div>
                                        @else
                                            <div class="mt-2">
                                                <a href="{{ route('sign.publishing.songwriter.documents.show', ['token' => $signee->token, 'file' => $signee->signature, 'hash' => sha1($signee->signature->id . '|' . $signee->signature->updated_at)]) }}" target="_blank" class="d-inline-block border rounded-3 p-2 bg-white">
                                                    @if(Str::startsWith($signee->signature->mime_type, 'image/'))
                                                        <img src="{{ route('sign.publishing.songwriter.documents.show', ['token' => $signee->token, 'file' => $signee->signature, 'hash' => sha1($signee->signature->id . '|' . $signee->signature->updated_at)]) }}" alt="Signature" style="max-height: 120px; max-width: 100%; display: block; object-fit: contain;">
                                                    @else
                                                        <div class="document flex-column justify-content-end rounded-4" style="border: 2px solid #FF8C00; max-height: 120px; width: 150px; height: 100px;">
                                                            <div class="d-flex flex-grow-1">
                                                                <i class="material-icons-outlined" style="margin: auto; font-size: 2.5rem">file_present</i>
                                                            </div>
                                                            <span class="text-muted text-sm text-center p-1 w-100" style="background-color: #FF8C00; border-radius:0px 0px 10px 10px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.8rem;" title="{{ $signee->signature->original_name }}">{{ $signee->signature->original_name }}</span>
                                                        </div>
                                                    @endif
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong class="d-block mb-2 text-primary">Timeline</strong>
                                    <div class="timeline">
                                        <div class="timeline-item">
                                            <div class="timeline-marker"></div>
                                            <div class="timeline-content">
                                                <div class="timeline-title">Signee Created
                                                    @if ($signee->status == 'pending' && !empty($resendRoute))
                                                       <a href="{{ route($resendRoute, $signee->id) }}" class="btn btn-sm btn-outline-primary">Resend Request</a>
                                                    @endif
                                                </div>
                                                <div class="timeline-date">{{ \Carbon\Carbon::parse($signee->created_at)->format('j F Y, H:i') }}</div>
                                            </div>
                                        </div>
                                        @if($signee->status == 'accepted')
                                            <div class="timeline-item">
                                                <div class="timeline-marker bg-success"></div>
                                                <div class="timeline-content">
                                                    <div class="timeline-title">Request Accepted</div>
                                                    <div class="timeline-date">{{ \Carbon\Carbon::parse($signee->accepted_at ?? $signee->updated_at)->format('j F Y, H:i') }}</div>
                                                </div>
                                            </div>
                                        @elseif($signee->status == 'rejected')
                                            <div class="timeline-item">
                                                <div class="timeline-marker bg-danger"></div>
                                                <div class="timeline-content">
                                                    <div class="timeline-title">Request Rejected</div>
                                                    <div class="timeline-date">{{ \Carbon\Carbon::parse($signee->updated_at)->format('j F Y, H:i') }}</div>
                                                </div>
                                            </div>
                                        @endif
                                        @if($signee->contract && !empty($contractRoute))
                                            <div class="timeline-item timeline-item-contract">
                                                <div class="timeline-marker bg-primary"></div>
                                                <div class="timeline-content">
                                                    <div class="timeline-title">
                                                        Contract Generated
                                                        <span class="badge bg-primary ms-2">{{ $signee->contract->docnum }}</span>
                                                    </div>
                                                    <div class="timeline-date">{{ \Carbon\Carbon::parse($signee->contract->created_at)->format('j F Y, H:i') }}</div>
                                                    <div class="mt-2">
                                                        <a href="{{ route($contractRoute, ['id' => $signee->contract->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <span class="material-icons-outlined me-1" style="font-size: 14px;">visibility</span>
                                                            View Contract
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@else
    {{-- Customer view: Simple table --}}
    <div class="table-responsive">
        <table class="table table-sm table-details-2">
            <thead>
                <th>Songwriter</th>
                <th>Email</th>
                <th>Role</th>
                <th>Share</th>
                @if ($allowEdit)
                    <th>Publishers</th>
                    <th>Status</th>
                    <th>Action</th>
                @endif
            </thead>
            <tbody>
                @foreach($publishing->signees as $signee)
                    <tr>
                        @if($signee)
                            <td>{{ $signee->full_name }}</td>
                            <td>{{ $signee->email }}</td>
                        @else
                            <td colspan="2">Deleted Songwriter</td>
                        @endif
                        <td>{{ $signee->role_name }}</td>
                        <td>{{ $signee->share % 1 === 0 ? number_format($signee->share, 0, ',', '.') : number_format($signee->share, 2, ',', '.') }}%</td>
                        @if ($allowEdit)
                            <td><i class="bi bi-info-circle" style="cursor: pointer" data-bs-toggle="modal" data-bs-target="#publishers-{{ $signee->id }}"></i></td>
                            <td>@include('components.badge-publishing-signee', ['status' => $signee->status])</td>
                            <td><a href="{{ route($resendRoute, $signee->id) }}" class="btn btn-sm btn-outline-primary">Resend Request</a></td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@push('css')
    <style>
        ul {
            padding-left: 1rem;
        }
        .table-details, .table-details-2 {
            font-size: 0.9em;
        }
        .table-details  td:first-child{
            color: var(--bs-secondary-color);
            font-size: 1em;
        }
        .table-details  td:last-child {
            font-size: 1.1em;
            font-weight: 700;
        }
        .table-details-2 th {
            color: var(--bs-secondary-color);
            font-size: 1em;
            font-weight: normal;
        }
        .table-details-2 th:first-child, .table-details-2 td:first-child {
            padding-left: 0px;
        }
        .table-details-2 th:last-child, .table-details-2 td:last-child {
            padding-right: 0px;
        }
        .table-details-2 td {
            font-size: 1.1em;
            font-weight: 700;
            vertical-align: middle;
        }

        /* Admin-specific accordion styles */
        .songwriter-header-content {
            width: 100%;
        }
        .songwriter-number {
            width: 40px;
            height: 40px;
            background: var(--bs-primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1em;
            flex-shrink: 0;
        }
        .songwriter-name {
            font-weight: 700;
            font-size: 1.05em;
        }
        .songwriter-email {
            font-size: 0.9em;
        }
        .songwriter-role,
        .songwriter-share {
            font-size: 0.9em;
        }
        .songwriter-action {
            flex-shrink: 0;
        }
        .accordion-button:not(.collapsed) {
            background-color: rgba(var(--bs-primary-rgb), 0.05);
        }
        .songwriter-detail {
            padding: 1.5rem;
        }

        /* Tab separator - distinct visual break */
        .tab-separator {
            height: 1px;
            background: linear-gradient(to right, transparent, var(--bs-border-color) 20%, var(--bs-border-color) 80%, transparent);
            margin: 1rem 0 1.5rem 0;
        }

        /* Tab content with min-height to prevent page jumps */
        .tab-content-min-height {
            min-height: 300px;
        }

        /* Borderless table for songwriter info */
        .songwriter-info-table {
            font-size: 0.9em;
        }
        .songwriter-info-table td:first-child {
            color: var(--bs-secondary-color);
            padding-left: 0px;
            white-space: nowrap;
            font-size: 1em;
            width: 35%;
            vertical-align: top;
        }
        .songwriter-info-table td:last-child {
            font-weight: 700;
            padding-right: 0px;
            font-size: 1.1em;
        }
        .songwriter-info-table .material-icons-outlined {
            font-size: 18px;
            vertical-align: middle;
        }

        /* Section Titles */
        .section-title {
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--bs-border-color);
            display: flex;
            align-items: center;
        }
        .section-title .material-icons-outlined {
            color: var(--bs-primary);
        }

        /* Publishers List */
        .publishers-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .publisher-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.75rem;
            transition: all 0.3s ease;
        }
        .publisher-item:hover {
            box-shadow: 0 4px 12px rgba(var(--bs-body-color-rgb), 0.1);
            transform: translateX(5px);
        }
        .publisher-number {
            width: 40px;
            height: 40px;
            background: var(--bs-primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }
        .publisher-info {
            margin-left: 1rem;
        }
        .publisher-name {
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        /* Contract Card */
        .contract-card {
            border: 2px solid var(--bs-primary);
            border-radius: 1rem;
            overflow: hidden;
        }
        .contract-header {
            background: var(--bs-primary);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .contract-icon {
            width: 48px;
            height: 48px;
            background: rgba(var(--bs-body-bg-rgb), 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .contract-icon .material-icons-outlined {
            font-size: 28px;
        }
        .contract-body {
            padding: 1.25rem;
        }

        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            text-align: center;
        }
        .empty-state .material-icons-outlined {
            font-size: 64px;
            color: var(--bs-secondary-color);
            opacity: 0.5;
            margin-bottom: 1rem;
        }

        /* Document cards */
        .document-card {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.75rem;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        .document-card:hover {
            box-shadow: 0 4px 12px rgba(var(--bs-body-color-rgb), 0.1);
            transform: translateY(-2px);
        }
        .document-preview {
            width: 100%;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bs-secondary-bg);
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            overflow: hidden;
        }
        .doc-thumbnail {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .doc-thumbnail:hover {
            transform: scale(1.05);
        }
        .doc-icon {
            font-size: 64px;
            color: var(--bs-secondary-color);
        }
        .doc-icon .material-icons-outlined {
            font-size: 64px;
        }
        .document-missing {
            height: 150px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--bs-secondary-color);
        }
        .document-missing .material-icons-outlined {
            font-size: 48px;
            opacity: 0.5;
        }
        .document-info {
            margin-top: 0.5rem;
        }
        .doc-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9em;
        }

        /* Nav pills customization */
        .nav-pills-sm .nav-link {
            padding: 0.5rem 1rem;
            font-size: 0.9em;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }
        .nav-pills-sm .nav-link:hover {
            background-color: rgba(var(--bs-primary-rgb), 0.1);
        }
        .nav-pills-sm .nav-link.active {
            background-color: var(--bs-primary);
        }

        /* Timeline styles */
        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--bs-border-color);
        }
        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .timeline-marker {
            position: absolute;
            left: -1.6rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--bs-primary);
            border: 2px solid var(--bs-body-bg);
            box-shadow: 0 0 0 2px var(--bs-border-color);
        }
        .timeline-content {
            padding-left: 1rem;
        }
        .timeline-title {
            font-weight: 600;
            font-size: 0.95em;
            margin-bottom: 0.25rem;
        }
        .timeline-date {
            font-size: 0.85em;
            color: var(--bs-secondary-color);
        }
        .timeline-item-contract {
            background: rgba(var(--bs-primary-rgb), 0.05);
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-left: -0.75rem;
        }

        /* Copy button */
        .copy-btn {
            color: var(--bs-secondary-color);
            transition: color 0.2s ease;
        }
        .copy-btn:hover {
            color: var(--bs-primary);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .songwriter-details-container {
                padding: 1rem;
            }
            .nav-pills-sm {
                flex-wrap: wrap;
            }
            .nav-pills-sm .nav-link {
                padding: 0.4rem 0.75rem;
                font-size: 0.85em;
            }
        }
    </style>
@endpush
