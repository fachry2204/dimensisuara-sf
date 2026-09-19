<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Release;
use App\Models\Track;
use App\Models\System\Version;
use App\Models\System\Genre;
use App\Models\System\Subgenre;
use App\Models\System\Contributor;
use App\Models\System\Production;
use App\Services\Releases\ReleaseService;
use App\Http\Requests\Release\AlbumRequest;
use App\Http\Requests\Release\TrackRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReleaseOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SystemSeeder::class);

        // Create or find a user and assign a profile if necessary
        $this->user = User::first() ?? User::factory()->create();
    }

    /** @test */
    public function it_validates_active_options_successfully()
    {
        $version = Version::where('is_active', true)->first();
        $genre = Genre::where('is_active', true)->first();
        $subgenre = Subgenre::where('genreid', $genre->id)->where('is_active', true)->first();

        $rules = (new AlbumRequest())->rules();

        $data = [
            'title' => 'Test Album',
            'titlelang' => 'id',
            'primaryartists' => [
                ['name' => 'Artist One', 'social' => 'instagram.com/artistone', 'spotify' => '']
            ],
            'recordlabel' => 'Indie Label',
            'version' => $version->id,
            'genre' => $genre->id,
            'subgenre' => $subgenre ? $subgenre->id : null,
            'released' => 0,
            'releaseddate' => null,
            'upc' => null,
            'releaseon' => now()->addDays(15)->format('Y-m-d'),
            'tracknum' => 1,
            'type' => 'single',
            'status' => 'draft',
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes(), 'Validator should pass with active options.');
    }

    /** @test */
    public function it_fails_validation_for_inactive_options()
    {
        // Deactivate a version
        $version = Version::where('is_active', true)->first();
        $version->update(['is_active' => false]);

        $rules = (new AlbumRequest())->rules();

        $data = [
            'title' => 'Test Album',
            'titlelang' => 'id',
            'primaryartists' => [
                ['name' => 'Artist One', 'social' => 'instagram.com/artistone', 'spotify' => '']
            ],
            'recordlabel' => 'Indie Label',
            'version' => $version->id, // inactive
            'genre' => Genre::where('is_active', true)->first()->id,
            'subgenre' => null,
            'released' => 0,
            'releaseddate' => null,
            'upc' => null,
            'releaseon' => now()->addDays(15)->format('Y-m-d'),
            'tracknum' => 1,
            'type' => 'single',
            'status' => 'draft',
        ];

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->passes(), 'Validator should fail when version is inactive.');
        $this->assertArrayHasKey('version', $validator->errors()->toArray());
    }

    /** @test */
    public function it_tolerates_inactive_options_if_already_saved_on_model()
    {
        // Deactivate a version
        $version = Version::where('is_active', true)->first();
        $version->update(['is_active' => false]);

        // Create dummy release that already has this version
        $release = new Release();
        $release->version = $version->id;

        // rules() with context
        $rules = (new AlbumRequest())->rules($release);

        $data = [
            'title' => 'Test Album',
            'titlelang' => 'id',
            'primaryartists' => [
                ['name' => 'Artist One', 'social' => 'instagram.com/artistone', 'spotify' => '']
            ],
            'recordlabel' => 'Indie Label',
            'version' => $version->id, // inactive but matches current model version
            'genre' => Genre::where('is_active', true)->first()->id,
            'subgenre' => null,
            'released' => 0,
            'releaseddate' => null,
            'upc' => null,
            'releaseon' => now()->addDays(15)->format('Y-m-d'),
            'tracknum' => 1,
            'type' => 'single',
            'status' => 'draft',
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes(), 'Validator should pass because inactive version matches model version.');
    }

    /** @test */
    public function test_custom_validation_attributes_and_translations()
    {
        // 1. Test AlbumRequest custom attributes
        $albumRequest = new AlbumRequest();
        $albumAttributes = $albumRequest->attributes();
        $this->assertArrayHasKey('title', $albumAttributes);
        $this->assertArrayHasKey('titlelang', $albumAttributes);
        $this->assertArrayHasKey('version', $albumAttributes);
        $this->assertArrayHasKey('genre', $albumAttributes);
        $this->assertArrayHasKey('subgenre', $albumAttributes);

        // 2. Test TrackRequest custom attributes
        $trackRequest = new TrackRequest();
        $trackAttributes = $trackRequest->attributes();
        $this->assertArrayHasKey('title', $trackAttributes);
        $this->assertArrayHasKey('titlelang', $trackAttributes);
        $this->assertArrayHasKey('version', $trackAttributes);
        $this->assertArrayHasKey('genre', $trackAttributes);
        $this->assertArrayHasKey('subgenre', $trackAttributes);
        $this->assertArrayHasKey('songwriters', $trackAttributes);

        // 3. Test Indonesian translations load correctly
        app()->setLocale('id');
        $this->assertEquals('Judul album/single', __('validation.attributes.album_title'));
        $this->assertEquals('Judul lagu', __('validation.attributes.track_title'));
        $this->assertEquals('Penulis lagu', __('validation.attributes.songwriters'));

        // 4. Test English translations load correctly
        app()->setLocale('en');
        $this->assertEquals('Title', __('validation.attributes.album_title'));
        $this->assertEquals('Title', __('validation.attributes.track_title'));
        $this->assertEquals('Songwriters', __('validation.attributes.songwriters'));
    }
}
