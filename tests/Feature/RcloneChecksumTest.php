<?php

namespace Tests\Feature;

use App\Services\RcloneDrive;
use App\Services\SystemSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RcloneChecksumTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'rclone_checksum_test', 'database.connections.rclone_checksum_test'=>[
            'driver'=>'sqlite', 'database'=>':memory:', 'prefix'=>'',
        ]]);
        Schema::create('system_settings', function ($table) {
            $table->string('key')->primary(); $table->text('value'); $table->timestamps();
        });
        $this->app->forgetInstance(SystemSettings::class);
        app(SystemSettings::class)->save(['drive_remote'=>'gdrive', 'drive_folder'=>'dimensisuara', 'drive_binary'=>'rclone', 'drive_config'=>'']);
    }

    protected function tearDown(): void
    {
        DB::purge('rclone_checksum_test');
        parent::tearDown();
    }

    public static function hashNames(): array
    {
        return [['md5'], ['MD5'], ['Md5']];
    }

    #[DataProvider('hashNames')]
    public function test_file_stat_normalizes_hash_names_without_changing_the_checksum(string $key): void
    {
        $checksum = md5('original bytes');
        $client = Mockery::mock(RcloneDrive::class)->makePartial();
        $client->shouldReceive('run')->once()->with(['lsjson','gdrive:dimensisuara/test.wav','--stat','--hash','--hash-type','MD5'])
            ->andReturn(json_encode(['Size'=>14, 'IsDir'=>false, 'Hashes'=>[$key=>$checksum]]));
        $stat = $client->stat('gdrive:dimensisuara/test.wav');
        $this->assertSame($checksum, $stat['Hashes']['MD5']);
        $this->assertSame(14, $stat['Size']);
    }

    #[DataProvider('hashNames')]
    public function test_connection_verifies_real_probe_content_and_cleans_up_for_each_hash_casing(string $key): void
    {
        $this->probe($key, false);
    }

    public function test_connection_still_rejects_an_incorrect_checksum_and_cleans_up(): void
    {
        $this->probe('md5', true);
    }

    private function probe(string $key, bool $badChecksum): void
    {
        $content = ''; $temporary = null; $destination = null; $deleted = false;
        $client = Mockery::mock(RcloneDrive::class)->makePartial();
        $client->shouldReceive('run')->andReturnUsing(function (array $args) use ($key, $badChecksum, &$content, &$temporary, &$destination, &$deleted) {
            if ($args[0] === 'listremotes') return "gdrive: drive\n";
            if ($args[0] === 'copyto') {
                $temporary = $args[1]; $destination = $args[2];
                $content = file_get_contents($temporary);
                $this->assertSame(32, strlen($content));
                $this->assertStringStartsWith('gdrive:dimensisuara/.connection-test-', $destination);
                $this->assertContains('--immutable', $args);
                return '';
            }
            if ($args[0] === 'deletefile') {
                $this->assertSame($destination, $args[1]); $deleted = true;
                return '';
            }
            if ($args === ['lsjson','gdrive:','--stat']) return json_encode(['IsDir'=>true]);
            $this->assertSame($destination, $args[1]);
            return json_encode(['IsDir'=>false,'Size'=>strlen($content),'Hashes'=>[$key=>$badChecksum ? str_repeat('0',32) : md5($content)]]);
        });
        $failure = null;
        try { $client->testConnection(); }
        catch (\RuntimeException $exception) { $failure = $exception; }
        $this->assertSame($badChecksum, $failure !== null);
        if ($badChecksum) $this->assertSame('Tes verifikasi Google Drive gagal.', $failure->getMessage());
        $this->assertTrue($deleted);
        $this->assertFileDoesNotExist($temporary);
    }
}
