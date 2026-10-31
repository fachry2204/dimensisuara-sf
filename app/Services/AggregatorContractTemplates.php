<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class AggregatorContractTemplates
{
    public const FIELDS = [
        'APP_NAME', 'DOCNUM', 'DATE', 'FULLNAME', 'IDNUM', 'FULLADDRESS', 'PHONE', 'EMAIL',
        'COMPANYNAME', 'NPWPCOMPANY', 'NIBCOMPANY', 'NPWPPERSONAL', 'POSITION',
        'BANKNAME', 'ACCOUNTNUMBER', 'ACCOUNTNAME', 'USER_PERCENT', 'SYSTEM_PERCENT',
        'SIGNATURE1', 'SIGNATURE2',
        'PROVIDER_NAME', 'PROVIDER_ADDRESS', 'PROVIDER_DIRECTOR', 'PROVIDER_POSITION', 'PROVIDER_CONTACT_NAME',
        'PROVIDER_CONTACT_ADDRESS', 'PROVIDER_PHONE', 'PROVIDER_EMAIL', 'PROVIDER_SIGNATURE',
    ];

    public function get(bool $company): string
    {
        $key = $company ? 'aggregator_draft_company' : 'aggregator_draft_personal';
        $saved = app(SystemSettings::class)->get($key);
        if (filled($saved)) return app(CompanyIdentity::class)->tokenize($saved);

        $html = file_get_contents(resource_path('contract/'.($company ? 'aggregator_company' : 'aggregator').'.html'));
        $html = str_replace(['70% (tujuh puluh persen)', '30% (tiga puluh persen)'], ['[USER_PERCENT]%', '[SYSTEM_PERCENT]%'], $html);
        $html = app(CompanyIdentity::class)->tokenize($html);
        return preg_replace('/Soundfresh(?:\.id)?/i', '[APP_NAME]', $html);
    }

    public function formatSignatureLayout(string $html, string $date = '[DATE]'): string
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($dom);
        foreach (array_reverse(iterator_to_array($dom->getElementsByTagName('table'))) as $table) {
            if (!str_contains($table->textContent, 'PIHAK PERTAMA') || !str_contains($table->textContent, 'PIHAK KEDUA')) continue;
            if (str_contains($table->getAttribute('class'), 'contract-signatures-v2')) {
                $location = $xpath->query('.//p[@class="contract-signing-date"]', $table)->item(0);
                if (!$location || $location->getElementsByTagName('br')->length === 0) return $html;
                $savedDate = trim(preg_replace('/^Kota Bogor\s*,?\s*/', '', $location->textContent));
                while ($location->firstChild) $location->removeChild($location->firstChild);
                $bold = $dom->createElement('strong');
                $bold->appendChild($dom->createTextNode('Kota Bogor, '.$savedDate));
                $location->appendChild($bold);
                return str_replace('<?xml encoding="UTF-8">', '', $dom->saveHTML());
            }
            $formatted = str_contains($table->getAttribute('class'), 'contract-signatures');
            $rows = $xpath->query('./tr | ./tbody/tr', $table);
            foreach ($rows as $row) {
                if ($xpath->query('./td | ./th', $row)->length !== ($formatted ? 3 : 2)) return $html;
            }
            $table->setAttribute('class', trim($table->getAttribute('class').' contract-signatures contract-signatures-v2'));
            $table->setAttribute('style', 'width:100%;table-layout:fixed;border-collapse:collapse;page-break-inside:avoid;');
            foreach ($rows as $row) {
                $cells = $xpath->query('./td | ./th', $row);
                foreach ([$cells->item(0), $cells->item($cells->length - 1)] as $cell) {
                    $cell->setAttribute('style', 'width:34%;text-align:center;vertical-align:top;padding:0;border:0;');
                    foreach ($cell->getElementsByTagName('div') as $box) {
                        $box->setAttribute('style', 'height:125px;text-align:center;');
                    }
                    foreach ($cell->getElementsByTagName('img') as $image) {
                        $image->setAttribute('width', '200');
                        $image->setAttribute('style', 'width:200px;max-width:100%;max-height:115px;margin:0;');
                    }
                }
                $space = $dom->createElement('td');
                $space->setAttribute('style', 'width:32%;padding:0;border:0;');
                $space->setAttribute('class', 'digital-stamp-space');
                if (!$formatted) $row->insertBefore($space, $cells->item(1));
            }
            $oldLocation = $table->previousSibling;
            while ($oldLocation && $oldLocation->nodeType === XML_TEXT_NODE && trim($oldLocation->textContent) === '') {
                $oldLocation = $oldLocation->previousSibling;
            }
            if ($oldLocation instanceof \DOMElement && $oldLocation->getAttribute('class') === 'contract-signing-date') {
                $date = trim(str_replace('Kota Bogor', '', $oldLocation->textContent));
                $oldLocation->parentNode->removeChild($oldLocation);
            }
            $location = $dom->createElement('p');
            $location->setAttribute('class', 'contract-signing-date');
            $location->setAttribute('style', 'text-align:center;font-weight:bold;margin:0 0 18px;');
            $bold = $dom->createElement('strong');
            $bold->appendChild($dom->createTextNode('Kota Bogor, '.$date));
            $location->appendChild($bold);
            $dateRow = $dom->createElement('tr');
            foreach ([34, 32, 34] as $index => $width) {
                $cell = $dom->createElement('td');
                $cell->setAttribute('style', 'width:'.$width.'%;padding:0;border:0;vertical-align:top;');
                if ($index === 0) $cell->appendChild($location);
                if ($index === 1) $cell->setAttribute('class', 'digital-stamp-space');
                $dateRow->appendChild($cell);
            }
            $rows->item(0)->parentNode->insertBefore($dateRow, $rows->item(0));
            return str_replace('<?xml encoding="UTF-8">', '', $dom->saveHTML());
        }
        return $html;
    }

    public function validateDraft(string $html, string $key): string
    {
        preg_match_all('/\[([A-Z][A-Z0-9_]*)\]/', $html, $matches);
        $unknown = array_diff($matches[1], self::FIELDS);
        if ($unknown) throw ValidationException::withMessages([$key => 'Penanda tidak dikenal: '.implode(', ', array_unique($unknown))]);
        foreach (['USER_PERCENT', 'SYSTEM_PERCENT', 'FULLNAME'] as $field) {
            if (!str_contains($html, '['.$field.']')) {
                throw ValidationException::withMessages([$key => 'Draft wajib memuat ['.$field.'].']);
            }
        }

        // Only document markup is accepted; no scripts, external assets or local file URLs.
        $html = preg_replace('/<\?(?:.|\s)*?\?>/', '', $html);
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $allowed = ['html','head','meta','title','style','body','header','footer','main','section','article','div','span','p','br','hr','h1','h2','h3','h4','h5','h6','strong','b','em','i','u','s','small','sup','sub','ol','ul','li','table','thead','tbody','tfoot','tr','th','td','colgroup','col'];
        foreach (iterator_to_array($dom->getElementsByTagName('*')) as $node) {
            if (!in_array(strtolower($node->tagName), $allowed, true)) {
                $node->parentNode?->removeChild($node);
                continue;
            }
            foreach (iterator_to_array($node->attributes) as $attribute) {
                if (!in_array(strtolower($attribute->name), ['style','class','id','colspan','rowspan','width','height','align','type','start'], true)) $node->removeAttribute($attribute->name);
            }
        }
        $safe = $dom->saveHTML();
        if (preg_match('/url\s*\(|@import|expression\s*\(|javascript:|file:|<\?/i', str_replace('<?xml encoding="UTF-8">', '', $safe))) {
            throw ValidationException::withMessages([$key => 'Draft tidak boleh memuat URL aset, script, atau kode PHP.']);
        }
        $safe = str_replace('<?xml encoding="UTF-8">', '', $safe);
        foreach (['USER_PERCENT', 'SYSTEM_PERCENT', 'FULLNAME'] as $field) {
            if (!str_contains(strip_tags($safe), '['.$field.']')) {
                throw ValidationException::withMessages([$key => 'Penanda ['.$field.'] harus berada di teks dokumen.']);
            }
        }
        return $safe;
    }
}
