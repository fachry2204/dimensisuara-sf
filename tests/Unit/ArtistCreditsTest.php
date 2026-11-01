<?php

namespace Tests\Unit;

use App\Support\ArtistCredits;
use PHPUnit\Framework\TestCase;

class ArtistCreditsTest extends TestCase
{
    public function test_legacy_json_names_are_unwrapped_for_primary_and_featured_artists(): void
    {
        foreach ([['name' => '["PTQ RRI Tilawah"]'], '["PTQ RRI Tilawah"]', json_encode(['["PTQ RRI Tilawah"]'])] as $input) {
            $credits = ArtistCredits::normalize($input);
            $this->assertSame(['PTQ RRI Tilawah'], array_column($credits, 'name'));
        }
        $this->assertSame(['First', 'Second'], array_column(ArtistCredits::normalize(['name'=>'["First","Second"]']), 'name'));
    }

    public function test_empty_legacy_credits_do_not_display_brackets(): void
    {
        foreach ([null, '', [], '[]', ['[]'], ['name'=>'[]'], json_encode(['name'=>'[]'])] as $input) {
            $this->assertSame([], ArtistCredits::normalize($input));
        }
    }

    public function test_normal_names_and_credit_links_are_preserved(): void
    {
        $name = 'The "Sound" [Live]';
        $credits = ArtistCredits::normalize([(object) ['name'=>$name, 'social'=>'example.test', 'spotify'=>'open.spotify.com/artist/1']]);
        $this->assertSame($name, $credits[0]->name);
        $this->assertSame('example.test', $credits[0]->social);
        $this->assertSame('open.spotify.com/artist/1', $credits[0]->spotify);
        $legacy = ArtistCredits::normalize(['name'=>'["First"]', 'socialLink'=>'legacy.test']);
        $this->assertSame('First', $legacy[0]->name);
        $this->assertSame('legacy.test', $legacy[0]->social);
    }
}
