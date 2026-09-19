<?php

namespace Tests\Feature;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\File;
use App\Models\Release;
use App\Models\Track;
use App\Models\User\Profile;
use App\Models\User\User;
use App\Notifications\Admin\TrackBackupFailed;
use App\Services\TrackFileBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrackFileBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProfile(): Profile
    {
        $user = User::factory()->create();

        $profile = new Profile([
            'firstname' => 'Test',
            'lastname' => 'User',
            'phone' => '08123456789',
            'address1' => 'Jl. Test',
            'city' => 'Jakarta',
            'state' => 'DKI Jakarta',
            'zip' => '12345',
            'country' => 'ID',
            'bankname' => 'BCA',
            'bankbranch' => 'Jakarta',
            'accountname' => 'Test User',
            'accountnumber' => '1234567890',
        ]);
        $profile->userid = $user->id;
        $profile->save();

        return $profile;
    }

    protected function makeReleasedTrack(Profile $profile, string $status = 'released'): Track
    {
        $track = Track::create([
            'profileid' => $profile->id,
            'title' => 'Forever',
            'primaryartists' => json_encode([['name' => 'Keenan Te']]),
        ]);

        $release = Release::create([
            'profileid' => $profile->id,
            'status' => $status,
        ]);

        $track->releases()->attach($release->id, ['track_order' => 1]);

        return $track;
    }

    protected function makeFile(Profile $profile, Track $track, string $identifier = 'tracks/abc.wav'): File
    {
        $file = new File([
            'disk' => 'local',
            'identifier' => $identifier,
            'original_name' => 'forever.wav',
            'mime_type' => 'audio/wav',
            'size' => 18,
            'type' => 'full',
            'uploaded_at' => now()->subDays(8)->toDateTimeString(),
        ]);
        $file->owner()->associate($track);
        $file->profile()->associate($profile);
        $file->save();

        return $file;
    }

    public function test_eligible_track_file_is_backed_up_and_local_file_removed()
    {
        Storage::fake('local');
        Storage::fake('ftp');

        $profile = $this->makeProfile();
        $track = $this->makeReleasedTrack($profile);

        Storage::disk('local')->put('tracks/abc.wav', 'fake-audio-content');

        $file = $this->makeFile($profile, $track);

        $service = app(TrackFileBackupService::class);

        $this->assertTrue($service->backup($file));

        Storage::disk('local')->assertMissing('tracks/abc.wav');

        $file->refresh();
        $this->assertNull($file->identifier);
        $this->assertNotNull($file->backup_identifier);
        $this->assertNotNull($file->backed_up_at);
        $this->assertSame(0, (int) $file->backup_attempts);
        $this->assertNull($file->backup_error);
    }

    public function test_file_without_released_release_is_not_eligible()
    {
        Storage::fake('local');
        Storage::fake('ftp');

        $profile = $this->makeProfile();
        $track = $this->makeReleasedTrack($profile, 'draft');

        Storage::disk('local')->put('tracks/abc.wav', 'fake-audio-content');

        $file = $this->makeFile($profile, $track);

        $service = app(TrackFileBackupService::class);

        $this->assertFalse($service->eligibleFiles()->whereKey($file->id)->exists());
    }

    public function test_failed_backup_records_error_and_keeps_local_file()
    {
        Storage::fake('local');
        Storage::fake('ftp');

        $profile = $this->makeProfile();
        $track = $this->makeReleasedTrack($profile);

        Storage::disk('local')->put('tracks/abc.wav', 'fake-audio-content');

        $file = $this->makeFile($profile, $track);
        $file->update(['identifier' => 'tracks/missing.wav']);

        $service = app(TrackFileBackupService::class);

        $this->assertFalse($service->backup($file));

        $file->refresh();
        $this->assertSame(1, (int) $file->backup_attempts);
        $this->assertNotNull($file->backup_error);
        $this->assertNotNull($file->identifier);
        $this->assertNull($file->backup_identifier);
    }

    public function test_failed_backup_alerts_admins_after_threshold()
    {
        Notification::fake();

        Storage::fake('local');
        Storage::fake('ftp');

        $profile = $this->makeProfile();
        $track = $this->makeReleasedTrack($profile);

        $admin = User::factory()->create();
        $role = Role::create(['name' => 'Admin', 'description' => 'Admin']);
        $permission = Permission::create([
            'name' => 'Manage Releases',
            'slug' => 'manage-releases-status',
            'description' => '',
            'category' => 'releases',
        ]);
        $role->permissions()->attach($permission->id);
        $admin->roles()->attach($role->id);

        Storage::disk('local')->put('tracks/abc.wav', 'fake-audio-content');

        $file = $this->makeFile($profile, $track);
        $file->update(['backup_attempts' => 2]);

        $service = app(TrackFileBackupService::class);
        $service->markFailed($file, 'Upload to FTP failed.');

        Notification::assertSentTo($admin, TrackBackupFailed::class);
    }
}
