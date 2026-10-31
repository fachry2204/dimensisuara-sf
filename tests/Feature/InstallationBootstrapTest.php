<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class InstallationBootstrapTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = base_path('bootstrap/cache/dimensi-install-'.bin2hex(random_bytes(6)));
        File::makeDirectory($this->directory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    private function runSetupCommand(array $arguments): Process
    {
        $cacheDirectory = 'bootstrap/cache/'.basename($this->directory);
        $process = new Process([PHP_BINARY, base_path('artisan'), ...$arguments], base_path(), [
            'APP_ENV'=>'production',
            'APP_KEY'=>'',
            'DB_CONNECTION'=>'sqlite',
            'DB_DATABASE'=>$this->directory.'/does-not-exist.sqlite',
            'APP_CONFIG_CACHE'=>$cacheDirectory.'/config.php',
            'APP_PACKAGES_CACHE'=>$cacheDirectory.'/packages.php',
            'APP_SERVICES_CACHE'=>$cacheDirectory.'/services.php',
            'APP_ROUTES_CACHE'=>$cacheDirectory.'/routes.php',
            'APP_EVENTS_CACHE'=>$cacheDirectory.'/events.php',
        ]);
        $process->run();
        return $process;
    }

    public function test_composer_package_discovery_works_before_database_and_key_configuration(): void
    {
        $process = $this->runSetupCommand(['package:discover','--no-ansi']);
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
        $this->assertFileExists($this->directory.'/packages.php');
        $this->assertFileDoesNotExist($this->directory.'/does-not-exist.sqlite');
    }

    public function test_key_generation_and_configuration_cache_do_not_require_database(): void
    {
        $key = $this->runSetupCommand(['key:generate','--show','--no-ansi']);
        $this->assertTrue($key->isSuccessful(), $key->getErrorOutput().$key->getOutput());
        $cache = $this->runSetupCommand(['config:cache','--no-ansi']);
        $this->assertTrue($cache->isSuccessful(), $cache->getErrorOutput().$cache->getOutput());
        $this->assertFileExists($this->directory.'/config.php');
        $this->assertFileDoesNotExist($this->directory.'/does-not-exist.sqlite');
    }
}
