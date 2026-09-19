<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

use Carbon\Carbon;

/**
 * The TikTok & YouTube Music pre-release date must fall between 1 and 7 days
 * before the planned release date.
 */
class PreReleaseWindow implements ValidationRule, DataAwareRule
{
    protected array $data = [];

    /**
     * @param  string|null  $releaseon  Fallback planned release date, used when
     *                                  the validated data does not carry one.
     */
    public function __construct(protected ?string $releaseon = null)
    {
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $releaseon = data_get($this->data, 'releaseon') ?: $this->releaseon;

        if (!$value || !$releaseon || !strtotime($releaseon) || !strtotime($value)) {
            return;
        }

        $planned = Carbon::parse($releaseon)->startOfDay();
        $prerelease = Carbon::parse($value)->startOfDay();

        if ($prerelease->lt($planned->copy()->subDays(7)) || $prerelease->gt($planned->copy()->subDay())) {
            $fail('The pre-release date must be between 1 and 7 days before the planned release date.');
        }
    }
}
