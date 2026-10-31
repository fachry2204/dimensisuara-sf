<?php

namespace App\Services;

class CompanyIdentity
{
    public const FIELDS = [
        'PROVIDER_NAME' => 'company_name',
        'PROVIDER_ADDRESS' => 'company_address',
        'PROVIDER_DIRECTOR' => 'company_director',
        'PROVIDER_POSITION' => 'company_position',
        'PROVIDER_CONTACT_NAME' => 'company_contact_name',
        'PROVIDER_CONTACT_ADDRESS' => 'company_contact_address',
        'PROVIDER_PHONE' => 'company_phone',
        'PROVIDER_EMAIL' => 'company_email',
    ];

    public function signaturePath(): ?string
    {
        $filename = app(SystemSettings::class)->get('company_signature');
        if ($filename === null) return is_file(storage_path('app/sign.jpeg')) ? storage_path('app/sign.jpeg') : null;
        if (!preg_match('/^director-[a-f0-9-]+\.(png|jpg|jpeg|webp)$/', $filename)) return null;
        $path = config('system-settings.company_signatures_path').DIRECTORY_SEPARATOR.$filename;
        return is_file($path) ? $path : null;
    }

    /** Convert legacy provider text before any client data is merged. */
    public function tokenize(string $html): string
    {
        $patterns = [
            '/PT\s+Digital\s+Masif\s+Suara\s+Visual/i' => '[PROVIDER_NAME]',
            '/Fachry\s+Hidayat/i' => '[PROVIDER_DIRECTOR]',
            '/Yunita\s+Afianti/i' => '[PROVIDER_CONTACT_NAME]',
            '/(?:yunita(?:soundfresh|\[APP_NAME\])|digitaldmsv)@gmail\.com/i' => '[PROVIDER_EMAIL]',
            '/087735629645/' => '[PROVIDER_PHONE]',
            '/Jl\.\s*Ciledug\s+Raya\s+No\.\s*4D,\s*RT\.?\s*(?:010|10)\s*\/RW\.?\s*(?:002|2),\s*(?:Kelurahan\s+)?Petukangan\s+Utara,\s*(?:Kecamatan\s+)?Pesanggrahan,\s*Kota\s+Jakarta\s+Selatan,\s*(?:DKI\s+Jakarta|Daerah\s+Khusus\s+Ibukota\s+Jakarta(?:\s*12260)?)/i' => '[PROVIDER_ADDRESS]',
        ];
        $html = preg_replace(array_keys($patterns), array_values($patterns), $html);
        $html = preg_replace('/(Alamat\s*:\s*)\[PROVIDER_ADDRESS\]/i', '$1[PROVIDER_CONTACT_ADDRESS]', $html);
        $html = preg_replace('/(>Alamat<\/td>\s*<td[^>]*>:\s*)\[PROVIDER_ADDRESS\]/i', '$1[PROVIDER_CONTACT_ADDRESS]', $html);
        return $this->tokenizePosition($html);
    }

    private function tokenizePosition(string $html): string
    {
        if (!str_contains($html, '[PROVIDER_DIRECTOR]') || !preg_match('/Direktur\s+Utama/i', $html)) return $html;
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//text()') as $text) {
            if (!preg_match('/Direktur\s+Utama/i', $text->nodeValue)) continue;
            $paragraph = $xpath->query('ancestor::p[1] | ancestor::li[1]', $text);
            $providerParagraph = false;
            foreach ($paragraph as $node) {
                if (str_contains($node->textContent, '[PROVIDER_DIRECTOR]')) $providerParagraph = true;
            }
            $cell = $xpath->query('ancestor::td[1]', $text)->item(0);
            $table = $xpath->query('ancestor::table[1]', $text)->item(0);
            $providerCell = $cell && $table && str_contains($table->textContent, '[PROVIDER_DIRECTOR]')
                && $xpath->query('preceding-sibling::td', $cell)->length === 0;
            if ($providerParagraph || $providerCell) {
                $text->nodeValue = preg_replace('/Direktur\s+Utama/i', '[PROVIDER_POSITION]', $text->nodeValue);
            }
        }
        return str_replace('<?xml encoding="UTF-8">', '', $dom->saveHTML());
    }

    public function fill(string $html): string
    {
        $settings = app(SystemSettings::class);
        $fields = [];
        foreach (self::FIELDS as $token => $key) $fields['['.$token.']'] = e((string) $settings->get($key));
        return strtr($html, $fields);
    }
}
