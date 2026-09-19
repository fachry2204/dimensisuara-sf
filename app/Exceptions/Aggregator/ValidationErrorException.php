<?php

namespace App\Exceptions\Aggregator;

use Exception;

class ValidationErrorException extends Exception
{
    protected $part;
    protected $position;
    protected $validationErrors;

    public function __construct($part, $validationErrors, $position = null)
    {
        parent::__construct('Validation errors');

        $this->part = $part;
        $this->position = $position;
        $this->validationErrors = $validationErrors;
    }

    public function getData()
    {
        $locale = app()->getLocale();
        $formattedErrors = [];

        $errorsArray = method_exists($this->validationErrors, 'toArray') 
            ? $this->validationErrors->toArray() 
            : (array) $this->validationErrors;

        foreach ($errorsArray as $key => $messages) {
            $formattedKey = null;
            if (preg_match('/^([a-z_]+)\.(\d+)\.([a-z_]+)$/', $key, $matches)) {
                $attribute = $matches[1];
                $index = intval($matches[2]) + 1;
                $field = $matches[3];

                $translationKey = "validation.attributes.{$attribute}.*.{$field}";
                $translated = __($translationKey);

                if ($translated !== $translationKey) {
                    $formattedKey = str_replace(':position', $index, $translated);
                } else {
                    $formattedKey = self::formatArrayKey($key, $locale);
                }
            }

            foreach ((array)$messages as $message) {
                if ($formattedKey) {
                    $message = str_replace(
                        [$key, ucwords(str_replace('.', ' ', $key)), str_replace('.', ' ', $key)],
                        [$formattedKey, $formattedKey, $formattedKey],
                        $message
                    );
                    $message = str_replace(':position', $index, $message);
                }
                $formattedErrors[] = $message;
            }
        }

        return $formattedErrors;
    }

    public function getPart()
    {
        return $this->part;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public static function formatArrayKey($key, $locale = 'id')
    {
        if (preg_match('/^([a-z_]+)\.(\d+)\.([a-z_]+)$/', $key, $matches)) {
            $attribute = $matches[1];
            $index = intval($matches[2]) + 1;
            $field = $matches[3];

            if ($locale === 'id') {
                $attrs = [
                    'primaryartists' => 'Artis Utama',
                    'featuredartists' => 'Artis Unggulan',
                    'songwriters' => 'Penulis Lagu',
                    'contributors' => 'Kontributor',
                    'productions' => 'Tim Produksi',
                ];
                $fields = [
                    'name' => 'Nama',
                    'social' => 'Media sosial',
                    'spotify' => 'Link Spotify',
                    'role' => 'Peran',
                ];
                
                $attrName = $attrs[$attribute] ?? $attribute;
                $fieldName = $fields[$field] ?? $field;
                return "{$fieldName} {$attrName} ke-{$index}";
            } else {
                $attrs = [
                    'primaryartists' => 'Primary Artist',
                    'featuredartists' => 'Featured Artist',
                    'songwriters' => 'Songwriter',
                    'contributors' => 'Contributor',
                    'productions' => 'Production Team member',
                ];
                $fields = [
                    'name' => 'name',
                    'social' => 'social media link',
                    'spotify' => 'Spotify link',
                    'role' => 'role',
                ];

                $attrName = $attrs[$attribute] ?? $attribute;
                $fieldName = $fields[$field] ?? $field;
                return "{$attrName} #{$index} {$fieldName}";
            }
        }
        return null;
    }
}
