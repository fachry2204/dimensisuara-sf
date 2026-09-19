# Comprehensive Implementation Plan - Publishing Improvements

This plan outlines the required updates to correct the metadata source state, translate language labels, resolve 403 signature image display issues, and add songwriter details into the accordion views.

## Use Case Impact

AFFECTED USE CASES:
- MODIFIED:
  - Create Publishing Request (form metadata source state & language review display)
  - Show Publishing Request (songwriter accordion details display)
  - Guest Songwriter Signing Page (view-only signature display with hash validation)
- NEW: (none)
- REMOVED: (none)

---

## Proposed Changes

### Component 1: Song Publishing Form (Creation/Edit)

#### [MODIFY] [create.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/customer/publishing/create.blade.php)

##### Task 1.1: Correct metadata source initialization
Update line 497:
```diff
-                source: @json(old('source', $publishing->source ?? 'existing')),
+                source: @json(old('source', $publishing->id ? ($publishing->release_id ? 'existing' : 'new') : 'existing')),
```

##### Task 1.2: Add Symfony Languages mapping to javascript
Insert the JSON translations object in the Blade template inputs:
```html
    <script type="application/json" id="json-languages">@json(Symfony\Component\Intl\Languages::getNames())</script>
```
Register it in the Alpine.js store under properties:
```javascript
                languages: JSON.parse(document.getElementById('json-languages').textContent),
```

##### Task 1.3: Use translated languages in Step 4 Review Summary
Update line 402:
```diff
-                                    <td><span x-text="$store.publishing.language || 'Not provided'"
+                                    <td><span x-text="($store.publishing.languages && $store.publishing.language ? ($store.publishing.languages[$store.publishing.language.toUpperCase()] || $store.publishing.languages[$store.publishing.language.toLowerCase()] || $store.publishing.language) : 'Not provided')"
```

---

### Component 2: Guest Signature Display

#### [MODIFY] [publishing-signee.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/components/publishing-signee.blade.php)

##### Task 2.1: Add hash validation parameter to signature file URLs
Update lines 46-48:
```diff
-                                <a href="{{ route('sign.publishing.songwriter.documents.show', ['token' => $signee->token, 'file' => $signee->signature]) }}" target="_blank" class="d-inline-block border rounded-3 p-2 bg-white">
-                                    @if(Str::startsWith($signee->signature->mime_type, 'image/'))
-                                        <img src="{{ route('sign.publishing.songwriter.documents.show', ['token' => $signee->token, 'file' => $signee->signature]) }}" alt="Signature" style="max-height: 150px; max-width: 100%; display: block; object-fit: contain;">
+                                <a href="{{ route('sign.publishing.songwriter.documents.show', ['token' => $signee->token, 'file' => $signee->signature, 'hash' => sha1($signee->signature->id . '|' . $signee->signature->updated_at)]) }}" target="_blank" class="d-inline-block border rounded-3 p-2 bg-white">
+                                    @if(Str::startsWith($signee->signature->mime_type, 'image/'))
+                                        <img src="{{ route('sign.publishing.songwriter.documents.show', ['token' => $signee->token, 'file' => $signee->signature, 'hash' => sha1($signee->signature->id . '|' . $signee->signature->updated_at)]) }}" alt="Signature" style="max-height: 150px; max-width: 100%; display: block; object-fit: contain;">
```

---

### Component 3: Songwriter Accordion Display

#### [MODIFY] [publishing.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/components/publishing.blade.php)

##### Task 3.1: Expand the detailed accordion content to render songwriter details
In the `$isDetailed = true` accordion container, update the collapse body layout:
```html
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
                                                        <div class="document flex-column justify-content-end rounded-4" style="border: 2px solid #FF8C00; max-height: 120px;">
                                                            <div class="d-flex flex-grow-1">
                                                                <i class="material-icons-outlined" style="margin: auto; font-size: 3rem">file_present</i>
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
                                                    @if ($signee->status == 'pending')
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
                                        @if($signee->contract)
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
```

---

## Testing & Verification

1. Run PHPUnit test suite to ensure existing publishing features pass:
   ```powershell
   php artisan test --filter=PublishingTest
   ```
2. Manually verify all modified flows (Default selection on edits, language labels translation in Step 4, 403-free guest signature viewing, and the expanded songwriter accordion details).

---

## Acceptance Criteria
- [ ] Correct source toggle selection defaults when editing manual vs existing release drafts.
- [ ] Symfony ISO-639-1 language codes mapped to names dynamically in the Step 4 Review summary of the form.
- [ ] Signed guest signature files display cleanly without triggering a 403 access error.
- [ ] Detailed songwriter accordion contains all personal details, shares, roles, and signatures in a clean 2-column layout.
