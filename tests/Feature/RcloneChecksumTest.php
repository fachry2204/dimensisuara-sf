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
                $this->assertSame(realpath(storage_path('app/temp')),realpath(dirname($temporary)));
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
        if ($badChecksum) $this->assertStringContainsString('ukuran atau checksum MD5 salinan tidak cocok', $failure->getMessage());
        $this->assertTrue($deleted);
        $this->assertFileDoesNotExist($temporary);
    }

    public static function processFailures(): array
    {
        return [
            ['immutable file modified secret-test-token','Salinan tujuan sudah ada tetapi tidak cocok'],
            ['corrupted on transfer secret-test-token','Rclone mendeteksi ukuran atau checksum'],
            ['object not found secret-test-token','File atau folder Google Drive tidak ditemukan'],
            ['invalid_grant token revoked secret-test-token', 'Otorisasi Google Drive ditolak'],
            ['Failed to save config: permission denied secret-test-token', 'Akses ditolak'],
            ['storageQuotaExceeded secret-test-token', 'Kuota penyimpanan'],
            ['dial tcp: no such host secret-test-token', 'Koneksi server'],
            ['Call to undefined function proc_get_status secret-test-token', 'PHP tidak dapat menjalankan'],
            ['didn\'t find section in config file secret-test-token', 'Konfigurasi rclone'],
            ['unknown flag --hash-type secret-test-token', 'Versi rclone'],
            ['unrecognized failure secret-test-token', 'Rclone gagal menyelesaikan'],
        ];
    }

    #[DataProvider('processFailures')]
    public function test_process_errors_return_fixed_diagnostics_without_raw_secrets(string $stderr, string $expected): void
    {
        $process=Mockery::mock(\Symfony\Component\Process\Process::class);
        $process->shouldReceive('run')->once()->andReturn(1);
        $process->shouldReceive('isSuccessful')->once()->andReturnFalse();
        $process->shouldReceive('getErrorOutput')->once()->andReturn($stderr);
        $process->shouldReceive('getExitCode')->once()->andReturn(7);
        $client=Mockery::mock(RcloneDrive::class)->makePartial();
        $client->shouldReceive('process')->once()->andReturn($process);
        try { $client->run(['listremotes','--long']); $this->fail('Expected failure'); }
        catch (\App\Exceptions\DriveSetupException $error) {
            $this->assertStringContainsString($expected,$error->getMessage());
            $this->assertStringNotContainsString('secret-test-token',$error->getMessage());
            $this->assertStringContainsString('(listremotes, kode 7)',$error->getMessage());
        }
    }

    public function test_failed_upload_reports_stage_and_still_cleans_probe_without_exposing_error_text(): void
    {
        $temporary=null; $deleted=false;
        $client=Mockery::mock(RcloneDrive::class)->makePartial();
        $client->shouldReceive('run')->andReturnUsing(function ($args) use (&$temporary,&$deleted) {
            if ($args[0]==='listremotes') return "gdrive: drive\n";
            if ($args[0]==='lsjson') return '{"IsDir":true}';
            if ($args[0]==='copyto') { $temporary=$args[1]; throw new \App\Exceptions\DriveSetupException('Akses ditolak.'); }
            if ($args[0]==='deletefile') { $deleted=true; return ''; }
            throw new \RuntimeException('secret-test-token');
        });
        try { $client->testConnection(); $this->fail('Expected upload failure'); }
        catch (\App\Exceptions\DriveSetupException $error) {
            $this->assertStringContainsString('tahap mengunggah file uji ke folder tujuan',$error->getMessage());
            $this->assertStringContainsString('Akses ditolak',$error->getMessage());
        }
        $this->assertTrue($deleted);
        $this->assertFileDoesNotExist($temporary);
    }

    public function test_connection_diagnostics_reach_settings_error_and_do_not_mark_connection_successful(): void
    {
        $client=Mockery::mock(RcloneDrive::class);
        $message='Tes koneksi gagal pada tahap membaca daftar remote. PHP tidak dapat menjalankan rclone.';
        $client->shouldReceive('testConnection')->once()->andThrow(new \App\Exceptions\DriveSetupException($message));
        $response=app(\App\Http\Controllers\Admin\GoogleDriveSettingsController::class)->test(app(SystemSettings::class),$client);
        $this->assertSame($message,session('errors')->first('drive'));
        $this->assertSame('failed',app(SystemSettings::class)->get('drive_connection_status'));
        $this->assertNotEmpty(app(SystemSettings::class)->get('drive_checked_at'));
        $this->assertTrue($response->isRedirect());
    }

    public function test_php_process_exception_is_sanitized_before_display(): void
    {
        $process=Mockery::mock(\Symfony\Component\Process\Process::class);
        $process->shouldReceive('run')->once()->andThrow(new \RuntimeException('proc_get_status disabled secret-test-token'));
        $client=Mockery::mock(RcloneDrive::class)->makePartial();
        $client->shouldReceive('process')->once()->andReturn($process);
        try { $client->run(['listremotes','--long']); $this->fail('Expected PHP process failure'); }
        catch (\App\Exceptions\DriveSetupException $error) {
            $this->assertStringContainsString('PHP tidak dapat menjalankan rclone',$error->getMessage());
            $this->assertStringNotContainsString('secret-test-token',$error->getMessage());
        }
    }

    public function test_php_file_warning_reports_preflight_stage_and_basedir_without_exposing_path(): void
    {
        $client=Mockery::mock(RcloneDrive::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $client->shouldReceive('checkSetup')->once()->andThrow(new \ErrorException('is_file(): open_basedir restriction in effect. private-path-and-token'));
        $client->shouldNotReceive('run');
        try { $client->testConnection(); $this->fail('Expected preflight failure'); }
        catch (\App\Exceptions\DriveSetupException $error) {
            $this->assertStringContainsString('tahap memeriksa pengaturan PHP dan akses file',$error->getMessage());
            $this->assertStringContainsString('open_basedir',$error->getMessage());
            $this->assertStringNotContainsString('private-path-and-token',$error->getMessage());
        }
    }

    public function test_local_probe_failure_reports_stage_without_uploading(): void
    {
        $client=Mockery::mock(RcloneDrive::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $client->shouldReceive('run')->with(['listremotes','--long'])->once()->andReturn("gdrive: drive\n");
        $client->shouldReceive('run')->with(['lsjson','gdrive:','--stat'])->once()->andReturn('{}');
        $client->shouldReceive('createProbe')->once()->andThrow(new \ErrorException('tempnam(): permission denied private-path-and-token'));
        try { $client->testConnection(); $this->fail('Expected temporary file failure'); }
        catch (\App\Exceptions\DriveSetupException $error) {
            $this->assertStringContainsString('tahap membuat file uji lokal',$error->getMessage());
            $this->assertStringContainsString('Akses ditolak',$error->getMessage());
            $this->assertStringNotContainsString('private-path-and-token',$error->getMessage());
        }
    }

    public function test_unexpected_failure_logs_only_exception_location_and_shows_safe_fallback(): void
    {
        $failure = new \RuntimeException('private-refresh-token');
        \Illuminate\Support\Facades\Log::shouldReceive('warning')->once()->with('Tes Google Drive gagal sebelum diagnosis selesai.',Mockery::on(function ($context) {
            return $context['exception_type'] === \RuntimeException::class && isset($context['source_file'],$context['source_line'])
                && !str_contains(json_encode($context),'private-refresh-token');
        }));
        $client=Mockery::mock(RcloneDrive::class);
        $client->shouldReceive('testConnection')->once()->andThrow($failure);
        app(\App\Http\Controllers\Admin\GoogleDriveSettingsController::class)->test(app(SystemSettings::class),$client);
        $this->assertStringContainsString('pemeriksaan PHP aplikasi',session('errors')->first('drive'));
        $this->assertStringNotContainsString('private-refresh-token',session('errors')->first('drive'));
    }
}
