@php
    /**
     * $user
     * $type
     * $profileupdate,
     * $profileidentityupdate,
     * $profileidentitydelete,
     * $profilecontractretrieve
     **/
@endphp

@if($user->profile)
    <div class="card">
        <div class="card-header p-3">
            <div class="d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-bold">Customer Profile</h6>
                @if($user->profile->status == 'verified')
                    <span class="badge bg-success text-dark">Verified</span>
                @elseif($user->profile->status == 'pending')
                    <span class="badge bg-warning text-dark">Pending</span>
                @elseif($user->profile->status == 'blocked')
                    <span class="badge bg-danger text-light">Blocked</span>
                @else
                    <span class="badge bg-grd-royal">Unknown</span>
                @endif
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route($profileupdate, $user->id) }}" method="POST" id="user-form">
                @csrf
                @method('put')
                <fieldset>
                    <h4 class="mb-3">Registration Type</h4>
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            @php
                                $personal = $company = '';
                                if (old('registrationtype')) {
                                    $personal = old('registrationtype') == 'personal' ? 'checked' : '';
                                    $company = old('registrationtype') == 'company' ? 'checked' : '';
                                } else {
                                    $personal = $user->profile->is_company ? '' : 'checked';
                                    $company = $user->profile->is_company ? 'checked' : '';
                                }
                            @endphp
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="registrationtype" value="personal"
                                    id="registrationtype-personal" {{ $personal }} required>
                                <label class="form-check-label" for="registrationtype-personal">Daftar sebagai
                                    personal</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="registrationtype" value="company"
                                    id="registrationtype-company" {{ $company }}>
                                <label class="form-check-label" for="registrationtype-company">Daftar sebagai
                                    perusahaan</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 mt-5">
                        <h4>Company Information</h4>
                        <p>Please fill in the company information if you are registering as a company.</p>
                    </div>
                    <fieldset class="company-only">
                        <div class="row g-3 mb-3">
                            <div class="col-lg-6">
                                <label for="companyname" class="form-label">Nama Perusahaan <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="companyname" name="companyname"
                                    value="{{ old('companyname', $user->profile->companyname ?? '') }}" required>
                            </div>
                            <div class="col-lg-6">
                                <label for="nibcompany_num" class="form-label">Nomor Induk Berusaha (NIB) <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="nibcompany_num"
                                    id="nibcompany_num"
                                    value="{{ old('nibcompany_num', $user->profile->nibcompany_num ?? '') }}" required>
                            </div>
                            <div class="col-lg-6">
                                <label for="npwpcompany_num" class="form-label">Nomor Pokok Wajib Pajak (NPWP) Perusahaan
                                    <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="npwpcompany_num"
                                    id="npwpcompany_num"
                                    value="{{ old('npwpcompany_num', $user->profile->npwpcompany_num ?? '') }}" required>
                            </div>
                            <div class="col-lg-6">
                                <label for="position" class="form-label">Jabatan PIC <span
                                        class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="position" id="position" required>
                                    <option value="" disabled selected>Pilih Jabatan</option>
                                    <option value="direktur_utama" {{ old('position', $user->profile->position ?? '') == 'direktur_utama' ? 'selected' : '' }}>Direktur Utama</option>
                                    <option value="direktur" {{ old('position', $user->profile->position ?? '') == 'direktur' ? 'selected' : '' }}>Direktur</option>
                                </select>
                            </div>
                        </div>
                    </fieldset>
                    <div class="mb-3 mt-5">
                        <h4>PIC Information</h4>
                        <p>Contact person information.</p>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-lg-6">
                            <label for="firstname" class="form-label">Nama Depan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="firstname" id="firstname"
                                autocomplete="on" value="{{ old('firstname', $user->profile->firstname ?? '') }}" required>
                        </div>
                        <div class="col-lg-6">
                            <label for="lastname" class="form-label">Nama Belakang</label>
                            <input type="text" class="form-control form-control-sm" name="lastname" id="lastname"
                                autocomplete="on" value="{{ old('lastname', $user->profile->lastname ?? '') }}">
                        </div>
                        <div class="col-lg-6">
                            <label for="email" class="form-label">Alamat Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control form-control-sm" name="email" id="email"
                                autocomplete="on" value="{{ old('email', $user->email ?? '') }}" disabled>
                        </div>
                        <div class="col-lg-6">
                            <label for="phone" class="form-label">Nomor Handphone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="phone" id="phone"
                                autocomplete="on" oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                                inputmode="numeric" value="{{ old('phone', $user->profile->phone ?? '') }}" required>
                        </div>
                        <div class="col-lg-6">
                            <label for="ktp_num" class="form-label">Nomor Induk Kependudukan (NIK) <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="ktp_num" id="ktp_num"
                                autocomplete="on" value="{{ old('ktp_num', $user->profile->ktp_num ?? '') }}" required>
                        </div>
                        <div class="col-lg-6">
                            <fieldset class="personal-only">
                                <label for="npwppersonal_num" class="form-label">Nomor Pokok Wajib Pajak (NPWP) Pribadi
                                    <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="npwppersonal_num"
                                    id="npwppersonal_num" autocomplete="on"
                                    value="{{ old('npwppersonal_num', $user->profile->npwppersonal_num ?? '') }}" required>
                            </fieldset>
                        </div>
                        <div class="col-12">
                        </div>
                    </div>
                    <div class="mb-3 mt-5">
                        <h4>Address Information</h4>
                        <p>Enter your address based on your KTP or your company address.</p>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-lg-6">
                            <label for="country" class="form-label">Negara <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="country" id="country"
                                aria-label="Default select example" autocomplete="on"
                                value="{{ old('country', $user->profile->country ?? '') }}" required>
                                <option disabled value="">Pilih Negara</option>
                                @foreach (Monarobase\CountryList\CountryListFacade::getList() as $code => $country)
                                    <option value="{{ $code }}" {{ $code == old('country', $user->profile->country ?? '') ? 'selected' : '' }}>{{ $country }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <fieldset class="row g-3 mb-3" id="automatic">
                        <div class="col-lg-6">
                            <label for="provinceid" class="form-label">Provinsi <span class="text-danger">*</span></label>
                            <select class="form-control form-control-sm select2" name="provinceid" id="provinceid"
                                data-value="{{ old('provinceid', $user->profile->provinceid ?? '') }}" required></select>
                        </div>

                        <div class="col-lg-6">
                            <label for="cityid" class="form-label">Kabupaten/Kota <span class="text-danger">*</span></label>
                            <select class="form-control form-control-sm select2" name="cityid" id="cityid"
                                data-value="{{ old('cityid', $user->profile->cityid ?? '') }}" required></select>
                        </div>

                        <div class="col-lg-6">
                            <label for="districtid" class="form-label">Kecamatan <span class="text-danger">*</span></label>
                            <select class="form-control form-control-sm select2" name="districtid" id="districtid"
                                data-value="{{ old('districtid', $user->profile->districtid ?? '') }}" required></select>
                        </div>

                        <div class="col-lg-6">
                            <label for="villageid" class="form-label">Kelurahan <span class="text-danger">*</span></label>
                            <select class="form-control form-control-sm select2" name="villageid" id="villageid"
                                data-value="{{ old('villageid', $user->profile->villageid ?? '') }}" required></select>
                        </div>

                        <div class="col-lg-6">
                            <label for="zip" class="form-label">Kode Pos <span class="text-danger">*</span></label>
                            <select class="form-control form-control-sm select2" name="zip" id="postcode"
                                data-value="{{ old('zip', $user->profile->zip ?? '') }}" required></select>
                        </div>
                    </fieldset>
                    <fieldset class="row g-3 mb-3" id="manual">
                        <div class="col-lg-6">
                            <label for="state" class="form-label">Provinsi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="state" id="state"
                                autocomplete="on" value="{{ old('state', $user->profile->state ?? '') }}" required>
                        </div>

                        <div class="col-lg-6">
                            <label for="city" class="form-label">Kota <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="city" id="city" autocomplete="on"
                                value="{{ old('city', $user->profile->city ?? '') }}" required>
                        </div>

                        <div class="col-lg-6">
                            <label for="zip" class="form-label">Kode Pos <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="zip" id="zip" autocomplete="on"
                                value="{{ old('zip', $user->profile->zip ?? '') }}" required>
                        </div>
                    </fieldset>
                    <div class="row g-3 mb-3">
                        <div class="col-lg-6">
                            <label for="address1" class="form-label">Alamat Baris 1 <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="address1" id="address1"
                                autocomplete="on" value="{{ old('address1', $user->profile->address1 ?? '') }}" required>
                        </div>
                        <div class="col-lg-6">
                            <label for="address2" class="form-label">Alamat Baris 2</label>
                            <input type="text" class="form-control form-control-sm" name="address2" id="address2"
                                autocomplete="on" value="{{ old('address2', $user->profile->address2 ?? '') }}">
                        </div>

                    </div>

                    <div class="mb-3 mt-5">
                        <h4>Bank Account Information</h4>
                        <p>Please fill in the bank account information for withdrawal purpose.</p>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-lg-6">
                            <label for="bankname" class="form-label">Nama Bank <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="bankname" id="bankname"
                                value="{{ old('bankname', $user->profile->bankname ?? '') }}" required>
                        </div>
                        <div class="col-lg-6">
                            <label for="bankbranch" class="form-label">Cabang Bank <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="bankbranch" id="bankbranch"
                                value="{{ old('bankbranch', $user->profile->bankbranch ?? '') }}" required>
                        </div>
                        <div class="col-lg-6">
                            <label for="accountname" class="form-label">Nama Pemilik Rekening <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="accountname" id="accountname"
                                value="{{ old('accountname', $user->profile->accountname ?? '') }}" required>
                        </div>
                        <div class="col-lg-6">
                            <label for="accountnumber" class="form-label">Nomor Rekening <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="accountnumber" id="accountnumber"
                                value="{{ old('accountnumber', $user->profile->accountnumber ?? '') }}" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>

                </fieldset>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header p-3">
            <h6 class="card-title mb-0 fw-bold">Identity Documents</h6>
        </div>
        <div class="card-body">
            <div class="row">
                @if ($user->profile->identities->count() > 0)
                    @foreach ($required as $req)
                        @php
                            $identity = $user->profile->identities->where('type', $req)->first();
                        @endphp
                        @if ($identity)
                            @if(Str::startsWith($identity->mime_type, 'image/'))
                                <div class="col-md-3 p-2 text-center">
                                    <a href="{{ $identity->url }}" target="_blank">
                                        <img alt="{{ $identity->type }}" class="document d-none rounded-4" id="{{ $identity->type }}"
                                            data-url="{{ $identity->url }}" @if($identity->type == 'signature')
                                            style="background-color: #ffffff" @endif>
                                        <div class="document" id="{{ $identity->type }}-spinner">
                                            <div class="spinner-grow " style="width: 3rem; height: 3rem;">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                        <div class="" style="height: 50px">
                                            <span class="text-muted text-sm text-center">{{ $identitymap[$identity->type] }}</span>
                                        </div>
                                    </a>
                                </div>
                            @else
                                <div class="col-md-3 p-2 text-center">
                                    <a href="{{ $identity->url }}" target="_blank">
                                        <div class="document flex-column justify-content-end rounded-4" id="{{ $identity->type }}-spinner"
                                            style="border: 2px solid #FF8C00">
                                            <div class="d-flex flex-grow-1">
                                                <i class="material-icons-outlined" style="margin: auto; font-size: 6.5rem">file_present</i>
                                            </div>
                                            <span class="text-muted text-sm text-center p-2 w-100"
                                                style="background-color: #FF8C00; border-radius:0px 0px 10px 10px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                                                title="{{ $identity->original_name }}">{{ $identity->original_name }}</span>
                                        </div>
                                        <div class="" style="height: 50px">
                                            <span class="text-muted text-sm text-center">{{ $identitymap[$identity->type] }}</span>
                                        </div>
                                    </a>
                                </div>
                            @endif
                        @endif
                    @endforeach
                @else
                    <div class="d-flex align-items-center justify-content-center w-100" style="height: 200px">
                        <h6 class="text-muted">No documents found</h6>
                    </div>
                @endif
                <div>
                    <strong>Action:</strong>
                    <form action="{{ route($profileidentityupdate) }}" id="identity-form" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('put')
                        <input type="file" name="file" id="identity-upload-file" accept=".jpg, .jpeg, .png, .pdf"
                            class="d-none" maxlength="5242880">
                        <input type="hidden" name="type" id="identity-upload-type">
                        <input type="hidden" name="userid" value="{{ $user->id }}">
                    </form>
                    <table class="table-borderless">
                        <tbody>
                            @foreach ($required as $doc)
                                <tr>
                                    <td class="pe-2">{{ $identitymap[$doc] }}</td>
                                    <td class="pe-2">
                                        @if ($doc == 'signature')
                                            <a href="javascript:void(0)" data-bs-toggle="modal"
                                                data-bs-target="#signature-modal">Upload</a>
                                        @else
                                            <a href="javascript:void(0)" class="identity-upload-btn"
                                                data-type="{{ $doc }}">Upload</a>
                                        @endif
                                    </td>
                                    @if($type == 'admin')
                                        <td>
                                            <form action="{{ route($profileidentitydelete) }}" method="POST"
                                                id="identity-delete-form-{{ $doc }}">
                                                @csrf
                                                @method('delete')
                                                <input type="hidden" name="userid" id="userid" value="{{ $user->id }}">
                                                <input type="hidden" name="type" id="type" value="{{ $doc }}">
                                                <button type="submit" class="btn p-0 text-danger">Delete</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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
                    <form action="{{ route($profileidentityupdate) }}" id="signature-form" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('put')
                        <p>You can <label for="signature-upload" class="text-primary" style="cursor: pointer">upload a
                                digital signature image</label> or directly sign below</p>
                        <input type="file" class="form-control" name="signature-upload" id="signature-upload"
                            accept=".jpg, .jpeg, .png" style="display: none;">
                        <input type="hidden" name="signature" id="signature">
                        <input type="hidden" name="type" value="signature">
                        <input type="hidden" name="userid" value="{{ $user->id }}">
                        <div class="d-flex flex-column justify-content-center">
                            <canvas id="sig-canvas" class="my-2 mx-auto" width="450" height="160"
                                style="border: 1px solid #000000;border-radius: 15px;cursor: crosshair; display: block; background-color: #FFFFFF">
                                {{__('Your browser doesn\'t support canvas. Please update your browser.')}}
                            </canvas>
                            <button type="button" type="button" class="btn btn-secondary mx-auto" style="width: fit-content"
                                id="sig-clearBtn">{{__('Clear Signature')}}</button>
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
    </style>
@endpush
@push('script')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ URL::asset('build/js/canvas.js') }}" type="text/javascript"></script>
    <script>
        function loadImageAsync(target) {
            if (!$(target).data('url')) {
                return;
            }
            const url = $(target).data('url');
            const downloadingImage = new Image();
            downloadingImage.crossOrigin = "anonymous";
            downloadingImage.onload = function () {
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

        function registrationtypeRadio() {
            var value = $('input[name="registrationtype"]:checked').val()
            if (value == 'company') {
                $('.company-only').prop('required', true);
                $('.company-only').prop('disabled', false);

                $('.personal-only').prop('required', false);
                $('.personal-only').prop('disabled', true);
            }
            else {
                $('.company-only').prop('required', false);
                $('.company-only').prop('disabled', true);

                $('.personal-only').prop('required', true);
                $('.personal-only').prop('disabled', false);
            }
        }

        function signatureUpload() {
            let file = document.getElementById('signature-upload').files[0];
            if (file) {
                let reader = new FileReader();
                reader.onload = function (e) {
                    let image = new Image();
                    image.src = e.target.result;
                    image.onload = function () {
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
            $('#signature-upload').val('');
        }

        $(document).ready(function () {
            var documents = ['#ktp', '#npwppersonal', '#npwpcompany', '#nibcompany', '#deed', '#establishment', '#signature'];
            documents.forEach(document => {
                loadImageAsync(document)
            });
            registrationtypeRadio();

            $('input[name="registrationtype"]').on('change', function () {
                registrationtypeRadio();
            });

            $('#signature-upload').on('change', function () {
                signatureUpload();
            });

            $('.identity-upload-btn').on('click', function () {
                $('#identity-upload-type').val($(this).data('type'));
                $('#identity-upload-file').click();
            });

            $('#identity-upload-file').on('change', function () {
                $('#identity-form').submit();
            });

            $('#signature-form').on('submit', function (e) {
                e.preventDefault();

                // Convert canvas to image
                $('input[name="signature"]').val(document.getElementById('sig-canvas').toDataURL('image/png'));

                // // Submit form
                this.submit();
            });

            // COUNTRY
            function countryChange() {
                if ($('#country').val() == 'ID') {
                    $('#automatic').removeClass('d-none');
                    $('#automatic').removeAttr('disabled');
                    $('#manual').addClass('d-none');
                    $('#manual').prop('disabled', true);

                    ['#provinceid', '#cityid', '#districtid', '#villageid', '#postcode'].forEach(element => {
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
            $('#country').on('change', function () {
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
            $('#provinceid').on('change', function () {
                const code = $(this).val();
                $('#district').empty(); // Clear lower level too
                loadSelect('#cityid', '/regions/children', { parent_code: code }, $('#cityid').data('value'));
            });

            // City → District
            $('#cityid').on('change', function () {
                const code = $(this).val();
                loadSelect('#districtid', '/regions/children', { parent_code: code }, $('#districtid').data('value'));
            });

            // District → Village
            $('#districtid').on('change', function () {
                const code = $(this).val();
                loadSelect('#villageid', '/regions/children', { parent_code: code }, $('#villageid').data('value'));
            });

            // Village → Postcode
            $('#villageid').on('change', function () {
                const code = $(this).val();
                loadSelect('#postcode', '/regions/postcode', { parent_code: code }, $('#postcode').data('value'));
            });

        });
    </script>
@endpush