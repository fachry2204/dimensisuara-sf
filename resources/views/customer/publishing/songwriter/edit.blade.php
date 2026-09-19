@extends('layouts.app')
@section('title')
@if ($songwriter->id) Edit @else New @endif Songwriter
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('publishing.songwriters.index') }}">Publishing</a></li>
                    <li class="breadcrumb-item active" aria-current="page"></li>
                </ol>
            </nav>
            <h4 class="mb-0 font-weight-bold fs-2">@if ($songwriter->id) Edit @else New @endif Songwriter</h4>
        </div>
    </div>
@endsection
@section('content')
    <form action="@if ($songwriter->id) {{ route('publishing.songwriters.update', $songwriter->id) }} @else {{ route('publishing.songwriters.store') }} @endif" method="POST" id="user-form" enctype="multipart/form-data">
        @csrf
        @method($songwriter->id ? 'PUT' : 'POST')
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header p-3">
                        <h6 class="card-title mb-0 fw-bold">Personal Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-lg-6">
                                <label for="firstname" class="form-label">First Name <span class="text-danger">*</span></label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control form-control-sm" name="firstname" id="firstname" autocomplete="on" value="{{ old('firstname', $songwriter->firstname ?? '') }}" required>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-lg-6">
                                <label for="lastname" class="form-label">Last Name</label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control form-control-sm" name="lastname" id="lastname" autocomplete="on" value="{{ old('lastname', $songwriter->lastname ?? '') }}">
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-lg-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            </div>
                            <div class="col-lg-6">
                                <input type="email" class="form-control form-control-sm" name="email" id="email" autocomplete="on" value="{{ old('email', $songwriter->email ?? '') }}" required>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-lg-6">
                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control form-control-sm" name="phone" id="phone" autocomplete="on" oninput="this.value = this.value.replace(/[^0-9]/g, '');" inputmode="numeric" value="{{ old('phone', $songwriter->phone ?? '') }}" required>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-lg-6">
                                <label for="ktp_num" class="form-label">Citizen ID Number (KTP/NIK) <span class="text-danger">*</span></label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control form-control-sm" name="ktp_num" id="ktp_num" autocomplete="on" value="{{ old('ktp_num', $songwriter->ktp_num ?? '') }}" required>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-lg-6">
                                <label for="npwppersonal_num" class="form-label">Personal Tax ID Number (NPWP Pribadi) <span class="text-danger">*</span></label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control form-control-sm" name="npwppersonal_num" id="npwppersonal_num" autocomplete="on" value="{{ old('npwppersonal_num', $songwriter->npwppersonal_num ?? '') }}" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-warning" role="alert">
                    <i>These fields below are optional. You may complete them or let the songwriter fill them in themselves.</i>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header p-3">
                        <h6 class="card-title mb-0 fw-bold">Address</h6>
                    </div>
                    <div class="card-body">
                        <fieldset>
                            <div class="row mb-2">
                                <div class="col-lg-6">
                                    <label for="country" class="form-label">Country </label>
                                </div>
                                <div class="col-lg-6">
                                    <select class="form-select form-select-sm" name="country" id="country" autocomplete="on">
                                        <option disabled value="">-- Choose --</option>
                                        @foreach (Monarobase\CountryList\CountryListFacade::getList() as $code => $country)
                                            <option value="{{ $code }}" {{ $code == old('country', $songwriter->country ?? 'ID') ? 'selected' : '' }}>{{ $country }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <fieldset id="automatic">
                                <div class="row mb-2">
                                    <div class="col-lg-6">
                                        <label for="provinceid" class="form-label">State </label>
                                    </div>
                                    <div class="col-lg-6">
                                        <select class="form-control form-control-sm select2" name="provinceid" id="provinceid" data-value="{{ old('provinceid', $songwriter->provinceid ?? '') }}">
                                            <option disabled value="">-- Choose --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-lg-6">
                                        <label for="cityid" class="form-label">City (Kabupaten/Kota) </label>
                                    </div>
                                    <div class="col-lg-6">
                                        <select class="form-control form-control-sm select2" name="cityid" id="cityid" data-value="{{ old('cityid', $songwriter->cityid ?? '') }}">
                                            <option disabled value="">-- Choose --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-lg-6">
                                        <label for="districtid" class="form-label">District (Kecamatan) </label>
                                    </div>
                                    <div class="col-lg-6">
                                        <select class="form-control form-control-sm select2" name="districtid" id="districtid" data-value="{{ old('districtid', $songwriter->districtid ?? '') }}">
                                            <option disabled value="">-- Choose --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-lg-6">
                                        <label for="villageid" class="form-label">Village (Kelurahan) </label>
                                    </div>
                                    <div class="col-lg-6">
                                        <select class="form-control form-control-sm select2" name="villageid" id="villageid" data-value="{{ old('villageid', $songwriter->villageid ?? '') }}">
                                            <option disabled value="">-- Choose --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-lg-6">
                                        <label for="zip2" class="form-label">Postal Code </label>
                                    </div>
                                    <div class="col-lg-6">
                                        <select class="form-control form-control-sm select2" name="zip2" id="zip2" autocomplete="off" data-value="{{ old('zip2', $songwriter->zip ?? '') }}">
                                            <option disabled value="">-- Choose --</option>
                                        </select>
                                    </div>
                                </div>
                            </fieldset>
                            <fieldset id="manual">
                                <div class="row mb-2">
                                    <div class="col-lg-6">
                                        <label for="state" class="form-label">State </label>
                                    </div>
                                    <div class="col-lg-6">
                                        <input class="form-control form-control-sm" name="state" id="state" value="{{ old('state', $songwriter->state ?? '') }}">
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-lg-6">
                                        <label for="city" class="form-label">City </label>
                                    </div>
                                    <div class="col-lg-6">
                                        <input class="form-control form-control-sm" name="city" id="city" value="{{ old('city', $songwriter->city ?? '') }}">
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-lg-6">
                                        <label for="zip" class="form-label">Postal Code </label>
                                    </div>
                                    <div class="col-lg-6">
                                        <input class="form-control form-control-sm" name="zip" id="zip" value="{{ old('zip', $songwriter->zip ?? '') }}">
                                    </div>
                                </div>
                            </fieldset>
                            <div class="row mb-2">
                                <div class="col-lg-6">
                                    <label for="address1" class="form-label">Address 1 </label>
                                </div>
                                <div class="col-lg-6">
                                    <input type="text" class="form-control form-control-sm" name="address1" id="address1" autocomplete="on" value="{{ old('address1', $songwriter->address1 ?? '') }}">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-lg-6">
                                    <label for="address2" class="form-label">Address 2</label>
                                </div>
                                <div class="col-lg-6">
                                    <input type="text" class="form-control form-control-sm" name="address2" id="address2" autocomplete="on" value="{{ old('address2', $songwriter->address2 ?? '') }}">
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header p-3">
                        <h6 class="card-title mb-0 fw-bold">Bank Details</h6>
                    </div>
                    <div class="card-body">
                        <fieldset>
                            <div class="row mb-2">
                                <div class="col-lg-6">
                                    <label for="bankname" class="form-label">Bank Name </label>
                                </div>
                                <div class="col-lg-6">
                                    <input type="text" class="form-control form-control-sm" name="bankname" id="bankname" value="{{ old('bankname', $songwriter->bankname ?? '') }}">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-lg-6">
                                    <label for="bankbranch" class="form-label">Bank Branch </label>
                                </div>
                                <div class="col-lg-6">
                                    <input type="text" class="form-control form-control-sm" name="bankbranch" id="bankbranch" value="{{ old('bankbranch', $songwriter->bankbranch ?? '') }}">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-lg-6">
                                    <label for="accountname" class="form-label">Account Name </label>
                                </div>
                                <div class="col-lg-6">
                                    <input type="text" class="form-control form-control-sm" name="accountname" id="accountname" value="{{ old('accountname', $songwriter->accountname ?? '') }}">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-lg-6">
                                    <label for="accountnumber" class="form-label">Account Number </label>
                                </div>
                                <div class="col-lg-6">
                                    <input type="text" class="form-control form-control-sm" name="accountnumber" id="accountnumber" value="{{ old('accountnumber', $songwriter->accountnumber ?? '') }}">
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
@endsection
@push('modals')
    <div class="modal fade" id="confirm-delete-document-modal" tabindex="-1" aria-labelledby="confirm-delete-document-modal-title" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="document-remove-form">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirm-delete-document-modal-title">Confirm Delete Document</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this document?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="confirm-delete-document-btn">Delete Document</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endpush
@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
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
            object-fit: cover
        }
        [data-bs-theme="dark"] .select2.select2-container.select2-container--default.select2-container--below, [data-bs-theme="dark"] .select2.select2-container.select2-container--default {
            width: 100% !important;
        }
        [data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--bs-body-color);
        }
        [data-bs-theme="dark"] .select2-dropdown, [data-bs-theme="dark"] .caption-bg {
            background-color: black;
            color: var(--bs-body-color);
        }
        [data-bs-theme="semi-dark"] .caption-bg {
            background-color: white;
        }

        [data-bs-theme="dark"] .select2-container--default .select2-results__option--selected{
            background-color: grey
        }

        [data-bs-theme="dark"] .select2-container--default .select2-selection--single {
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            border-color: var(--bs-border-color);
            border-radius: var(--bs-border-radius-sm);
        }
    </style>
