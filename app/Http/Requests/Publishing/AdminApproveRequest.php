<?php

namespace App\Http\Requests\Publishing;

use Illuminate\Foundation\Http\FormRequest;

use App\Models\Publishing\Signee;

class AdminApproveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('manage-releases-status');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'signeeid'  => 'required|array',
            'signeeid.*' => 'required|integer|exists:publishing_signees,id',

            'number'    => 'required|array',
            'number.*'  => 'required|string',

            'month'     => 'required|array',
            'month.*'   => 'required|integer|min:1|max:12',

            'year'      => 'required|array',
            'year.*'    => 'required|integer',
        ];
    }
}
