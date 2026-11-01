<?php

namespace App\Support;

final class ArtistCredits
{
    /** Normalize display credits, including legacy JSON stored inside artist names. */
    public static function normalize(mixed $value, int $depth = 0): array
    {
        if ($depth > 8 || $value === null) return [];

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') return [];
            $decoded = json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded) || is_string($decoded))) {
                return self::normalize($decoded, $depth + 1);
            }
            return [(object) ['name' => $value, 'social' => null, 'spotify' => null]];
        }

        if (is_object($value)) $value = (array) $value;
        if (!is_array($value)) return [];

        if (array_key_exists('name', $value)) {
            return array_map(function ($artist) use ($value) {
                return (object) array_merge($value, [
                    'name' => $artist->name,
                    'social' => $value['social'] ?? $value['socialLink'] ?? $artist->social,
                    'spotify' => $value['spotify'] ?? $value['spotifyLink'] ?? $artist->spotify,
                ]);
            }, self::normalize($value['name'], $depth + 1));
        }

        $artists = [];
        foreach ($value as $artist) {
            array_push($artists, ...self::normalize($artist, $depth + 1));
        }
        return $artists;
    }
}
