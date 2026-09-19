<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Normal string validation
            'companyname'   => 'required_if:registrationtype,company|nullable|string',
            'firstname'     => 'required|string|max:100',
            'lastname'      => 'nullable|string|max:100',
            'phone'         => 'required|string|max:20',
            'ktp_num'       => 'required|string|max:50',
            'address1'      => 'required|string|max:255',
            'address2'      => 'nullable|string|max:255',
            
            'city'          => 'nullable|required_unless:country,ID|string|max:100',
            'state'         => 'nullable|required_unless:country,ID|string|max:100',
            'provinceid'    => 'nullable|required_if:country,ID|string|max:15',
            'cityid'        => 'nullable|required_if:country,ID|string|max:15',
            'districtid'    => 'nullable|required_if:country,ID|string|max:15',
            'villageid'     => 'nullable|required_if:country,ID|string|max:15',

            'zip'           => 'required|string|max:20',
            'country'       => 'required|string|max:10',
            'bankname'      => 'required|string|max:100',
            'bankbranch'    => 'required|string|max:100',
            'accountname'   => 'required|string|max:100',
            'accountnumber' => 'required|string|max:50',

            'nibcompany_num'    => 'required_if:registrationtype,company|nullable|string|max:50',
            'npwpcompany_num'   => 'required_if:registrationtype,company|nullable|string|max:50',
            'npwppersonal_num'  => 'required_if:registrationtype,personal|nullable|string|max:50',
            'position'          => 'required_if:registrationtype,company|nullable|string|max:50',
            
            // Checkboxes, radio buttons, and canvas validation
            'registrationtype'      => 'required|string|in:personal,company',
        ];
    }

    
    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'companyname'   => 'Company Name',
            'firstname'     => 'First Name',
            'lastname'      => 'Last Name',
            'phone'         => 'Phone Number',
            'ktp_num'       => 'KTP Number',
            'address1'      => 'Address 1',
            'address2'      => 'Address 2',
            'city'          => 'City',
            'state'         => 'State',
            'provinceid'    => 'Provinsi',
            'cityid'        => 'Kabupaten/Kota',
            'districtid'    => 'Kecamatan',
            'villageid'     => 'Kelurahan',
            'zip'           => 'Zip Code',
            'country'       => 'Country',
            'bankname'      => 'Bank Name',
            'bankbranch'    => 'Bank Branch',
            'accountname'   => 'Account Name',
            'accountnumber' => 'Account Number',
            'nibcompany_num'    => 'NIB Company Number',
            'npwpcompany_num'   => 'NPWP Company Number',
            'npwppersonal_num'  => 'NPWP Personal Number',
            'position'          => 'Position',
            'registrationtype'  => 'Registration Type',
        ];
    }
}
