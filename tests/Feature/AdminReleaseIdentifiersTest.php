<?php

namespace Tests\Feature;

use App\Models\Release;
use App\Models\Track;
use App\Services\Releases\ReleaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminReleaseIdentifiersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'release_identifier_testing', 'database.connections.release_identifier_testing'=>[
            'driver'=>'sqlite', 'database'=>':memory:', 'prefix'=>'', 'foreign_key_constraints'=>true,
        ]]);
        Log::setDefaultDriver('null');
        foreach (['releases'=>new Release, 'tracks'=>new Track] as $table=>$model) {
            Schema::create($table, function ($t) use ($model) {
                $t->id();
                foreach (array_diff($model->getFillable(), ['primaryartists','featuredartists','contributors','productions','songwriters']) as $field) $t->string($field)->nullable();
                $t->timestamps();
            });
        }
        Schema::create('release_track', function ($t) {
            $t->integer('release_id'); $t->integer('track_id'); $t->integer('track_order'); $t->timestamps();
        });
        Schema::create('user_profiles', fn ($t) => $t->id());
        Schema::create('filestorage', function ($t) {
            $t->id(); $t->string('owner_type'); $t->integer('owner_id'); $t->string('type');
        });
        foreach (['_versions','_genres','_productions'] as $table) Schema::create($table, function ($t) { $t->id(); $t->string('name'); });
        (require base_path('database/migrations/2026_11_01_130000_normalize_application_json_fields.php'))->up();
        DB::table('user_profiles')->insert(['id'=>42]);
    }

    protected function tearDown(): void
    {
        DB::purge('release_identifier_testing');
        parent::tearDown();
    }

    public function test_admin_single_and_album_track_forms_show_isrc_without_requiring_previously_released(): void
    {
        foreach (['partials.single-step1-fields'=>'single-isrc', 'partials.album-track-form-fields'=>'track-isrc'] as $view=>$id) {
            $html = view($view, ['allowIdentifierEntry'=>true])->render();
            $dom = new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
            $input = $dom->getElementById($id);
            $this->assertNotNull($input);
            $this->assertFalse($input->parentNode->hasAttribute('x-show'));
            $this->assertStringContainsString('.isrc', $input->getAttribute('x-model'));
            $this->assertFalse($input->hasAttribute('disabled'));
        }
    }

    public function test_new_release_identifiers_are_saved_and_can_be_updated_for_user_owner(): void
    {
        foreach (['single', 'album'] as $type) {
            $data = ['title'=>'Admin release', 'type'=>$type, 'version'=>1, 'released'=>0, 'upc'=>'0012345678901',
                'tracks_json'=>json_encode([['title'=>'Track one', 'version'=>1, 'isrc'=>'IDABC2600001']])];
            $release = app(ReleaseService::class)->store(new Release, 42, 'draft', $data);
            $this->assertSame('0012345678901', $release->fresh()->upc);
            $track = $release->tracks->first();
            $this->assertSame('IDABC2600001', $track->fresh()->isrc);
            $this->assertEquals(42, $track->profileid);
            $data['upc'] = '0098765432109';
            $data['tracks_json'] = json_encode([['id'=>$track->id, 'title'=>'Track one', 'version'=>1, 'isrc'=>'IDABC2600002']]);
            app(ReleaseService::class)->store($release, 42, 'draft', $data);
            $this->assertSame('0098765432109', $release->fresh()->upc);
            $this->assertSame('IDABC2600002', $track->fresh()->isrc);
            $this->assertSame(1, $release->fresh()->tracks()->count());
        }
    }
}
