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
        $this->app->forgetInstance(SystemSettings::class);
    }

    protected function tearDown(): void
    {
        DB::purge('landing_testing');
        parent::tearDown();
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

    public function test_public_cover_route_cannot_expose_private_or_future_release_files(): void
    {
        $private = $this->release('Private', 'pending');
        $future = $this->release('Future', 'released', today()->addDay()->toDateString());
        $missing = $this->release('Missing Cover');
        $this->get('/rilis/'.$private.'/cover')->assertNotFound();
        $this->get('/rilis/'.$future.'/cover')->assertNotFound();
        $this->get('/rilis/'.$missing.'/cover')->assertNotFound();
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
