<?php

namespace App\Support;

use Symfony\Component\Intl\Countries;

class CountryName
{
    public static function display(?string $value): string
    {
        $name = trim((string)$value);
        if ($name === '') return 'Tidak diketahui';
        $code = strtoupper($name);
        if (strlen($code) === 2 && Countries::exists($code)) {
            return Countries::getName($code,'id');
        }
        // Retain names supplied directly by a report and unrecognized legacy values.
        return $name;
    }
}
