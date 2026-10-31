<?php

namespace App\Services;

class PrimaryColors
{
    private function rgb(string $hex): array
    {
        return array_map('hexdec', str_split(substr($hex, 1), 2));
    }

    private function hex(array $rgb): string
    {
        return '#'.implode('', array_map(fn ($n) => str_pad(dechex((int) round($n)), 2, '0', STR_PAD_LEFT), $rgb));
    }

    private function luminance(array $rgb): float
    {
        $linear = array_map(function ($n) { $n /= 255; return $n <= .04045 ? $n / 12.92 : (($n + .055) / 1.055) ** 2.4; }, $rgb);
        return .2126 * $linear[0] + .7152 * $linear[1] + .0722 * $linear[2];
    }

    private function contrast(array $a, array $b): float
    {
        $a = $this->luminance($a); $b = $this->luminance($b);
        return (max($a, $b) + .05) / (min($a, $b) + .05);
    }

    private function mix(array $a, array $b, float $amount): array
    {
        return array_map(fn ($x, $y) => $x * (1 - $amount) + $y * $amount, $a, $b);
    }

    private function minContrast(array $start, array $end, array $text): float
    {
        $minimum = 21;
        for ($i = 0; $i <= 20; $i++) $minimum = min($minimum, $this->contrast($this->mix($start, $end, $i / 20), $text));
        return $minimum;
    }

    private function readable(array $color, array $surface, array $target): string
    {
        for ($i = 0; $i <= 100; $i++) {
            $candidate = $this->mix($color, $target, $i / 100);
            if ($this->contrast($candidate, $surface) >= 4.6) return $this->hex($candidate);
        }
        return $this->hex($target);
    }

    public function palette(?array $values = null): array
    {
        $values ??= app(SystemSettings::class)->all();
        $start = $this->rgb($values['primary_color']);
        $end = $values['primary_color_mode'] === 'gradient' ? $this->rgb($values['primary_gradient_color']) : $start;
        $white = [255,255,255]; $black = [0,0,0];
        $text = $this->minContrast($start, $end, $white) >= $this->minContrast($start, $end, $black) ? $white : $black;
        // If a gradient spans very different brightnesses, shade it enough for readable labels.
        if ($this->minContrast($start, $end, $text) < 4.6) {
            $originalStart = $start; $originalEnd = $end; $text = $white;
            for ($i = 1; $i <= 100; $i++) {
                $start = $this->mix($originalStart, $black, $i / 100);
                $end = $this->mix($originalEnd, $black, $i / 100);
                if ($this->minContrast($start, $end, $text) >= 4.6) break;
            }
        }
        $hoverTarget = $text === $white ? $black : $white;
        $hoverStart = $this->mix($start, $hoverTarget, .12);
        $hoverEnd = $this->mix($end, $hoverTarget, .12);
        $angle = (int) $values['primary_gradient_angle'];
        $background = fn ($a, $b) => $values['primary_color_mode'] === 'gradient'
            ? 'linear-gradient('.$angle.'deg, '.$this->hex($a).', '.$this->hex($b).')' : $this->hex($a);
        return [
            'start' => $this->hex($start), 'end' => $this->hex($end), 'background' => $background($start, $end),
            'hover' => $background($hoverStart, $hoverEnd), 'text' => $this->hex($text),
            'light_accent' => $this->readable($start, $white, $black),
            'dark_accent' => $this->readable($start, [20,30,45], $white),
            'light_soft' => $this->hex($this->mix($start, $white, .91)),
            'dark_soft' => $this->hex($this->mix($start, [20,30,45], .8)),
            'check' => $this->readable($start, $white, $black),
        ];
    }
}
