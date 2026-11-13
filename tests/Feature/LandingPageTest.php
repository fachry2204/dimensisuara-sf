<?php

namespace Tests\Feature;

use App\Models\Release;
use App\Services\SystemSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'landing_testing','database.connections.landing_testing'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>false]]);
        Schema::create('system_settings', function ($t) { $t->string('key')->primary(); $t->text('value'); $t->timestamps(); });
        Schema::create('releases', function ($t) { $t->id(); $t->string('title'); $t->text('primaryartists'); $t->string('status'); $t->string('type')->default('single'); $t->date('releaseddate')->nullable(); $t->date('finalreleaseddate')->nullable(); $t->timestamps(); });
        Schema::create('filestorage', function ($t) { $t->id(); $t->string('disk'); $t->string('identifier'); $t->string('type'); $t->string('owner_type'); $t->unsignedBigInteger('owner_id'); $t->timestamps(); });
        Schema::table('releases', fn ($t) => $t->date('releaseon')->nullable());
        Schema::table('releases', fn ($t) => $t->timestamp('verified_at')->nullable());
        $this->app->forgetInstance(SystemSettings::class);
    }

    protected function tearDown(): void
    {
        DB::purge('landing_testing');
        parent::tearDown();
    }

    public function test_drive_artwork_with_missing_mime_is_listed_and_validated_using_drive_metadata(): void
    {
        Schema::table('filestorage',fn ($t)=>$t->string('mime_type')->nullable());
        $id=$this->release('Archived cover');
        DB::table('filestorage')->insert(['disk'=>'rclone','identifier'=>'gdrive:catalogue/cover.jpeg','type'=>'cover','owner_type'=>Release::class,'owner_id'=>$id]);
        $this->get('/rilis')->assertOk()->assertSee(route('landing.cover',$id),false);
        $client=\Mockery::mock(\App\Services\RcloneDrive::class)->makePartial();
        $client->shouldReceive('stat')->andReturn(['Size'=>4,'MimeType'=>'image/jpeg','IsDir'=>false]);
        $client->shouldReceive('stream')->once()->with('gdrive:catalogue/cover.jpeg',0,4)->andReturnUsing(fn()=>print('jpeg'));
        $this->app->instance(\App\Services\RcloneDrive::class,$client);
        $this->get(route('landing.cover',$id))->assertOk()->assertHeader('Content-Type','image/jpeg')->assertStreamedContent('jpeg');
    }

    public function test_public_cover_falls_back_when_drive_thumbnail_is_unavailable_and_rejects_unsafe_mime(): void
    {
        Schema::table('filestorage',fn ($t)=>$t->string('mime_type')->nullable());
        $id=$this->release('Thumbnail fallback');
        foreach (['thumbnail','cover'] as $type) DB::table('filestorage')->insert(['disk'=>'rclone','identifier'=>'gdrive:catalogue/'.$type.'.png','type'=>$type,'owner_type'=>Release::class,'owner_id'=>$id,'mime_type'=>'image/png']);
        $client=\Mockery::mock(\App\Services\RcloneDrive::class)->makePartial();
        $client->shouldReceive('stat')->with('gdrive:catalogue/thumbnail.png')->andThrow(new \App\Exceptions\DriveSetupException('File tidak ditemukan.'));
        $client->shouldReceive('stat')->with('gdrive:catalogue/cover.png')->andReturn(['Size'=>4,'MimeType'=>'image/png']);
        $this->app->instance(\App\Services\RcloneDrive::class,$client);
        $this->get(route('landing.cover',$id))->assertOk()->assertHeader('Content-Type','image/png');
        $unsafe=\Mockery::mock(\App\Services\RcloneDrive::class)->makePartial();
        $unsafe->shouldReceive('stat')->andReturn(['Size'=>4,'MimeType'=>'text/html']);
        $unsafe->shouldNotReceive('stream');
        $this->app->instance(\App\Services\RcloneDrive::class,$unsafe);
        $this->get(route('landing.cover',$id))->assertNotFound();
    }

    public function test_artwork_diagnostic_reports_fixed_drive_error_without_changing_file(): void
    {
        $id=$this->release('Diagnostic');
        DB::table('filestorage')->insert(['disk'=>'rclone','identifier'=>'gdrive:catalogue/cover.jpeg','type'=>'cover','owner_type'=>Release::class,'owner_id'=>$id]);
        $client=\Mockery::mock(\App\Services\RcloneDrive::class);
        $client->shouldReceive('stat')->once()->andThrow(new \App\Exceptions\DriveSetupException('Akses ditolak. (lsjson, kode 7)'));
        $this->app->instance(\App\Services\RcloneDrive::class,$client);
        $this->artisan('drive:inspect-artwork',['release'=>$id])->expectsOutput('Akses ditolak. (lsjson, kode 7)')->assertFailed();
        $this->assertSame('gdrive:catalogue/cover.jpeg',DB::table('filestorage')->value('identifier'));
    }

    private function release(string $title, string $status = 'released', ?string $date = null): int
    {
        return DB::table('releases')->insertGetId(['title'=>$title,'primaryartists'=>'[{"name":"Test Artist"}]','status'=>$status,'releaseddate'=>$date ?? today()->subDay()->toDateString(),'type'=>'single']);
    }

    public function test_guest_sees_only_released_catalogue_and_database_contacts(): void
    {
        app(SystemSettings::class)->save(['company_email'=>'music@example.test','company_phone'=>'081234567890']);
        $this->release('Public Song');
        $this->release('Private Draft', 'draft');
        $this->release('Future Song', 'released', today()->addDay()->toDateString());
        $id = $this->release('Final Date Future');
        DB::table('releases')->where('id',$id)->update(['finalreleaseddate'=>today()->addWeek()->toDateString()]);
        $this->get('/')->assertOk()->assertSee('Public Song')->assertSee('Test Artist')->assertSee('music@example.test')
            ->assertSee('https://wa.me/6281234567890', false)->assertDontSee('Private Draft')->assertDontSee('Future Song')->assertDontSee('Final Date Future');
    }

    public function test_empty_catalogue_remains_a_complete_public_page(): void
    {
        $this->get('/')->assertOk()->assertSee('Karya berikutnya bisa jadi milik Anda.')->assertSee(route('register'), false)->assertSee(route('login'), false);
    }

    public function test_new_published_releases_use_planned_date_and_current_artist_data(): void
    {
        $older = $this->release('Older Catalogue', date: '2025-01-01');
        $new = $this->release('New Published', 'pending');
        DB::table('releases')->where('id', $new)->update([
            'releaseddate'=>null, 'releaseon'=>today()->toDateString(),
            'primaryartists'=>'[{"name":"Current Artist"}]',
        ]);
        $this->get('/')->assertOk()->assertDontSee('New Published');
        DB::table('releases')->where('id', $new)->update(['status'=>'released']);
        foreach (['/', '/rilis'] as $url) {
            $response = $this->get($url)->assertOk()->assertSee('New Published')->assertSee('Current Artist');
            $response->assertViewHas('releases', fn ($rows) => $rows->first()->id === $new);
            $response->assertSee('Dirilis '.today()->locale('id')->translatedFormat('d F Y'));
        }
        DB::table('releases')->where('id', $new)->update(['primaryartists'=>'[{"name":"Updated Artist"}]']);
        $this->get('/')->assertOk()->assertSee('Updated Artist')->assertDontSee('Current Artist');
    }

    public function test_published_catalogue_hides_future_releases_and_keeps_date_precedence(): void
    {
        $old = $this->release('Original Old', date: '2020-01-01');
        $rerelease = $this->release('Current Rerelease', date: '2019-01-01');
        DB::table('releases')->where('id', $rerelease)->update(['releaseon'=>today()->toDateString()]);
        $future = $this->release('Scheduled Publication', date: '2018-01-01');
        DB::table('releases')->where('id', $future)->update(['releaseon'=>today()->addWeek()->toDateString()]);
        $final = $this->release('Final Overrides Plan', date: '2017-01-01');
        DB::table('releases')->where('id', $final)->update(['releaseon'=>today()->toDateString(), 'finalreleaseddate'=>today()->addWeek()->toDateString()]);
        foreach (['/', '/rilis'] as $url) {
            $this->get($url)->assertOk()->assertSee('Current Rerelease')->assertSee('Original Old')
                ->assertDontSee('Scheduled Publication')->assertDontSee('Final Overrides Plan')
                ->assertViewHas('releases', fn ($rows) => $rows->firstWhere('id', $rerelease)->public_release_date->toDateString() === today()->toDateString());
        }
        $this->get('/rilis/'.$future.'/cover')->assertNotFound();
        $this->get('/rilis/'.$final.'/cover')->assertNotFound();
    }

    public function test_latest_six_include_new_publications_with_normalized_artist_storage(): void
    {
        for ($i=0; $i<7; $i++) $this->release('Older Item '.$i, date: today()->subDays(10)->toDateString());
        $latest = $this->release('Latest Live Release');
        DB::table('releases')->where('id', $latest)->update(['releaseddate'=>null, 'releaseon'=>today()->toDateString()]);
        (require database_path('migrations/2026_11_01_130000_normalize_application_json_fields.php'))->up();
        \App\Database\RelationalFields::write('releases', $latest, 'primaryartists', [['name'=>'Latest Live Artist']]);
        $this->get('/')->assertOk()->assertSee('Latest Live Release')->assertSee('Latest Live Artist')
            ->assertDontSee('Older Item 0')->assertDontSee('Older Item 1')
            ->assertViewHas('releases', fn ($rows) => $rows->count() === 6 && $rows->first()->id === $latest);
        $this->get('/rilis')->assertOk()->assertSee('Latest Live Artist')->assertSee('Older Item 0');
    }

    public function test_newest_published_release_is_sorted_by_approval_time_instead_of_release_date(): void
    {
        $newestDate = $this->release('Recent Song Date', date: today()->toDateString());
        DB::table('releases')->where('id', $newestDate)->update(['verified_at'=>now()->subDays(5), 'updated_at'=>now()]);
        for ($i=0; $i<6; $i++) {
            $id = $this->release('Previously Published '.$i);
            DB::table('releases')->where('id', $id)->update(['verified_at'=>now()->subDays(4)]);
        }
        $approved = $this->release('Just Published Old Song', date: '2020-01-01');
        DB::table('releases')->where('id', $approved)->update(['verified_at'=>now()->subMinute()]);
        foreach (['/', '/rilis'] as $url) {
            $this->get($url)->assertOk()->assertSee('Just Published Old Song')
                ->assertViewHas('releases', fn ($rows) => $rows->first()->id === $approved);
        }
        // Editing metadata on an already published release does not change its approval time.
        DB::table('releases')->where('id', $newestDate)->update(['updated_at'=>now()->addMinute()]);
        $this->get('/')->assertOk()->assertViewHas('releases', fn ($rows) => $rows->first()->id === $approved);
    }

    public function test_imported_publications_without_approval_time_have_a_stable_timestamp_fallback(): void
    {
        $older = $this->release('Imported Old', date: today()->toDateString());
        $newer = $this->release('Imported New', date: '2020-01-01');
        DB::table('releases')->where('id', $older)->update(['created_at'=>now()->subMonth(), 'updated_at'=>now()->subWeek()]);
        DB::table('releases')->where('id', $newer)->update(['created_at'=>now()->subMonth(), 'updated_at'=>now()->subDay()]);
        $this->get('/')->assertOk()->assertViewHas('releases', fn ($rows) => $rows->first()->id === $newer);
    }

    public function test_published_little_soul_is_hidden_until_its_scheduled_release_date(): void
    {
        $id = $this->release('Little Soul Endless Love', 'pending');
        DB::table('releases')->where('id', $id)->update(['releaseon'=>today()->addDays(9)->toDateString(), 'releaseddate'=>null]);
        $this->get('/rilis?q=Little%20Soul')->assertOk()->assertDontSee('Lihat rilis Little Soul Endless Love');
        DB::table('releases')->where('id', $id)->update(['status'=>'released', 'verified_at'=>now()]);
        foreach (['/', '/rilis?q=Little%20Soul'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('Lihat rilis Little Soul Endless Love')
                ->assertViewHas('releases', fn ($rows) => $rows->count() === 0);
        }
        $this->get('/rilis/'.$id.'/cover')->assertNotFound();
        $releaseDay = today()->addDays(9);
        try {
            $this->travelTo($releaseDay);
            foreach (['/', '/rilis?q=Little%20Soul'] as $url) {
                $this->get($url)->assertOk()->assertSee('Lihat rilis Little Soul Endless Love')
                    ->assertViewHas('releases', fn ($rows) => $rows->first()->id === $id);
            }
        } finally {
            $this->travelBack();
        }
        DB::table('releases')->where('id', $id)->update(['releaseon'=>null]);
        $this->get('/')->assertOk()->assertDontSee('Little Soul Endless Love');
    }

    public function test_all_releases_button_opens_a_paginated_public_catalogue(): void
    {
        for ($i=1;$i<=26;$i++) $this->release('Public Track '.str_pad((string) $i,2,'0',STR_PAD_LEFT));
        $this->release('Unreleased Private Track','pending');
        $this->release('Future Private Track','uploading',today()->addWeek()->toDateString());
        $this->get('/')->assertOk()->assertSee('Lihat Semua Rilis')->assertSee(route('landing.releases'),false)
            ->assertDontSee('show-releases')->assertSee('Public Track 26')->assertDontSee('Public Track 01');
        $this->get('/rilis')->assertOk()->assertSee('Semua Rilis')->assertSee('26 rilisan')
            ->assertSee('Public Track 26')->assertDontSee('Public Track 01')
            ->assertDontSee('Unreleased Private Track')->assertDontSee('Future Private Track')
            ->assertSee('page=2',false)->assertSee('Halaman 1 dari 2');
        $this->get('/rilis?page=2')->assertOk()->assertSee('Public Track 01')->assertSee('Public Track 02')
            ->assertDontSee('Public Track 26')->assertSee('Halaman 2 dari 2');
    }

    public function test_public_cover_route_cannot_expose_unpublished_release_files(): void
    {
        $private = $this->release('Private', 'pending');
        $future = $this->release('Future', 'uploading', today()->addDay()->toDateString());
        $missing = $this->release('Missing Cover');
        $this->get('/rilis/'.$private.'/cover')->assertNotFound();
        $this->get('/rilis/'.$future.'/cover')->assertNotFound();
        $this->get('/rilis/'.$missing.'/cover')->assertNotFound();
    }

    public function test_catalogue_search_matches_keywords_across_title_artist_and_all_pages(): void
    {
        for ($i=1; $i<=30; $i++) $this->release('Public Music '.$i);
        $this->release('Hidden Music', 'pending');
        $this->release('Future Music', date: today()->addWeek()->toDateString());
        $this->get('/rilis?q=ARTIST%20public')->assertOk()->assertSee('30 hasil untuk')
            ->assertSee('Public Music 30')->assertDontSee('Public Music 1</h3>', false)
            ->assertDontSee('Hidden Music')->assertDontSee('Future Music')
            ->assertSee('q=ARTIST%20public&amp;page=2#rilis', false)
            ->assertSee('aria-current="page"', false)->assertSee('aria-label="Halaman 2"', false);
        $this->get('/rilis?q=ARTIST%20public&page=2')->assertOk()->assertSee('Public Music 1')
            ->assertSee('Halaman 2 dari 2')->assertDontSee('Public Music 30');
        $this->get('/rilis?q=music%2030')->assertOk()->assertSee('1 hasil untuk')->assertSee('Public Music 30');
        $this->get('/rilis?q=NoMatch')->assertOk()->assertSee('Rilisan belum ditemukan.')->assertSee('Reset pencarian');
        $this->get('/rilis?q=%20%20%20')->assertOk()->assertSee('30 rilisan');
        $this->getJson('/rilis?q[]=invalid')->assertUnprocessable();
        $this->getJson('/rilis?q='.str_repeat('a', 121))->assertUnprocessable();
    }

    public function test_catalogue_search_supports_normalized_artists_and_literal_wildcards(): void
    {
        $id = $this->release('Lagu 100%_Love');
        $this->release('Other Song');
        (require database_path('migrations/2026_11_01_130000_normalize_application_json_fields.php'))->up();
        \App\Database\RelationalFields::write('releases', $id, 'primaryartists', [['name'=>'Bayu Wafa', 'spotify'=>'private-metadata-match']]);
        $this->get('/rilis?q=WAFA%20lagu')->assertOk()->assertSee('Lagu 100%_Love')->assertSee('Bayu Wafa')->assertDontSee('Other Song');
        $this->get('/rilis?q='.urlencode('%_'))->assertOk()->assertSee('1 hasil untuk')->assertSee('Lagu 100%_Love');
        $this->get('/rilis?q=private-metadata-match')->assertOk()->assertSee('0 hasil untuk')->assertDontSee('Lagu 100%_Love');
        $this->get('/rilis?q='.urlencode('<script>alert(1)</script>'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_numbered_navigation_is_bounded_even_for_pages_outside_the_catalogue(): void
    {
        for ($i=0; $i<250; $i++) $this->release('Catalogue '.$i);
        $this->get('/rilis?page=6')->assertOk()->assertSee('aria-label="Halaman 1"', false)
            ->assertSee('aria-label="Halaman 5"', false)->assertSee('aria-label="Halaman 6"', false)
            ->assertSee('aria-label="Halaman 7"', false)->assertSee('aria-label="Halaman 11"', false)
            ->assertDontSee('aria-label="Halaman 3"', false);
        $this->get('/rilis?page=999999')->assertOk()->assertSee('aria-label="Halaman 11"', false);
    }

    public function test_cover_endpoint_serves_only_a_real_image_from_the_cover_relation(): void
    {
        $id = $this->release('With Cover');
        $directory = sys_get_temp_dir().'/dimensi-landing-'.bin2hex(random_bytes(5));
        mkdir($directory);
        config(['filesystems.disks.local.root'=>$directory]);
        Storage::forgetDisk('local');
        $path = $directory.'/cover.png';
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aZYkAAAAASUVORK5CYII='));
        try {
            DB::table('filestorage')->insert(['disk'=>'local','identifier'=>'cover.png','type'=>'cover','owner_type'=>Release::class,'owner_id'=>$id]);
            $this->get('/rilis/'.$id.'/cover')->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
            file_put_contents($path, '<?php private_data();');
            $this->get('/rilis/'.$id.'/cover')->assertNotFound();
            DB::table('filestorage')->update(['identifier'=>'../outside.png']);
            $this->get('/rilis/'.$id.'/cover')->assertNotFound();
        } finally {
            unlink($path);
            rmdir($directory);
            Storage::forgetDisk('local');
        }
    }
}
