<?php
require __DIR__.'/vendor/autoload.php';


use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as FileFacade;

use App\Models\Release;
use App\Models\Track;
use App\Models\User\Profile;

// Laravel bootstrapping
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Create connection to old database
$connection = DB::connection('mysql_old');

// Clear temp folder
$tempdir = 'app' . DIRECTORY_SEPARATOR . 'draft';
if (FileFacade::exists(storage_path($tempdir))) {
    FileFacade::deleteDirectory(storage_path($tempdir));
}
FileFacade::makeDirectory(storage_path($tempdir), 0755, true);

// Delete all releases and tracks
Release::query()->delete();

// Define paths
$dir        = env('OLDPANEL_ROOTDIR');
$cover_dir  = $dir . DIRECTORY_SEPARATOR . 'drive' . DIRECTORY_SEPARATOR . 'cover';
$track_dir  = $dir . DIRECTORY_SEPARATOR . 'drive' . DIRECTORY_SEPARATOR . 'lagu';

// Process each release
$oldalbums  = $connection->select('SELECT * FROM tb_album ORDER BY album_user ASC');
$oldtracks  = $connection->select('SELECT * FROM tb_lagu WHERE lagu_type = "Single"');

// Process albums
$errors = [];
foreach ($oldalbums as $oldalbum) {
    DB::beginTransaction();
    try {
        
        // Get user profile
        $profile = Profile::where('old_id', $oldalbum->album_user)->first();
        if(!$profile) {
            throw new \Exception('User profile not found: ' . $oldalbum->album_user);
        }

        // Create draft release
        $release = new Release([
            'profileid'         => $profile->id,
            'old_album_id'      => $oldalbum->album_id,
            'old_track_id'      => null,
            'type'              => 'album',
            'tracknum'          => 0,
            'title'             => $oldalbum->album_judul,
            'titlelang'         => 'id',
            'primaryartists'    => null,
            'recordlabel'       => null,
            'version'           => null,
            'genre'             => null,
            'subgenre'          => null,
            'releasedir'        => null,
            'released'          => false,
            'releaseddate'      => null,
            'upc'               => null,
            'status'            => 'draft',
            'reason'            => null,
            'releaseon'         => null,
        ]);
        $release->save();

        // Store cover
        if($oldalbum->album_cover) {
            if(FileFacade::exists($cover_dir . DIRECTORY_SEPARATOR . $oldalbum->album_cover)) {
                $randomstring = bin2hex(random_bytes(10));

                FileFacade::copy(
                    $cover_dir . DIRECTORY_SEPARATOR . $oldalbum->album_cover,      // source
                    storage_path($tempdir . DIRECTORY_SEPARATOR . $randomstring)   // destination
                );

                $release->load('draft');
                $release->draft()->create([
                    'profileid'         => $profile->id,
                    'path'              => $tempdir . DIRECTORY_SEPARATOR . $randomstring,
                ]);
            }
        }

        // Get tracks
        $oldtracks = $connection->select('SELECT * FROM tb_lagu WHERE lagu_album = ? AND lagu_type = "Album" ORDER BY lagu_created ASC', [$oldalbum->album_id]);

        // Process each track
        foreach ($oldtracks as $i => $oldtrack) {
            $genre = genreConvert($oldtrack->lagu_genre);

            if($i == 0) {
                $release->primaryartists    = json_encode([getLaguArtist($oldtrack->lagu_artis)]);
                $release->recordlabel       = $oldtrack->lagu_label;
                $release->version           = $oldtrack->lagu_versi;
                $release->genre             = $genre['genre'];
                $release->subgenre          = $genre['subgenre'];
                $release->upc               = $oldtrack->lagu_upc;
                $release->status            = statusConvert($oldtrack->lagu_status);
                $release->tracknum          = count($oldtracks);
                $release->releaseon         = $oldtrack->lagu_tgl_rilis;
                $release->save();
            }

            $contributors = $oldtrack->lagu_kontributor ? explode(', ', $oldtrack->lagu_kontributor) : [];
            $new_contributors = [];
            foreach ($contributors as $contributor) {
                $new_contributors[] = [
                    'name' => $contributor,
                    'role' => 42,
                ];
            }

            $track = new Track([
                'profileid'             => $profile->id,
                'primaryartists'        => json_encode([getLaguArtist($oldtrack->lagu_artis)]),
                'featuredartists'       => json_encode([]),
                'title'                 => $oldtrack->lagu_judul,
                'titlelang'             => $oldtrack->lagu_bahasa == 'Indonesia' ? 'id' : 'en',
                'version'               => $oldtrack->lagu_versi,
                'genre'                 => $genre['genre'],
                'subgenre'              => $genre['subgenre'],
                'composer'              => $oldtrack->lagu_komposer,
                'instrumental'          => $oldtrack->lagu_instrumental == 'Iya' ? true : false,
                'lyrics'                => null,
                'lyriclang'             => 'id',
                'explicit'              => 'non-explicit',
                'songwriter'            => null,
                'isrc'                  => $oldtrack->lagu_isrc,
                'duration'              => 0,
                'contributors'          => json_encode($new_contributors),
                'productions'           => json_encode([]),
            ]);

            $track->save();
            $track->releases()->attach([
                $release->id => [
                    'track_order' => $i,
                    'upc' => $oldtrack->lagu_upc,
                    'isrc' => $oldtrack->lagu_isrc
                ]
            ]);
            
            // Copy track file
            $randomstring = bin2hex(random_bytes(10));
            if(FileFacade::exists($track_dir . DIRECTORY_SEPARATOR . $oldtrack->lagu_file)) {

                try {
                    $hash1 = hash_file('sha256', $track_dir . DIRECTORY_SEPARATOR . $oldtrack->lagu_file);
                    FileFacade::copy(
                        $track_dir . DIRECTORY_SEPARATOR . $oldtrack->lagu_file,        // source
                        storage_path($tempdir . DIRECTORY_SEPARATOR . $randomstring)   // destination
                    );
    
                    // Compare hash
                    $hash2 = hash_file('sha256', storage_path($tempdir . DIRECTORY_SEPARATOR . $randomstring));
                    if($hash1 != $hash2) {
                        throw new \Exception("Hash does not match");
                    }
    
                    $track->load('full_draft');
                    $track->full_draft()->create([
                        'profileid' => $profile->id,
                        'path'      => $tempdir . DIRECTORY_SEPARATOR . $randomstring,
                        'type'      => 'full'
                    ]);
                    
                } 
                catch (\Throwable $th) {
                    print_r($oldtrack->lagu_judul . ' : ' .  $th->getMessage() . PHP_EOL);
                }
            }
        }

        DB::commit();
        print_r($oldalbum->album_judul . ' : OK'. PHP_EOL);
    }
    catch (\Throwable $th) {
        DB::rollBack();
        print_r($oldalbum->album_judul . ' : ' .  $th->getMessage() . PHP_EOL);
    }
}

