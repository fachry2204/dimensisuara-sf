<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

use App\Models\User\User;
use App\Models\File;

use App\Services\FileService;

use App\Http\Requests\RegisterRequest;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    protected $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function tnc()
    {
        return view('auth.tnc');
    }

    public function register(RegisterRequest $request)
    {
        try {
            // Get user if exists
            $user = User::where('email', $request->email)->first();

            // Additional validation
            try {
                if (!validateSignature($request->signature)) {
                    throw new \Exception('The signature is invalid.');
                }
                if ($user && $user->profile) {
                    throw new \Exception('The email address has already been taken.');
                }
            } catch (\Exception $e) {
                // Return if validation fails
                return response()->json([
                    'success' => false,
                    'errors' => [$e->getMessage()]
                ], 422);
            }

            // Process registration, use transaction
            DB::transaction(function () use ($request) {

                // Create user 
                $user = User::updateOrCreate(
                    [
                        'email' => $request->email
                    ],
                    [
                        'name' => $request->firstname . ' ' . $request->lastname,
                        'password' => Hash::make($request->password),
                    ]
                );

                // Create profile
                $profile = $user->profile()->create([
                    'firstname' => strtoupper($request->firstname),
                    'lastname' => strtoupper($request->lastname),
                    'phone' => preg_replace('/\D/', '', $request->phone),
                    'ktp_num' => $request->ktp_num,
                    'is_company' => $request->registrationtype === 'company',
                    'companyname' => strtoupper($request->companyname),
                    'address1' => $request->address1,
                    'address2' => $request->address2,
                    'city' => $request->city,
                    'state' => $request->state,

                    'provinceid' => $request->provinceid,
                    'cityid' => $request->cityid,
                    'districtid' => $request->districtid,
                    'villageid' => $request->villageid,

                    'zip' => $request->zip,
                    'country' => $request->country,
                    'bankname' => $request->bankname,
                    'bankbranch' => $request->bankbranch,
                    'accountname' => $request->bankaccountname,
                    'accountnumber' => $request->bankaccountnumber,
                    'feature_aggregator' => 1,
                    'feature_publishing' => 1,
                    'feature_mcn' => 0,

                    'nibcompany_num' => $request->nibcompany_num,
                    'npwpcompany_num' => $request->npwpcompany_num,
                    'npwppersonal_num' => $request->npwppersonal_num,
                    'position' => $request->position,

                    'status' => 'pending',
                    'reason' => null,
                    'delete_by' => null
                ]);

                // Store documents
                foreach (['ktp', 'nibcompany', 'npwpcompany', 'npwppersonal', 'deed', 'establishment', 'signature'] as $key) {
                    if ($key == 'signature') {
                        FileService::store($request->signature, $profile, $profile, 'signature', 'files');
                    } else if ($request->hasFile($key)) {
                        FileService::store($request->file($key), $profile, $profile, $key, 'files');
                    }
                }

                // Login user
                Auth::login($profile->user);

                // Log
                Log::info('User ' . $profile->user->email . ' has registered as User ID' . $user->id);
            });

            // Return on success
            return response()->json([
                'success' => true
            ], 201);
        } catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'errors' => $th->getMessage(),
            ], 500);
        }
    }
}