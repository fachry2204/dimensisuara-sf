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
            'number' => 'required|integer|min:1|max:2147483647',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2000,2100',
        ];

        if ($this->input('type') === 'aggregator') {
            $rules['contract_date'] = ['nullable', 'date_format:Y-m-d'];
            $rules['aggregator_rate'] = ['required', 'numeric', 'multiple_of:1', 'between:0,100'];
            $rules['system_rate'] = ['required', 'numeric', 'multiple_of:1', 'between:0,100'];
        }

        if ($this->user()->can('upload-contracts')) {
            return [
                ...$rules,
                'pdf' => 'nullable|file|mimetypes:application/pdf|max:10240',
            ];
        }

        return $rules;

    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('type') === 'aggregator' && !$validator->errors()->hasAny(['aggregator_rate', 'system_rate'])
                && (int) $this->input('aggregator_rate') + (int) $this->input('system_rate') !== 100) {
                $validator->errors()->add('aggregator_rate', 'Total Pembagian User dan Pembagian System harus 100%.');
            }
        });
    }
}
