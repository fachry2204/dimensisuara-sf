<?php

namespace App\Http\Requests\Release;

use Illuminate\Foundation\Http\FormRequest;

use Carbon\Carbon;

use App\Rules\PreReleaseWindow;

class AlbumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return current_user()->profile ? true : false;
    }

    public function rules(?\App\Models\Release $release = null): array
    {
        return [
            'title' => 'required|string|max:255',
            'titlelang' => 'required|string|max:255',
            'primaryartists' => 'required|array|min:1',
            'primaryartists.*.name' => 'required|filled|string|max:100',
            'primaryartists.*.social' => 'required|filled|string',
            'primaryartists.*.spotify' => 'nullable|string',
            'recordlabel' => 'required|string|max:100',
            'version' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($release) {
                    $item = \App\Models\System\Version::find($value);
                    if (!$item) {
                        $fail('The selected version is invalid.');
                        return;
                    }
                    if ($item->is_active || ($release && $release->version == $value)) {
                        return;
                    }
                    $fail('The selected version is a legacy option and is no longer available.');
                }
            ],
            'genre' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($release) {
                    $item = \App\Models\System\Genre::find($value);
                    if (!$item) {
                        $fail('The selected genre is invalid.');
                        return;
                    }
                    if ($item->is_active || ($release && $release->genre == $value)) {
                        return;
                    }
                    $fail('The selected genre is a legacy option and is no longer available.');
                }
            ],
            'subgenre' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($release) {
                    if ($value === 0 || $value === '0' || empty($value)) {
                        return;
                    }
                    $item = \App\Models\System\Subgenre::find($value);
                    if (!$item) {
                        $fail('The selected subgenre is invalid.');
                        return;
                    }
                    if ($item->is_active || ($release && $release->subgenre == $value)) {
                        return;
                    }
                    $fail('The selected subgenre is a legacy option and is no longer available.');
                }
            ],
            'released' => 'required|boolean',
            'releaseddate' => 'nullable|required_if:released,1|date',
            'upc' => 'nullable|required_if:released,1|string|max:15',
            'releaseon' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    if (!Carbon::parse($value)->isAfter(today()->addDays(13))) {
                        $fail('The planned release date must be at least 14 days from today.');
                    }
                },
            ],
            'tiktok_ytmusic_rerelease_date' => [
                'bail',
                'nullable',
                'date',
                new PreReleaseWindow($release ? $release->releaseon : null),
            ],
            'tracknum' => 'required|integer|min:1',
            'type' => 'required|string|in:single,album',
            'status' => 'required|string|in:draft,pending,review,rejected,released',
        ];
    }

    public function messages(): array
    {
        return [
            'ipl.required_if' => 'IPL is required if version is cover',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => __('validation.attributes.album_title'),
            'titlelang' => __('validation.attributes.album_titlelang'),
            'version' => __('validation.attributes.album_version'),
            'genre' => __('validation.attributes.album_genre'),
            'subgenre' => __('validation.attributes.album_subgenre'),
        ];
    }

}
