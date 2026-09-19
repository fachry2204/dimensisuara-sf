<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Http\UploadedFile;

use Google\Client;
use Google\Service\Drive;

use App\Services\FileService;

use App\Models\File;
use App\Models\Contract;
use App\Models\Release;
use App\Models\User\Profile;

class StorageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Override PHP settings
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '4096M');

        // Clear filestorage table
        DB::table('filestorage')->delete();

        // Get all profiles
        $profiles = Profile::all();
        foreach ($profiles as $profile) {

            try {
                // Get all identities of profile (KTP, NPWP, etc)
                $identities = DB::table('user_profile_identities')
                    ->where('profileid', $profile->id)
                    ->get();
    
                foreach ($identities as $identity) {
                    // Get the file from file table
                    $filerecord = DB::table('files')
                        ->where('owner_id', $identity->id)
                        ->where('owner_type', 'App\Models\User\Identity')
                        ->first();
    
                    if($filerecord) {
                        // Get file from Google Drive
                        $filecontent    = SELF::getDriveFile($filerecord->googlefileid);
                        $file           = $filecontent->getBody()->getContents();
                        $filename       = bin2hex(random_bytes(10));
                        $filepath       = storage_path('app' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $filename);
                        FileFacade::put($filepath, $file);
                    }
    
                    // Get the file from draft table
                    else {
                        $filerecord = DB::table('drafts')
                            ->where('owner_id', $identity->id)
                            ->where('owner_type', 'App\Models\User\Identity')
                            ->first();
    
                        if($filerecord) {
                            // Get file from local storage
                            $filepath = storage_path($filerecord->path);
                        }
                    }
    
                    if($filepath) {
                        // Create uploaded file
                        $uploadFile = new UploadedFile(
                            $filepath, 
                            $profile->full_name . ' - ' . $identity->type . '.jpeg', 
                            'image/jpeg', null, true
                        );
    
                        // Store file as new format
                        FileService::store($uploadFile, $profile, $profile, $identity->type, 'files');
                    }
                }
                
            } 
            catch (\Throwable $th) {
                print_r($profile->full_name . ' - ' . $identity->id . ' : ' .  $th->getMessage() . PHP_EOL);
                continue;
            }
            
        }

        // Get all releases
        $releases = Release::all();
        foreach ($releases as $release) {
            $cover = DB::table('drafts')
                ->where('owner_type', 'App\Models\Release')
                ->where('owner_id', $release->id)
                ->first();

            if($cover) {
                $filepath = storage_path($cover->path);
                $uploadFile = new UploadedFile(
                    $filepath, 
                    $release->title . '.jpeg', 
                    'image/jpeg', null, true
                );
                FileService::store($uploadFile, $release, $release->profile, 'cover', 'covers');
                FileService::store($uploadFile, $release, $release->profile, 'thumbnail', 'thumbnails');
            }

            // Get tracks
            foreach ($release->tracks as $track) {
                $trackfiles = DB::table('drafts')
                    ->where('owner_type', 'App\Models\Track')
                    ->where('owner_id', $track->id)
                    ->get();

                foreach($trackfiles as $trackfile) {
                    if($trackfile) {
                        try {
                            $filepath = storage_path($trackfile->path);
                            $uploadFile = new UploadedFile(
                                $filepath, 
                                $track->title . ' - ' . $trackfile->type . '.wav', 
                                'audio/wav', null, true
                            );
                            FileService::store($uploadFile, $track, $track->profile, $trackfile->type, 'tracks');
                        } 
                        catch (\Throwable $th) {
                            print_r($track->title . ' - ' . $trackfile->id . ' : ' .  $th->getMessage() . PHP_EOL);
                            continue;
                        }
                    }
                }
            }
        }
    }

    public static function getDriveFile($googlefileid) 
    {
        // Get key and dir id from env
        $key    = storage_path('app' . DIRECTORY_SEPARATOR . env('GOOGLE_SERVICE_ACCOUNT_KEY_JSON'));
        $dir_id = env('DRIVE_ROOT_FOLDER_ID');

        // Create client
        $client = new Client();
        $client->setAuthConfig($key);
        $client->addScope(Drive::DRIVE);
        $drive  = new Drive($client);

        // Get file
        return $drive->files->get($googlefileid, ['alt' => 'media']);
    }
}
