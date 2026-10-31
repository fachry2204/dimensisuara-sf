<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CreateAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('manage-admins');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->input('type') === 'new') {
            return [
                'type'      => 'required|string|in:new,existing',
                'name'      => 'required|string|max:191',
                'email'     => 'required|string|email|unique:user_admin,email|unique:users,email|max:191',
                'password'  => 'required|string|min:8|confirmed',
                'role'      => 'required|integer|exists:access_roles,id',
            ];
        }
        else {
            return [
                'type'      => 'required|string|in:new,existing',
                'userid'    => 'required|integer|exists:user_admin,id',
                'role'      => 'required|integer|exists:access_roles,id',
            ];
        }
    }
}