// Process singles
$oldsingles = $connection->select('SELECT * FROM tb_lagu WHERE lagu_album = "" ORDER BY lagu_created ASC');
foreach ($oldsingles as $oldsingle) {
    DB::beginTransaction();
    try {
        // Get user profile
        $profile = Profile::where('old_id', $oldsingle->upload_by)->first();
        if(!$profile) {
            throw new \Exception('User profile not found: ' . $oldsingle->upload_by);
        }

        // Create draft release
        $genre = genreConvert($oldsingle->lagu_genre);
        $release = new Release([
            'profileid'         => $profile->id,
            'old_album_id'      => null,
            'old_track_id'      => $oldsingle->lagu_id,
            'type'              => 'single',
            'tracknum'          => 1,
            'title'             => $oldsingle->lagu_judul,
            'titlelang'         => 'id',
            'primaryartists'    => json_encode([getLaguArtist($oldsingle->lagu_artis)]),
            'recordlabel'       => $oldsingle->lagu_label,
            'version'           => $oldsingle->lagu_versi,
            'genre'             => $genre['genre'],
            'subgenre'          => $genre['subgenre'],
            'releasedir'        => null,
            'released'          => false,
            'releaseddate'      => null,
            'upc'               => $oldsingle->lagu_upc,
            'status'            => statusConvert($oldsingle->lagu_status),
            'reason'            => null,
            'releaseon'         => $oldsingle->lagu_tgl_rilis,
        ]);
        $release->save();

        // Store cover
        $randomstring = bin2hex(random_bytes(10));
        if($oldsingle->lagu_cover) {
            if(FileFacade::exists($cover_dir . DIRECTORY_SEPARATOR . $oldsingle->lagu_cover)) {
                FileFacade::copy(
                    $cover_dir . DIRECTORY_SEPARATOR . $oldsingle->lagu_cover,  // source
                    storage_path($tempdir . DIRECTORY_SEPARATOR . $randomstring)     // destination
                );
                $release->load('draft');
                $release->draft()->create([
                    'profileid'         => $profile->id,
                    'path'              => $tempdir . DIRECTORY_SEPARATOR . $randomstring,
                ]);
            }
        }

        // Process contributors
        $contributors = $oldsingle->lagu_kontributor ? explode(', ', $oldsingle->lagu_kontributor) : [];
        $newcontributors = [];
        foreach($contributors as $contributor) {
            $newcontributors[] = [
                'name' => $contributor,
                'role' => 42
            ];
        }

        $track = new Track([
            'profileid'             => $profile->id,
            'primaryartists'        => json_encode([getLaguArtist($oldsingle->lagu_artis)]),
            'featuredartists'       => json_encode([]),
            'title'                 => $oldsingle->lagu_judul,
            'titlelang'             => $oldsingle->lagu_bahasa == 'Indonesia' ? 'id' : 'en',
            'version'               => $oldsingle->lagu_versi,
            'genre'                 => $genre['genre'],
            'subgenre'              => $genre['subgenre'],
            'composer'              => $oldsingle->lagu_komposer,
            'instrumental'          => $oldsingle->lagu_instrumental == 'Iya' ? true : false,
            'lyrics'                => null,
            'lyriclang'             => 'id',
            'explicit'              => 'non-explicit',
            'songwriter'            => null,
            'isrc'                  => $oldsingle->lagu_isrc,
            'duration'              => 0,
            'contributors'          => json_encode($newcontributors),
            'productions'           => json_encode([]),
        ]);
        $track->save();
        $track->releases()->attach([
            $release->id => [
                'track_order' => $i,
                'upc' => $oldtrack->lagu_upc,
                'isrc' => $oldtrack->lagu_isrc
            ]
        ]);

        // Copy track file
        $randomstring = bin2hex(random_bytes(10));
        if(FileFacade::exists($track_dir . DIRECTORY_SEPARATOR . $oldsingle->lagu_file)) {
            FileFacade::copy(
                $track_dir . DIRECTORY_SEPARATOR . $oldsingle->lagu_file,       // source
                storage_path($tempdir . DIRECTORY_SEPARATOR . $randomstring)    // destination
            );
            $track->load('full_draft');
            $track->full_draft()->create([
                'profileid' => $profile->id,
                'path'      => $tempdir . DIRECTORY_SEPARATOR . $randomstring,
                'type'      => 'full'
            ]);
        }

        DB::commit();
        print_r($oldsingle->lagu_judul . ' : OK'. PHP_EOL);
    }
    catch (\Throwable $th) {
        DB::rollBack();
        print_r($oldsingle->lagu_judul . ' : ' .  $th->getMessage() . PHP_EOL);
    }
    
}

function genreConvert($genre_id) {
    $genreMap = [
        1   => 1,
        6   => 17,
        7   => 7,
        18  => 13,
        23  => 16,
        27  => 18,
    ];

    $subgenre = $genre_id == 6 ? 267 : null;
    
    return [
        'genre'     => $genreMap[$genre_id],
        'subgenre'  => $subgenre
    ];
}

function statusConvert($status) {
    // Get status
    $newstatus = '';
    switch($status) {
        case 'Sudah Rilis':
            $newstatus = 'released';
            break;
        case 'Rilis Ditolak':
            $newstatus = 'rejected';
            break;
        case 'Rilis Diterima':
        case 'Rilis Diriview':
            $newstatus = 'pending';
            break;
        default:
            $newstatus = 'draft';
            break;
    }

    return $newstatus;
}

function getLaguArtist($lagu_artis) {
    $connection = DB::connection('mysql_old');
    $oldartists = $connection->select('SELECT * FROM tb_artis WHERE artis_id = ?', [$lagu_artis]);

    if(count($oldartists) == 0) {
        return null;
    }

    return $oldartists[0]->artis_nama;
}