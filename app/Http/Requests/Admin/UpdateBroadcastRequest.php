<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateBroadcastRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('broadcast');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'selection_mode' => 'required|in:exclude,include',
            'excluded_user_ids' => 'nullable|array',
            'excluded_user_ids.*' => 'exists:users,id',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'The subject field is required.',
            'message.required' => 'The message field is required.',
            'message.max' => 'The message may not be greater than 5000 characters.',
        ];
    }
}
