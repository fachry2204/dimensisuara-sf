<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

use App\Models\User\User;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->input('id') === $this->route('id') && Gate::allows('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $valid_docs = [];
        $user       = User::find($this->input('id'));
        if($user) {

            if($user->profile) {
                // Check the new registration type
                $type = $this->input('registrationtype');
                if($type == 'personal') {
                    // Become required if the user profile previously doesn't have the required document
                    $valid_docs = [
                        'ktp'           => ($user->profile->ktp          ? 'nullable' : 'required') . '|file|mimes:jpeg,jpg,png,pdf',
                        'npwppersonal'  => ($user->profile->npwppersonal ? 'nullable' : 'required') . '|file|mimes:jpeg,jpg,png,pdf',
                    ];
                }
                else {
                    $valid_docs = [
                        'ktp'           => ($user->profile->ktp          ? 'nullable' : 'required') . '|file|mimes:jpeg,jpg,png,pdf',
                        'nibcompany'    => ($user->profile->nibcompany   ? 'nullable' : 'required') . '|file|mimes:jpeg,jpg,png,pdf',
                        'npwpcompany'   => ($user->profile->npwpcompany  ? 'nullable' : 'required') . '|file|mimes:jpeg,jpg,png,pdf',
                    ];
                }

                $valid = [
                    // Normal string validation
                    'companyname'   => 'required_if:registrationtype,company|nullable|string',
                    'firstname'     => 'required|string|max:100',
                    'lastname'      => 'nullable|string|max:100',
                    'email'         => 'required|string|email|max:50|unique:users,email,' . $user->id,
                    'phone'         => 'required|string|max:20',
                    'address1'      => 'required|string|max:255',
                    'address2'      => 'nullable|string|max:255',

                    'city'          => 'required_unless:country,ID|string|max:100',
                    'state'         => 'required_unless:country,ID|string|max:100',
                    'provinceid'    => 'required_if:country,ID|string|max:15',
                    'cityid'        => 'required_if:country,ID|string|max:15',
                    'districtid'    => 'required_if:country,ID|string|max:15',
                    'villageid'     => 'required_if:country,ID|string|max:15',

                    'zip'           => 'required|string|max:20',
                    'country'       => 'required|string|max:10',
                    'bankname'      => 'required|string|max:100',
                    'bankbranch'    => 'required|string|max:100',
                    'accountname'   => 'required|string|max:100',
                    'accountnumber' => 'required|string|max:50',
                    
                    // Checkboxes, radio buttons, and canvas validation
                    'registrationtype'      => 'required|string|in:personal,company',
                ];
                return array_merge($valid, $valid_docs);
            }
            else {
                return [
                    'name'     => 'required|string|max:100',
                    'email'         => 'required|string|email|max:50',
                ];
            }
        }
    }
}
