<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReleaseDetailViewTest extends TestCase
{
    public function test_release_detail_renders_empty_and_legacy_artist_credits_for_admin_and_customer(): void
    {
        foreach (['admin','customer'] as $type) {
            foreach ([null, [], json_encode([['name'=>json_encode(['Artist One','Artist Two'])]]), [['name'=>'Artist One'],['name'=>'Artist Two']]] as $credits) {
                $release = new class {
                    public array $values = [];
                    public function __get($name) { return $this->values[$name] ?? null; }
                };
                $release->values = [
                    'id'=>3637,'title'=>'Release detail fixture','status'=>'pending',
                    'type'=>'single','releaseon'=>'2026-10-13','recordlabel'=>'Dimensi Suara',
                    'primaryartists'=>$credits,'tracks'=>collect(),'histories'=>collect(),
                ];
                $html = view('components.release',compact('release','type'))->render();
                $this->assertStringContainsString('Release detail fixture',$html);
                $this->assertStringNotContainsString('@php',$html);
                if ($credits) {
                    $this->assertStringContainsString('Artist One, Artist Two',$html);
                    $this->assertStringNotContainsString('[&quot;Artist',$html);
                }
            }
        }
    }
}
