<?php

namespace Tests\Feature;

use App\Jobs\RunLegacyImport;
use App\Models\LegacyImportRun;
use App\Models\User\User;
use App\Services\Legacy\DimensiImporter;
use App\Services\Legacy\ImportPaths;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

require_once __DIR__.'/LegacyImportTest.php';

class LegacyImportSettingsTest extends LegacyImportTest
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/legacy-ui-'.bin2hex(random_bytes(6));
        File::makeDirectory($this->directory.'/inbox', 0700, true);
        File::makeDirectory($this->directory.'/old/uploads', 0700, true);
        config(['legacy-import.allowed_root'=>$this->directory, 'legacy-import.inbox'=>$this->directory.'/inbox']);
        (require base_path('database/migrations/2026_10_31_100000_create_legacy_import_runs.php'))->up();
        Schema::create('user_admin', function ($t) { $t->id(); $t->string('email'); });
        Schema::create('filestorage', function ($t) {
            $t->id();
            foreach (['disk','identifier','original_name','mime_type','type','owner_type','owner_id','profileid','uploaded_at','size'] as $column) $t->text($column)->nullable();
            $t->timestamps();
        });
        Storage::fake('local');
        Bus::fake();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    private function signIn(bool $allowed = true): void
    {
        $user = new class extends User {
            public bool $allowed = true;
            public function hasPermission(string $permission): bool { return $permission === 'admin' || ($this->allowed && $permission === 'manage-automation'); }
        };
        $user->allowed = $allowed;
        $user->forceFill(['id'=>123,'name'=>'Admin','email_verified_at'=>now()]);
        $user->setRelation('profile', null);
        $user->setRelation('unreadNotifications', collect());
        $this->withoutVite();
        $this->actingAs($user);
    }

    private function dump(array $data): string
    {
        $sql = '';
        foreach ($data as $table => $rows) foreach ($rows as $row) {
            $values = array_map(fn ($value) => $value === null ? 'NULL' : "'".str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $value)."'", array_values($row));
            $sql .= 'INSERT INTO `'.$table.'` (`'.implode('`, `', array_keys($row)).'`) VALUES ('.implode(', ', $values).");\n";
        }
        $path = $this->directory.'/inbox/test.sql';
        file_put_contents($path, $sql);
        return $path;
    }

    private function runFor(array $data): LegacyImportRun
    {
        $path = $this->dump($data);
        return LegacyImportRun::create(['sql_path'=>$path,'sql_sha256'=>hash_file('sha256',$path),'media_root'=>$this->directory.'/old']);
    }

    public function test_server_sql_selection_is_private_and_requires_server_permission(): void
    {
        $this->dump($this->data());
        $route = route('admin.system-settings.legacy.store');
        $this->post($route, ['server_sql'=>'test.sql'])->assertRedirect();
        $this->assertSame(0, LegacyImportRun::count());
        $this->signIn(false);
        $this->post($route, ['server_sql'=>'test.sql'])->assertForbidden();
        $this->signIn();
        $this->post($route, ['server_sql'=>'../outside.sql'])->assertSessionHasErrors('sql');
        $this->post($route, ['server_sql'=>'test.sql','media_root'=>$this->directory.'/old'])->assertRedirect();
        $this->assertSame(1, LegacyImportRun::count());
        Bus::assertDispatched(RunLegacyImport::class, fn ($job) => $job->phase === 'trial');
    }

    public function test_trial_rolls_back_and_apply_requires_successful_trial_and_backup(): void
    {
        $run = $this->runFor($this->data());
        (new RunLegacyImport($run->id, 'trial'))->handle();
        $this->assertSame('trial_ready', $run->fresh()->status);
        $this->assertSame(0, DB::table('users')->count());
        $this->assertSame(0, DB::table('legacy_import_records')->count());
        $this->signIn();
        $url = route('admin.system-settings.legacy.action', [$run->id,'apply']);
        $this->post($url)->assertSessionHasErrors('backup_confirmed');
        $this->post($url, ['backup_confirmed'=>1])->assertRedirect();
        $this->post($url, ['backup_confirmed'=>1])->assertStatus(409);
        (new RunLegacyImport($run->id, 'apply'))->handle();
        $this->assertSame('imported', $run->fresh()->status);
        $this->assertSame(1, DB::table('tracks')->count());
    }

    public function test_changed_dump_and_incompatible_schema_fail_without_writes(): void
    {
        $run = $this->runFor($this->data());
        file_put_contents($run->sql_path, '--changed', FILE_APPEND);
        (new RunLegacyImport($run->id,'trial'))->handle();
        $this->assertSame('trial_failed',$run->fresh()->status);
        $this->assertSame(0, DB::table('users')->count());
        $data = $this->data(); unset($data['users'][7]['password_hash']);
        $run = $this->runFor($data);
        (new RunLegacyImport($run->id,'trial'))->handle();
        $this->assertSame('trial_failed',$run->fresh()->status);
        $this->assertStringContainsString('Struktur tabel users', $run->fresh()->error);
    }

    public function test_admin_email_is_never_imported_as_a_customer(): void
    {
        DB::table('user_admin')->insert(['email'=>'legacy@example.test']);
        DB::beginTransaction();
        $stats = (new DimensiImporter)->run($this->data(),str_repeat('a',64));
        $this->assertSame(1,$stats['users.email_conflict_skipped']);
        $this->assertSame(0,DB::table('users')->count());
        DB::rollBack();
    }

    public function test_media_copies_exact_wav_and_cover_and_can_retry_missing_files_without_duplicates(): void
    {
        $data = $this->data();
        $data['releases'][11]['cover_art'] = 'https://old.example/uploads/cover.png';
        $data['tracks'][12]['audio_file'] = '/uploads/master.wav';
        $data['tracks'][12]['audio_clip'] = '/uploads/missing.wav';
        file_put_contents($this->directory.'/old/uploads/cover.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a8R8AAAAASUVORK5CYII='));
        $wav = 'RIFF'.pack('V',36+200).'WAVEfmt '.pack('VvvVVvv',16,1,1,8000,16000,2,16).'data'.pack('V',200).str_repeat("\0",200);
        file_put_contents($this->directory.'/old/uploads/master.wav',$wav);
        $run = $this->runFor($data);
        $run->update(['status'=>'apply_queued']);
        (new RunLegacyImport($run->id,'apply'))->handle();
        $run->refresh()->update(['status'=>'media_queued']);
        for ($i=0;$i<3;$i++) (new RunLegacyImport($run->id,'media'))->handle();
        $this->assertSame('media_issues',$run->fresh()->status);
        $this->assertSame(3,DB::table('filestorage')->count());
        $full = DB::table('filestorage')->where('type','full')->first();
        $this->assertSame($wav,Storage::disk('local')->get($full->identifier));
        $this->assertSame(1,DB::table('legacy_import_media_results')->where('status','issue')->count());
        $this->assertFileExists($this->directory.'/old/uploads/master.wav');
        copy($this->directory.'/old/uploads/master.wav',$this->directory.'/old/uploads/missing.wav');
        $run->refresh()->update(['status'=>'media_queued','media_cursor'=>0,'media_processed'=>0]);
        for ($i=0;$i<3;$i++) (new RunLegacyImport($run->id,'media'))->handle();
        $this->assertSame('complete',$run->fresh()->status);
        $this->assertSame(4,DB::table('filestorage')->count());
        $this->assertSame(0,DB::table('legacy_import_media_results')->where('status','issue')->count());
        file_put_contents($this->directory.'/old/uploads/master.wav',$wav.'changed');
        $run->refresh()->update(['status'=>'media_queued','media_cursor'=>0]);
        for ($i=0;$i<3;$i++) (new RunLegacyImport($run->id,'media'))->handle();
        $this->assertSame('media_issues',$run->fresh()->status);
        $this->assertSame($wav,Storage::disk('local')->get($full->identifier));
    }

    public function test_paths_reject_traversal_empty_root_and_sibling_prefix(): void
    {
        $paths = new ImportPaths;
        foreach (['../secret.wav','%2e%2e/secret.wav', '/uploads/../../secret.wav'] as $reference) {
            try { $paths->media($reference,$this->directory.'/old'); $this->fail('Traversal accepted'); }
            catch (RuntimeException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $this->expectException(RuntimeException::class);
        $paths->mediaRoot('');
    }

    public function test_media_mapping_survives_identical_rows_in_a_different_dump(): void
    {
        $first = $this->runFor($this->data());
        $first->update(['status'=>'apply_queued']);
        (new RunLegacyImport($first->id,'apply'))->handle();
        file_put_contents($first->sql_path,"\n-- Second export\n",FILE_APPEND);
        $second = LegacyImportRun::create(['sql_path'=>$first->sql_path,'sql_sha256'=>hash_file('sha256',$first->sql_path),'media_root'=>$first->media_root,'status'=>'apply_queued']);
        (new RunLegacyImport($second->id,'apply'))->handle();
        $this->assertSame('imported',$second->fresh()->status);
        $this->assertSame(2,DB::table('legacy_import_run_records')->where('run_id',$second->id)->count());
        $this->assertSame(1,DB::table('tracks')->count());
    }
}
