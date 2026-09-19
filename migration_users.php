<?php
require __DIR__.'/vendor/autoload.php';

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as FileFacade;

use App\Models\User\User;
use App\Models\Release;
use App\Models\Track;

use App\Services\Drive\IdentityDriveService;

// Laravel bootstrapping
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Create connection to old database
$connection = DB::connection('mysql_old');

// Define paths
$dir            = env('OLDPANEL_ROOTDIR');
$identity_dir   = $dir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'iden';

// Clear temp, draft, and cache folders if exists
if (FileFacade::exists(storage_path('app' . DIRECTORY_SEPARATOR . 'temp'))) FileFacade::deleteDirectory(storage_path('app' . DIRECTORY_SEPARATOR . 'temp'));
if (FileFacade::exists(storage_path('app' . DIRECTORY_SEPARATOR . 'draft'))) FileFacade::deleteDirectory(storage_path('app' . DIRECTORY_SEPARATOR . 'draft'));
if (FileFacade::exists(storage_path('app' . DIRECTORY_SEPARATOR . 'cache'))) FileFacade::deleteDirectory(storage_path('app' . DIRECTORY_SEPARATOR . 'cache'));

// Process each user
$oldusers = $connection->select('SELECT * FROM tb_users');
$errors = [];
foreach ($oldusers as $olduser) {
    try {
        // Skip if user email already exists
        if(User::where('email', $olduser->user_email)->exists()) {
            continue;
        }

        DB::beginTransaction();
        
        // Create user and profile
        $nameParts = explode(' ', $olduser->user_nama);
        $user = User::create([
            'name'      => $olduser->user_nama,
            'email'     => $olduser->user_email,
            'password'  => $olduser->user_password,
        ]);
        $user->profile()->create([
            'old_id'        => $olduser->user_id,
            'firstname'     => count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 0, -1)) : $nameParts[0],
            'lastname'      => count($nameParts) > 1 ? end($nameParts) : $nameParts[0],
            'phone'         => $olduser->user_telp,

            'is_company'    => false,
            'companyname'   => null,

            'address1'      => '',
            'address2'      => '',
            'city'          => '',
            'state'         => '',
            'zip'           => '',
            'country'       => 'ID',

            'bankname'          => $olduser->user_bank ?? '',
            'bankbranch'        => '',
            'accountname'       => $olduser->user_bank_an ?? '',
            'accountnumber'     => $olduser->user_bank_no ?? '',
            
            'ktp_num'           => $olduser->user_no_identitas,
            'npwppersonal_num'  => $olduser->user_npwp,
            'signature'         => null,

            'feature_aggregator'    => true,
            'feature_publishing'    => false,
            'feature_mcn'           => false,

            'status'            => $olduser->user_status == 'Aktif' ? 'verified' : 'pending',
        ]);

        $user->created_at           = $olduser->user_created;
        $user->profile->created_at  = $olduser->user_created;
        $user->save();
        $user->profile->save();

        // Create draft directory if not exists
        $dir = storage_path('app' . DIRECTORY_SEPARATOR . 'draft');
        if(!FileFacade::exists($dir)) {
            FileFacade::makeDirectory($dir, 0755, true);
        }

        // Process documents
        foreach ([$olduser->user_foto_identitas, $olduser->user_foto_npwp] as $doc) {
            if($doc) {
                if(FileFacade::exists($identity_dir . DIRECTORY_SEPARATOR . $doc)) {

                    // Copy file
                    $randomstring = bin2hex(random_bytes(10));
                    $path = 'app' . DIRECTORY_SEPARATOR . 'draft' . DIRECTORY_SEPARATOR . $randomstring;
                    FileFacade::copy(
                        $identity_dir . DIRECTORY_SEPARATOR . $doc, // source 
                        storage_path($path));                       // destination
                    
                    // Create identity
                    $identity = $user->profile->identities()->create([
                        'type'      => $doc == $olduser->user_foto_identitas ? 'ktp' : 'npwppersonal',
                    ]);

                    // Create identity draft
                    $identity->load('draft');
                    $identity->draft()->create([
                        'profileid' => $user->profile->id,
                        'path'      => $path,
                    ]);
                }
            }
        }

        // Upload documents
        $drive  = new IdentityDriveService();
        try {
            $drive->updateProfileData($user->profile);
        }
        catch (\Throwable $th) {
            $drive->deleteProfileData($user->profile);
            throw new \Exception($th->getMessage());
        }

        // Commit
        DB::commit();

        print_r($olduser->user_nama . ' : OK'. PHP_EOL);
    }
    catch (\Throwable $th) {
        DB::rollBack();
        print_r($olduser->user_nama . ' : ' .  $th->getMessage() . PHP_EOL);
    }
}

// Process admins
$oldadmins = $connection->select('SELECT * FROM tb_admin');
foreach ($oldadmins as $oldadmin) {
    try {
         // Skip if user email already exists
         if(User::where('email', $oldadmin->admin_email)->exists()) {
            continue;
        }

        DB::beginTransaction();
        
        // Create user
        $user = User::create([
            'name'      => $oldadmin->admin_nama,
            'email'     => $oldadmin->admin_email,
            'password'  => $oldadmin->admin_password,
        ]);

        $user->is_admin = 1;
        $user->save();
        
        DB::commit();
    } 
    catch (\Throwable $th) {
        DB::rollBack();
        print_r($oldadmin->admin_nama . ' : ' .  $th->getMessage() . PHP_EOL);
    }
}