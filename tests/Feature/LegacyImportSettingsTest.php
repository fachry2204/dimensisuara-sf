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
        for ($i=0;$i<4;$i++) (new RunLegacyImport($run->id,'media'))->handle();
        $this->assertSame('media_issues',$run->fresh()->status);
        $this->assertSame(3,DB::table('filestorage')->count());
        $full = DB::table('filestorage')->where('type','full')->first();
        $this->assertSame($wav,Storage::disk('local')->get($full->identifier));
        $this->assertSame(1,DB::table('legacy_import_media_results')->where('status','issue')->count());
        $this->assertFileExists($this->directory.'/old/uploads/master.wav');
        copy($this->directory.'/old/uploads/master.wav',$this->directory.'/old/uploads/missing.wav');
        $run->refresh()->update(['status'=>'media_queued','media_cursor'=>0,'media_processed'=>0]);
        for ($i=0;$i<4;$i++) (new RunLegacyImport($run->id,'media'))->handle();
        $this->assertSame('complete',$run->fresh()->status);
        $this->assertSame(4,DB::table('filestorage')->count());
        $this->assertSame(0,DB::table('legacy_import_media_results')->where('status','issue')->count());
        file_put_contents($this->directory.'/old/uploads/master.wav',$wav.'changed');
        $run->refresh()->update(['status'=>'media_queued','media_cursor'=>0]);
        for ($i=0;$i<4;$i++) (new RunLegacyImport($run->id,'media'))->handle();
        $this->assertSame('media_issues',$run->fresh()->status);
        $this->assertSame($wav,Storage::disk('local')->get($full->identifier));
    }

    public function test_existing_import_can_backfill_identity_documents_and_retry_without_duplicate_users_or_files(): void
    {
        $data = $this->data();
        $data['users'][7]['account_type'] = 'COMPANY';
        $documents = ['ktp_doc_path'=>'ktp.png','npwp_doc_path'=>'npwp.pdf','nib_doc_path'=>'nib.png',
            'kemenkumham_doc_path'=>'sk.pdf','signature_doc_path'=>'signature.png','contract_doc_path'=>'contract.docx'];
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a8R8AAAAASUVORK5CYII=');
        foreach ($documents as $field=>$name) {
            $data['users'][7][$field] = '/uploads/'.$name;
            if (str_ends_with($name,'.png')) file_put_contents($this->directory.'/old/uploads/'.$name,$png);
            if (str_ends_with($name,'.pdf')) file_put_contents($this->directory.'/old/uploads/'.$name,"%PDF-1.4\n%%EOF");
        }
        $zip = new \ZipArchive;
        $zip->open($this->directory.'/old/uploads/contract.docx',\ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml','<Types/>'); $zip->addFromString('word/document.xml','<document/>'); $zip->close();
        $run = $this->runFor($data);
        (new RunLegacyImport($run->id,'trial'))->handle();
        $this->assertSame(6,$run->fresh()->stats['media.source_found']);
        $run->refresh()->update(['status'=>'apply_queued']);
        (new RunLegacyImport($run->id,'apply'))->handle();
        // Simulate a completed import from before user-document mappings were supported.
        $record = DB::table('legacy_import_records')->where('entity','users')->first();
        DB::table('legacy_import_run_records')->where('run_id',$run->id)->where('record_id',$record->id)->delete();
        unlink($this->directory.'/old/uploads/signature.png');
        $run->refresh()->update(['status'=>'media_queued']);
        for ($i=0;$i<4;$i++) (new RunLegacyImport($run->id,'media'))->handle();
        $this->assertSame('media_issues',$run->fresh()->status);
        $this->assertSame(5,DB::table('filestorage')->count());
        file_put_contents($this->directory.'/old/uploads/signature.png',$png);
        $run->refresh()->update(['status'=>'media_queued','media_cursor'=>0,'media_processed'=>0]);
        for ($i=0;$i<4;$i++) (new RunLegacyImport($run->id,'media'))->handle();
        $this->assertSame('complete',$run->fresh()->status);
        $profile = \App\Models\User\Profile::first();
        $this->assertEqualsCanonicalizing(['ktp','npwpcompany','nibcompany','establishment','signature','legacy_contract'],$profile->identities->pluck('type')->all());
        foreach ($profile->identities as $file) {
            $this->assertSame((int) $profile->id,(int) $file->profileid);
            $this->assertSame($profile->getMorphClass(),$file->owner_type);
            $this->assertStringStartsWith('legacy-media/profiles/'.$profile->id.'/',$file->identifier);
            $this->assertSame(hash_file('sha256',$this->directory.'/old/uploads/'.$file->original_name),hash('sha256',Storage::disk('local')->get($file->identifier)));
        }
        $this->assertSame('application/vnd.openxmlformats-officedocument.wordprocessingml.document',$profile->identities->firstWhere('type','legacy_contract')->mime_type);
        file_put_contents($this->directory.'/old/uploads/ktp.png',$png.'changed');
        $run->refresh()->update(['status'=>'media_queued','media_cursor'=>0,'media_processed'=>0]);
        for ($i=0;$i<4;$i++) (new RunLegacyImport($run->id,'media'))->handle();
        $this->assertSame('media_issues',$run->fresh()->status);
        $this->assertSame($png,Storage::disk('local')->get($profile->identities->firstWhere('type','ktp')->identifier));
        $this->assertSame(6,DB::table('filestorage')->count());
        $this->assertSame(1,DB::table('users')->count());
        $this->assertSame(1,DB::table('user_profiles')->count());
    }

    public function test_personal_npwp_is_attached_to_the_imported_profile(): void
    {
        $data = $this->data();
        $data['users'][7]['npwp_doc_path'] = '/uploads/personal.pdf';
        file_put_contents($this->directory.'/old/uploads/personal.pdf',"%PDF-1.4\n%%EOF");
        $run = $this->runFor($data); $run->update(['status'=>'apply_queued']);
        (new RunLegacyImport($run->id,'apply'))->handle();
        $run->refresh()->update(['status'=>'media_queued']);
        for ($i=0;$i<4;$i++) (new RunLegacyImport($run->id,'media'))->handle();
        $this->assertSame('complete',$run->fresh()->status);
        $this->assertSame('npwppersonal',\App\Models\User\Profile::first()->identities()->sole()->type);
    }

    public function test_identity_only_import_does_not_process_catalogue_files_or_replace_existing_identity(): void
    {
        $data = $this->data();
        $data['users'][7]['ktp_doc_path'] = '/uploads/ktp.png';
        $data['users'][7]['signature_doc_path'] = '/uploads/signature-no-longer-on-source.png';
        $data['releases'][11]['cover_art'] = '/uploads/do-not-read-cover.png';
        $data['tracks'][12]['audio_file'] = '/uploads/do-not-read-master.wav';
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a8R8AAAAASUVORK5CYII=');
        file_put_contents($this->directory.'/old/uploads/ktp.png',$png);
        $run = $this->runFor($data); $run->update(['status'=>'apply_queued']);
        (new RunLegacyImport($run->id,'apply'))->handle();
        $profile = \App\Models\User\Profile::first();
        $release = \App\Models\Release::first(); $track = \App\Models\Track::first();
        $ids = [];
        foreach ([[$profile,'signature'],[$release,'cover'],[$track,'full']] as [$owner,$type]) {
            $path = 'existing/'.$type; Storage::disk('local')->put($path,'existing '.$type);
            $file = \App\Models\File::create(['disk'=>'local','identifier'=>$path,'type'=>$type,'original_name'=>$type,
                'mime_type'=>'application/octet-stream','size'=>15,'profileid'=>$profile->id,'owner_id'=>$owner->id,'owner_type'=>$owner->getMorphClass()]);
            $ids[] = $file->id;
        }
        $before = DB::table('filestorage')->whereIn('id',$ids)->orderBy('id')->get()->toJson();
        $releaseRecord = DB::table('legacy_import_records')->where('entity','releases')->first();
        DB::table('legacy_import_media_results')->insert(['run_id'=>$run->id,'record_id'=>$releaseRecord->id,'field'=>'cover_art','status'=>'issue','message'=>'Old cover issue']);
        $userRecord = DB::table('legacy_import_records')->where('entity','users')->first();
        DB::table('legacy_import_run_records')->where('run_id',$run->id)->where('record_id',$userRecord->id)->delete();
        $run->refresh()->update(['status'=>'media_issues']);
        $url = route('admin.system-settings.legacy.action',[$run->id,'identities']);
        $this->signIn(false); $this->post($url,['media_root'=>$run->media_root])->assertForbidden();
        $this->signIn(); $this->post($url,['media_root'=>$run->media_root])->assertSessionHasNoErrors()->assertRedirect();
        Bus::assertDispatched(RunLegacyImport::class,fn ($job) => $job->phase === 'identities');
        for ($i=0;$i<2;$i++) (new RunLegacyImport($run->id,'identities'))->handle();
        $run->refresh();
        $this->assertSame('identities_complete',$run->status);
        $this->assertSame(1,(int) $run->media_processed);
        $this->assertSame($before,DB::table('filestorage')->whereIn('id',$ids)->orderBy('id')->get()->toJson());
        $this->assertSame('existing full',Storage::disk('local')->get('existing/full'));
        $this->assertSame('existing cover',Storage::disk('local')->get('existing/cover'));
        $this->assertSame('existing signature',Storage::disk('local')->get('existing/signature'));
        $this->assertSame(1,$run->mediaResults()->where('status','copied')->count());
        $this->assertSame(1,$run->mediaResults()->where('status','kept')->count());
        $this->assertSame(0,$run->mediaResults()->where('status','issue')->count());
        $this->assertSame(1,DB::table('legacy_import_media_results')->where('status','issue')->count());
        $this->assertSame(1,DB::table('users')->count());
        $this->assertSame(1,DB::table('releases')->count());
        $this->assertSame(1,DB::table('tracks')->count());
    }

    public function test_direct_identity_command_resumes_without_queue_and_leaves_catalogue_untouched(): void
    {
        $data = $this->data();
        $data['users'][7]['ktp_doc_path'] = '/uploads/ktp.png';
        $data['users'][8] = array_replace($data['users'][7],['id'=>'8','email'=>'second@example.test']);
        $data['releases'][11]['cover_art'] = '/uploads/cover-must-not-be-read.png';
        $data['tracks'][12]['audio_file'] = '/uploads/wav-must-not-be-read.wav';
        file_put_contents($this->directory.'/old/uploads/ktp.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a8R8AAAAASUVORK5CYII='));
        $run = $this->runFor($data); $run->update(['status'=>'apply_queued']);
        (new RunLegacyImport($run->id,'apply'))->handle();
        $run->refresh()->update(['status'=>'identities_queued','started_at'=>null]);
        $this->artisan('legacy:import-identities',['run'=>$run->id,'--limit'=>1])
            ->expectsOutputToContain('WAV, cover art, dan IPL dilewati')
            ->expectsOutputToContain('Belum selesai')->assertSuccessful();
        $this->assertSame(1,(int) $run->fresh()->media_processed);
        $this->assertSame('identities_queued',$run->fresh()->status);
        $this->artisan('legacy:import-identities',['run'=>$run->id])
            ->expectsOutputToContain('Import dokumen identitas selesai')->assertSuccessful();
        $this->assertSame('identities_complete',$run->fresh()->status);
        $this->assertSame(2,(int) $run->fresh()->media_processed);
        $this->assertSame(2,DB::table('filestorage')->where('type','ktp')->count());
        $this->assertSame(0,DB::table('filestorage')->whereIn('type',['cover','full'])->count());
        Bus::assertNotDispatched(RunLegacyImport::class);
        // Running again retains already copied documents rather than duplicating them.
        $this->artisan('legacy:import-identities',['run'=>$run->id])->assertSuccessful();
        $this->assertSame(2,DB::table('filestorage')->count());
        $this->assertSame(2,$run->fresh()->mediaResults()->where('status','kept')->count());
    }

    public function test_direct_identity_command_reports_missing_sources_and_rejects_unimported_runs(): void
    {
        $data = $this->data(); $data['users'][7]['ktp_doc_path'] = '/uploads/missing-ktp.png';
        $run = $this->runFor($data);
        $this->artisan('legacy:import-identities',['run'=>$run->id])
            ->expectsOutputToContain('Database harus selesai diimpor')->assertFailed();
        $run->update(['status'=>'apply_queued']);
        (new RunLegacyImport($run->id,'apply'))->handle();
        $this->artisan('legacy:import-identities',['run'=>$run->id])
            ->expectsOutputToContain('Lokasi dicari:')->assertSuccessful();
        $this->assertSame('identities_issues',$run->fresh()->status);
        $this->assertSame(0,DB::table('filestorage')->count());
        Bus::assertNotDispatched(RunLegacyImport::class);
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

    public function test_missing_media_reports_the_local_path_without_url_credentials(): void
    {
        try {
            (new ImportPaths)->media('https://old.example/uploads/missing.png?token=secret',$this->directory.'/old');
            $this->fail('Missing source accepted');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Lokasi dicari: ',$e->getMessage());
            $this->assertStringContainsString('uploads/missing.png',str_replace('\\','/',$e->getMessage()));
            $this->assertStringNotContainsString('secret',$e->getMessage());
        }
    }

    public function test_application_root_resolves_public_uploads_and_private_documents_without_leaving_root(): void
    {
        File::makeDirectory($this->directory.'/old/public/uploads',0700,true);
        File::makeDirectory($this->directory.'/old/storage/private',0700,true);
        file_put_contents($this->directory.'/old/public/uploads/cover.png','cover');
        file_put_contents($this->directory.'/old/storage/private/contract.pdf','private contract');
        $paths = new ImportPaths;
        $this->assertSame(realpath($this->directory.'/old/public/uploads/cover.png'),$paths->media('/uploads/cover.png',$this->directory.'/old'));
        $this->assertSame(realpath($this->directory.'/old/storage/private/contract.pdf'),$paths->media('/storage/private/contract.pdf',$this->directory.'/old'));
        // Existing document-root layouts retain their explicit source location.
        file_put_contents($this->directory.'/old/uploads/cover.png','original');
        $this->assertSame(realpath($this->directory.'/old/uploads/cover.png'),$paths->media('/uploads/cover.png',$this->directory.'/old'));
        $this->expectException(RuntimeException::class);
        $paths->media('/uploads/../../outside.pdf',$this->directory.'/old');
    }

    public function test_retry_normalizes_uploads_root_and_tracks_when_the_worker_actually_starts(): void
    {
        File::makeDirectory($this->directory.'/old/public/uploads',0700,true);
        file_put_contents($this->directory.'/old/public/uploads/cover.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a8R8AAAAASUVORK5CYII='));
        $data = $this->data(); $data['releases'][11]['cover_art'] = '/uploads/cover.png';
        $run = $this->runFor($data); $run->update(['status'=>'apply_queued']);
        (new RunLegacyImport($run->id,'apply'))->handle();
        $record = DB::table('legacy_import_records')->where('entity','releases')->first();
        DB::table('legacy_import_media_results')->insert(['run_id'=>$run->id,'record_id'=>$record->id,'field'=>'cover_art','status'=>'issue','message'=>'Hasil lama']);
        $run->refresh()->update(['status'=>'media_issues','started_at'=>now()->subHour(),'media_processed'=>690]);
        $this->signIn();
        $this->post(route('admin.system-settings.legacy.action',[$run->id,'media']),['media_root'=>$this->directory.'/old/public/uploads'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $run->refresh();
        $this->assertNull($run->started_at);
        $this->assertSame(0,(int) $run->media_processed);
        $this->assertSame(realpath($this->directory.'/old'),$run->media_root);
        $this->assertSame('Menunggu worker impor',$run->mediaQueueLabel());
        $this->assertSame(1,DB::table('legacy_import_media_results')->where('status','issue')->count());
        (new RunLegacyImport($run->id,'media'))->handle();
        $started = $run->fresh()->started_at;
        $this->assertNotNull($started);
        $this->assertSame('Penyalinan media belum selesai',$run->fresh()->mediaQueueLabel());
        $this->travel(3)->seconds();
        (new RunLegacyImport($run->id,'media'))->handle();
        $this->assertTrue($started->equalTo($run->fresh()->started_at));
        $this->assertSame(0,DB::table('legacy_import_media_results')->where('status','issue')->count());
        $this->assertSame(1,DB::table('legacy_import_media_results')->where('status','copied')->count());
    }

    public function test_issue_pagination_uses_bootstrap_without_oversized_svg_and_preserves_selected_import(): void
    {
        $this->signIn();
        $run = $this->runFor($this->data()); $run->status = 'media_issues';
        for ($i=1;$i<=26;$i++) DB::table('legacy_import_media_results')->insert([
            'run_id'=>$run->id,'record_id'=>$i,'field'=>'cover_art','status'=>'issue','message'=>'File tidak ditemukan',
        ]);
        $issues = DB::table('legacy_import_media_results')->paginate(25,['*'],'issues_page')
            ->appends(['tab'=>'legacy','import'=>$run->id]);
        $html = view('admin.system-settings.legacy',[
            'importsReady'=>true,'selectedImport'=>$run,'importRuns'=>collect(),
            'importMediaCounts'=>collect(['issue'=>26]),'importMediaIssues'=>$issues,
            'errors'=>new \Illuminate\Support\ViewErrorBag,
        ])->render();
        $this->assertStringContainsString('class="page-item',$html);
        $this->assertStringContainsString('issues_page=2',$html);
        $this->assertStringContainsString('tab=legacy',$html);
        $this->assertStringContainsString('import='.$run->id,$html);
        $this->assertStringNotContainsString('<svg',$html);
        $this->assertStringContainsString('File Manager',$html);
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
        $this->assertSame(3,DB::table('legacy_import_run_records')->where('run_id',$second->id)->count());
        $this->assertSame(1,DB::table('tracks')->count());
    }
}
