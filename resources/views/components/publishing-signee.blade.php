@props([
    'displayAgreement' => true,
    'signee' => null
])
    
<div>
    <div class="nav nav-pills mb-3" id="pills-tab" role="tablist">
        <button class="nav-link active" id="pills-tab-details" data-bs-toggle="pill" data-bs-target="#pills-details" type="button" role="tab" aria-controls="pills-details" aria-selected="true">Details</button>
        <button class="nav-link" id="pills-tab-songwriter" data-bs-toggle="pill" data-bs-target="#pills-songwriter" type="button" role="tab" aria-controls="pills-songwriter" aria-selected="false">Songwriter Data</button>
    </div>
    <div class="tab-content" id="pills-tabContent">
        <div class="tab-pane fade show active" id="pills-details" role="tabpanel" aria-labelledby="pills-tab-details">
            @include('components.publishing', [
                'publishing' => $signee->publishing,
                'allowEdit' => false,
                'allowGenerateContract' => false,
                'resendRoute' => null
            ])

            @if($displayAgreement)
                <div class="mt-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="accept_terms" id="accept_terms" @if($signee->accept_terms) checked @endif disabled>
                        <label class="form-check-label" for="accept_terms">
                            <i>In the event the Author/Composer terminates the collaboration agreement prior to the expiration of the agreement term, such termination shall become effective <strong>one (1) year</strong> after the submission of the termination request by the Author/Composer, provided that it has been approved by all parties (<strong>Post Term Period</strong> applies to this agreement)</i>.
                        </label>
                    </div>
                </div>
            @endif
        </div>
        <div class="tab-pane fade" id="pills-songwriter" role="tabpanel" aria-labelledby="pills-tab-songwriter">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong class="d-block mb-2 text-primary">Personal & Publishing Details</strong>
                        <div class="mb-2"><strong>Name:</strong> {{ $signee->full_name }}</div>
                        <div class="mb-2"><strong>Email:</strong> {{ $signee->email }}</div>
                        @if($signee->ktp_num)<div class="mb-2"><strong>NIK:</strong> {{ $signee->ktp_num }}</div>@endif
                        @if($signee->phone)<div class="mb-2"><strong>Phone:</strong> {{ $signee->phone }}</div>@endif
                        <div class="mb-2"><strong>Role:</strong> {{ $signee->role_name }}</div>
                        <div class="mb-2"><strong>Share:</strong> {{ $signee->share }}%</div>
                        @if($signee->publisher)<div class="mb-2"><strong>Publisher:</strong> {{ $signee->publisher }}</div>@endif
                        @if($signee->lmk)<div class="mb-2"><strong>LMK:</strong> {{ $signee->lmk }}</div>@endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div>
                        <strong class="d-block mb-2 text-primary">Signature</strong>
                        @if(!$signee->signature)
                            <div class="text-muted">None Provided</div>
                        @else
                            <div class="mt-2">
                                <a href="{{ route('sign.publishing.songwriter.documents.show', ['token' => $signee->token, 'file' => $signee->signature, 'hash' => sha1($signee->signature->id . '|' . $signee->signature->updated_at)]) }}" target="_blank" class="d-inline-block border rounded-3 p-2 bg-white">
                                    @if(Str::startsWith($signee->signature->mime_type, 'image/'))
                                        <img src="{{ route('sign.publishing.songwriter.documents.show', ['token' => $signee->token, 'file' => $signee->signature, 'hash' => sha1($signee->signature->id . '|' . $signee->signature->updated_at)]) }}" alt="Signature" style="max-height: 150px; max-width: 100%; display: block; object-fit: contain;">
                                    @else
                                        <div class="document flex-column justify-content-end rounded-4" style="border: 2px solid #FF8C00">
                                            <div class="d-flex flex-grow-1">
                                                <i class="material-icons-outlined" style="margin: auto; font-size: 4rem">file_present</i>
                                            </div>
                                            <span class="text-muted text-sm text-center p-2 w-100" style="background-color: #FF8C00; border-radius:0px 0px 10px 10px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $signee->signature->original_name }}">{{ $signee->signature->original_name }}</span>
                                        </div>
                                    @endif
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>