<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Monarobase\CountryList\CountryListFacade as Countries;

/**
 * Created by ChatGPT
 */
class CountryMatcher
{
    protected array $countries;
    protected array $normalizedCountries;          // "ID" => "indonesia"
    protected array $normalizedCountriesNoStop;    // "ID" => "indonesia" (stopwords removed)
    protected array $stopwords = [
        'republic','federation','federated','states','state',
        'kingdom','the','of','and','united','islands','island',
        'territory','province','people','peoples'
    ];
    protected array $aliases = [
        // small, safe alias map (extend if you must)
        'u s a' => 'US',
        'usa'   => 'US',
        'u s'   => 'US',
        'america' => 'US',
        // historical example - handle deliberately (you may prefer null instead)
        'serbia and montenegro' => 'RS', // deprecated historic code; or treat as ambiguous

        // General
        'worldwide' => null,
        'global'    => null,

        // Russian Federation
        'russian federation' => 'RU',

        // Hong Kong & Macao
        'hong kong' => 'HK',
        'macao'     => 'MO',

        // Czechia
        'czech republic' => 'CZ',

        // China
        'china mainland' => 'CN',

        // Vietnam
        'viet nam' => 'VN',

        // Brunei
        'brunei darussalam' => 'BN',

        // Korea
        'korea republic of'                    => 'KR', // South Korea
        'korea democratic people s republic of' => 'KP', // North Korea

        // Laos
        'lao people s democratic republic' => 'LA',

        // Congo
        'congo'                                 => 'CD', // Republic of the Congo
        'congo the democratic republic of the'  => 'CD', // DRC

        // Myanmar
        'myanmar' => 'MM',

        // Libya
        'libyan arab jamahiriya' => 'LY',

        // North Macedonia
        'macedonia the former yugoslav republic of' => 'MK',

        // Falkland Islands
        'falkland islands malvinas' => 'FK',

        // Réunion
        'reunion'  => 'RE',
        'r��union' => 'RE', // corrupted legacy form

        // Historical / ambiguous
        'serbia and montenegro' => 'CS', // deprecated ISO; you may want null instead
    ];

    public function __construct()
    {
        $this->countries = Countries::getList('en'); // ["ID" => "Indonesia", ...]
        $this->normalizedCountries = [];
        $this->normalizedCountriesNoStop = [];

        foreach ($this->countries as $code => $name) {
            $norm = $this->normalize($name);
            $this->normalizedCountries[$code] = $norm;
            $this->normalizedCountriesNoStop[$code] = $this->removeStopwords($norm);
        }
    }

    protected function normalize(string $name): string
    {
        $name = trim($name);

        // fix encoding if needed
        $enc = mb_detect_encoding($name, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ISO-8859-15'], true);
        if ($enc && $enc !== 'UTF-8') {
            $name = mb_convert_encoding($name, 'UTF-8', $enc);
        }

        // remove accents, lowercase
        $name = Str::ascii($name);
        $name = strtolower($name);

        // replace common punctuation with space, remove other chars
        $name = preg_replace('/[\/,()\.\'"-]/u', ' ', $name);
        $name = preg_replace('/[^a-z0-9\s]/u', '', $name);

        // collapse spaces
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    protected function removeStopwords(string $name): string
    {
        if ($name === '') return '';
        $pattern = '/\b(' . implode('|', array_map('preg_quote', $this->stopwords)) . ')\b/u';
        $str = preg_replace($pattern, ' ', $name);
        $str = preg_replace('/\s+/', ' ', $str);
        return trim($str);
    }

    protected function toWordSet(string $name): array
    {
        if ($name === '') return [];
        return array_values(array_unique(array_filter(explode(' ', $name))));
    }

    protected function jaccard(array $a, array $b): float
    {
        $i = count(array_intersect($a, $b));
        $u = count(array_unique(array_merge($a, $b)));
        return $u > 0 ? ($i / $u) : 0.0;
    }

    public function match(string $input): ?string
    {
        $norm = $this->normalize($input);
        if ($norm === '') return null;

        // quick alias (normalized)
        $aliasKey = str_replace(' ', ' ', $norm); // already normalized
        if (isset($this->aliases[$aliasKey])) {
            return $this->aliases[$aliasKey];
        }

        // quick reject for non-country values commonly found in reports
        $nonCountries = ['worldwide','global','international','unknown','asia','europe','americas','africa','middle east'];
        if (in_array($norm, $nonCountries, true)) return null;

        // 1) Exact on full normalized name
        $exact = array_search($norm, $this->normalizedCountries, true);
        if ($exact !== false) return $exact;

        // 2) Exact on name-without-stopwords (useful e.g. "czech republic" -> "czech")
        $normNoStop = $this->removeStopwords($norm);
        $exactNoStop = array_search($normNoStop, $this->normalizedCountriesNoStop, true);
        if ($exactNoStop !== false) return $exactNoStop;

        // prepare input sets (without stopwords)
        $inputSet = $this->toWordSet($normNoStop);

        // 3) Jaccard similarity on non-stopword tokens (require decent overlap)
        $bestScore = 0.0;
        $bestCode = null;
        foreach ($this->normalizedCountriesNoStop as $code => $targetNoStop) {
            $targetSet = $this->toWordSet($targetNoStop);
            if (empty($inputSet) && empty($targetSet)) continue;
            $score = $this->jaccard($inputSet, $targetSet);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCode = $code;
            }
        }

        // trust token-similarity only above threshold
        if ($bestScore >= 0.6) {
            return $bestCode;
        }

        // 4) Fuzzy Levenshtein fallback: only among candidates that share at least one non-stopword token
        $bestDist = null;
        $bestCandidate = null;
        foreach ($this->normalizedCountries as $code => $targetFull) {
            $targetNoStop = $this->normalizedCountriesNoStop[$code] ?? '';
            if (!array_intersect($inputSet, $this->toWordSet($targetNoStop))) {
                continue; // require at least one meaningful shared token
            }

            $d = levenshtein($norm, $targetFull);
            $maxLen = max(mb_strlen($norm), mb_strlen($targetFull));
            $ratio = $maxLen > 0 ? ($d / $maxLen) : 1.0;

            // accept only fairly close ratios
            if ($ratio <= 0.35 && ($bestDist === null || $ratio < $bestDist)) {
                $bestDist = $ratio;
                $bestCandidate = $code;
            }
        }

        return $bestCandidate; // could be null if nothing safe found
    }
}
