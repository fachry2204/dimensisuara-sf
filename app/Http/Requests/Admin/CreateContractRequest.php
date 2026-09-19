<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CreateContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'profileid' => 'required|integer|exists:user_profiles,id',
            'type' => 'required|string|in:publishing,aggregator,mcn,publishing_statement',
            'number' => 'required|integer',
            'month' => 'required|integer',
            'year' => 'required|integer',
        ];

        if ($this->user()->can('upload-contracts')) {
            return [
                ...$rules,
                'pdf' => 'nullable|file|mimetypes:application/pdf',
            ];
        }

        return $rules;

    }
}