@endpush
@push('script')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script type="application/json" id="json-countries">@json( Monarobase\CountryList\CountryListFacade::getList() )</script>
    <script type="application/json" id="json-old">@json( old() ?? [] )</script>
    <script>
        $(document).ready(function() {

            // COUNTRY
            function countryChange() {
                if($('#country').val() == 'ID') {
                    $('#automatic').removeClass('d-none');
                    $('#automatic').removeAttr('disabled');
                    $('#manual').addClass('d-none');
                    $('#manual').prop('disabled', true);

                    ['#provinceid', '#cityid', '#districtid', '#villageid', '#zip2'].forEach(element => {
                        var $el = $(element);
                        if ($el.is(':visible')) {
                            $el.select2();
                        }
                    });
                    
                } else {
                    $('#automatic').addClass('d-none');
                    $('#automatic').prop('disabled', true);
                    $('#manual').removeClass('d-none');
                    $('#manual').removeAttr('disabled');
                }
            }
            countryChange();
            $('#country').on('change', function() {
                countryChange();
            });

            // SELECT2
            function loadSelect(selector, url, params = {}, selectedValue = null) {
                $.get(url, params, function (data) {
                    const $el = $(selector);

                    // Clear options, rebuild
                    $el.empty().append('<option value="">-- Choose --</option>');

                    data.forEach(item => {
                        $el.append(`<option value="${item.id}">${item.name}</option>`);
                    });

                    // Set selected value if provided
                    if (selectedValue) {
                        $el.val(selectedValue);
                    }

                    $el.trigger('change'); // tell Select2 to update
                });
            }

            $('.select2').select2();

            // Load provinces on page load
            loadSelect('#provinceid', '/regions/provinces', {}, $('#provinceid').data('value'));

            // Province → City
            $('#provinceid').on('change', function() {
                const code = $(this).val();
                $('#district').empty(); // Clear lower level too
                loadSelect('#cityid', '/regions/children', { parent_code: code }, $('#cityid').data('value'));
            });

            // City → District
            $('#cityid').on('change', function() {
                const code = $(this).val();
                loadSelect('#districtid', '/regions/children', { parent_code: code }, $('#districtid').data('value'));
            });

            // District → Village
            $('#districtid').on('change', function() {
                const code = $(this).val();
                loadSelect('#villageid', '/regions/children', { parent_code: code }, $('#villageid').data('value'));
            });

            // Village → Postcode
            $('#villageid').on('change', function() {
                const code = $(this).val();
                loadSelect('#zip2', '/regions/postcode', { parent_code: code }, $('#zip2').data('value'));
            });

        });
    </script>
@endpush
