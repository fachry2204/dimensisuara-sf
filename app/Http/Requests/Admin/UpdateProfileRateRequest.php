<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateProfileRateRequest extends FormRequest
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
        return [
            'aggregator_rate'   => 'required|integer|min:0|max:100',
            'publishing_rate'   => 'required|integer|min:0|max:100',
            'mcn_rate'          => 'required|integer|min:0|max:100',
        ];
    }
    public function messages(): array
    {
        return [
            '*.integer' => 'Revenue share harus berupa angka bulat tanpa titik atau koma.',
            '*.min' => 'Revenue share minimal 0%.',
            '*.max' => 'Revenue share maksimal 100%.',
        ];
    }

}
