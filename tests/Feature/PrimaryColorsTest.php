<?php

namespace Tests\Feature;

use App\Services\PrimaryColors;
use Tests\TestCase;

class PrimaryColorsTest extends TestCase
{
    private function palette(string $start, string $end, string $mode = 'gradient'): array
    {
        return app(PrimaryColors::class)->palette(['primary_color_mode'=>$mode, 'primary_color'=>$start, 'primary_gradient_color'=>$end, 'primary_gradient_angle'=>90]);
    }

    public function test_light_and_dark_solid_colors_get_readable_text(): void
    {
        $light = $this->palette('#ffff00', '#000000', 'solid');
        $dark = $this->palette('#102040', '#ffffff', 'solid');
        $this->assertSame('#000000', $light['text']);
        $this->assertSame('#ffff00', $light['background']);
        $this->assertSame('#ffffff', $dark['text']);
        $this->assertSame('#102040', $dark['background']);
    }

    public function test_extreme_gradient_keeps_label_contrast_across_the_gradient(): void
    {
        $colors = $this->palette('#ffffff', '#000000');
        $this->assertSame('#ffffff', $colors['text']);
        $rgb = fn ($hex) => array_map('hexdec', str_split(substr($hex, 1), 2));
        $a = $rgb($colors['start']); $b = $rgb($colors['end']);
        for ($i=0; $i<=20; $i++) {
            $linear = array_map(function ($x,$y) use ($i) { $v=($x*(1-$i/20)+$y*$i/20)/255; return $v<=.04045?$v/12.92:(($v+.055)/1.055)**2.4; }, $a,$b);
            $luminance=.2126*$linear[0]+.7152*$linear[1]+.0722*$linear[2];
            $this->assertGreaterThanOrEqual(4.5, 1.05/($luminance+.05));
        }
    }
}
