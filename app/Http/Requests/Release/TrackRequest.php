<?php

namespace App\Http\Requests\Release;

use Illuminate\Foundation\Http\FormRequest;

class TrackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return current_user()->profile ? true : false;
    }

    public function rules(?\App\Models\Track $track = null): array
    {
        return [
            'title' => 'required|string|max:255',
            'titlelang' => 'required|string|max:5',
            'version' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($track) {
                    $item = \App\Models\System\Version::find($value);
                    if (!$item) {
                        $fail('The selected version is invalid.');
                        return;
                    }
                    if ($item->is_active || ($track && $track->version == $value)) {
                        return;
                    }
                    $fail('The selected version is a legacy option and is no longer available.');
                }
            ],
            'genre' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($track) {
                    $item = \App\Models\System\Genre::find($value);
                    if (!$item) {
                        $fail('The selected genre is invalid.');
                        return;
                    }
                    if ($item->is_active || ($track && $track->genre == $value)) {
                        return;
                    }
                    $fail('The selected genre is a legacy option and is no longer available.');
                }
            ],
            'subgenre' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($track) {
                    if ($value === 0 || $value === '0' || empty($value)) {
                        return;
                    }
                    $item = \App\Models\System\Subgenre::find($value);
                    if (!$item) {
                        $fail('The selected subgenre is invalid.');
                        return;
                    }
                    if ($item->is_active || ($track && $track->subgenre == $value)) {
                        return;
                    }
                    $fail('The selected subgenre is a legacy option and is no longer available.');
                }
            ],
            'instrumental' => 'required|boolean',
            'lyrics' => 'nullable|required_if:instrumental,0|string',
            'lyriclang' => 'nullable|required_if:instrumental,0|string|max:5',
            'explicit' => 'nullable|required_if:instrumental,0|string|in:non-explicit,explicit,clean',
            'songwriters' => 'required|array|min:1',
            'songwriters.*.name' => 'required|string|max:100|min:1',
            'songwriters.*.role' => 'required|string|in:author,composer,author_composer',
            'isrc' => 'nullable|string|max:15',
            'primaryartists' => 'required|array|min:1',
            'primaryartists.*.name' => 'required|filled|string|min:1|max:100',
            'primaryartists.*.social' => 'required|filled|string|min:1',
            'primaryartists.*.spotify' => 'nullable|string|min:1',
            'featuredartists' => 'nullable|array',
            'featuredartists.*.name' => 'required|filled|string|min:1|max:100',
            'featuredartists.*.social' => 'required|filled|string|min:1',
            'featuredartists.*.spotify' => 'nullable|string|min:1',
            'contributors' => 'nullable|array',
            'contributors.*.name' => 'required|string|max:100|min:1',
            'contributors.*.role' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($track) {
                    $item = \App\Models\System\Contributor::find($value);
                    if (!$item) {
                        $fail('The selected contributor role is invalid.');
                        return;
                    }
                    if ($item->is_active) {
                        return;
                    }
                    if ($track) {
                        $existingList = is_string($track->contributors) ? json_decode($track->contributors, true) : $track->contributors;
                        if (is_array($existingList)) {
                            foreach ($existingList as $existing) {
                                $existingRole = is_object($existing) ? ($existing->role ?? null) : ($existing['role'] ?? null);
                                if ($existingRole == $value) {
                                    return;
                                }
                            }
                        }
                    }
                    $fail('The selected contributor role is a legacy option and is no longer available.');
                }
            ],
            'productions' => 'nullable|array',
            'productions.*.name' => 'required|string|max:100|min:1',
            'productions.*.role' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($track) {
                    $item = \App\Models\System\Production::find($value);
                    if (!$item) {
                        $fail('The selected production role is invalid.');
                        return;
                    }
                    if ($item->is_active) {
                        return;
                    }
                    if ($track) {
                        $existingList = is_string($track->productions) ? json_decode($track->productions, true) : $track->productions;
                        if (is_array($existingList)) {
                            foreach ($existingList as $existing) {
                                $existingRole = is_object($existing) ? ($existing->role ?? null) : ($existing['role'] ?? null);
                                if ($existingRole == $value) {
                                    return;
                                }
                            }
                        }
                    }
                    $fail('The selected production role is a legacy option and is no longer available.');
                }
            ],
            'iswc' => 'nullable|string|max:100',
            'writer_percentage' => 'required|numeric|min:0|max:100',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => __('validation.attributes.track_title'),
            'titlelang' => __('validation.attributes.track_titlelang'),
            'version' => __('validation.attributes.track_version'),
            'genre' => __('validation.attributes.track_genre'),
            'subgenre' => __('validation.attributes.track_subgenre'),
            'songwriters' => __('validation.attributes.songwriters'),
        ];
    }
}
