<?php

namespace App\Http\Requests\Publishing;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Validator;


class StorePublishingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (current_user()->profile && current_user()->profile->feature_publishing) ? true : false;
    }

    public function rules(): array
    {
        if ($this->status === 'draft') {
            return [
                'release_id' => 'nullable|integer|exists:releases,id',
                'track_id' => 'nullable|integer|exists:tracks,id',
                'title' => 'required|string|max:255',
                'other_title' => 'nullable|string|max:255',
                'sample' => 'nullable|string|max:255',
                'sync_license_type' => 'required|in:exclusive,non-exclusive',
                "performer" => 'nullable|string|max:100',
                "duration_minute" => 'nullable|integer|max:99',
                "duration_second" => 'nullable|integer|max:59',
                "genre" => 'nullable|integer',
                "language" => 'nullable|string|max:3',
                "iswc" => 'nullable|string|max:100',
                "isrc" => 'nullable|string|max:100',
                "accept_terms" => 'nullable|string|in:on',

                "songwriters" => 'nullable|array',
                "songwriters.*.name" => 'nullable|string|max:255',
                "songwriters.*.email" => 'nullable|email|max:255',
                "songwriters.*.ktp_num" => 'nullable|string|max:255',
                "songwriters.*.phone" => 'nullable|string|max:255',
                "songwriters.*.role" => 'nullable|in:author,composer,author_composer|max:100',
                "songwriters.*.publisher" => 'nullable|string|max:255',
                "songwriters.*.lmk" => 'nullable|string|max:255',
                "songwriters.*.share" => 'nullable|numeric|min:0|max:100',
            ];
        }

        return [
            'release_id' => 'nullable|integer|exists:releases,id',
            'track_id' => 'nullable|integer|exists:tracks,id',
            'title' => 'required|string|max:255',
            'other_title' => 'nullable|string|max:255',
            'sample' => 'required_without:track_id|nullable|string|max:255',
            'sync_license_type' => 'required|in:exclusive,non-exclusive',
            "performer" => 'required|string|max:100',
            "duration_minute" => 'required|integer|max:99',
            "duration_second" => 'required|integer|max:59',
            "genre" => 'required|integer',
            "language" => 'required|string|max:3',
            "iswc" => 'nullable|string|max:100',
            "isrc" => 'required|string|max:100',
            "accept_terms" => 'required|string|in:on',

            "songwriters" => 'required|array|min:1',
            "songwriters.*.id" => 'nullable|integer',
            "songwriters.*.name" => 'required|string|max:255',
            "songwriters.*.email" => 'required|email|max:255',
            "songwriters.*.ktp_num" => 'required|string|max:255',
            "songwriters.*.phone" => 'required|string|max:255',
            "songwriters.*.role" => 'required|in:author,composer,author_composer',
            "songwriters.*.publisher" => 'nullable|string|max:255',
            "songwriters.*.lmk" => 'nullable|string|max:255',
            "songwriters.*.share" => 'required|numeric|min:0|max:100',
        ];

    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->status !== 'draft') {
                    $songwriters = $this->input('songwriters');
                    if (isset($songwriters) && is_array($songwriters) && count($songwriters) > 0) {
                        // Check total share rate
                        if (!self::checkTotalShareRate($songwriters)) {
                            $validator->errors()->add('songwriters', 'Total songwriter share must be 100%');
                        }

                        // Validate first entry belongs to account owner
                        $firstEntry = $songwriters[0];
                        $ownerName = current_user()->full_name;

                        if (trim($firstEntry['name']) !== $ownerName) {
                            $validator->errors()->add('songwriters', 'The first songwriter must match your account name.');
                        }
                        if (trim($firstEntry['email']) !== current_user()->email) {
                            $validator->errors()->add('songwriters', 'The first songwriter must match your account email.');
                        }
                        if (isset($firstEntry['ktp_num']) && trim($firstEntry['ktp_num']) !== trim(current_user()->profile->ktp_num)) {
                            $validator->errors()->add('songwriters', 'The first songwriter must match your account citizen ID.');
                        }
                    }
                }
            }
        ];
    }

    private static function checkTotalShareRate(array $entries): bool
    {
        $total = 0;
        foreach ($entries as $entry) {
            $total += (float) ($entry['share'] ?? 0);
        }
        // Allow small tolerance for floating point precision (0.01%)
        return abs($total - 100) < 0.01;
    }

}
