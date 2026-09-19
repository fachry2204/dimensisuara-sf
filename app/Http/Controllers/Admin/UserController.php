<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\Admin\UpdateProfileRateRequest;
use App\Models\Access\Role;
use App\Models\User\User;
use App\Models\File;

use App\Services\FileService;

class UserController extends Controller
{
    public function index()
    {
        try {
            $customers  = User::whereHas('profile')->with('profile.identities')->get();
            $customers->each(function ($customer) {
                $profile = $customer->profile;
                $required = $profile->is_company
                    ? ['ktp', 'signature', 'npwpcompany', 'nibcompany', 'deed', 'establishment']
                    : ['ktp', 'signature', 'npwppersonal'];
                $present = $profile->identities->pluck('type')->all();

                $profile->setAttribute('is_profile_complete', empty(array_diff($required, $present)));
            });

            $userSummary = [
                'total' => $customers->count(),
                'pending' => $customers->filter(fn ($customer) => $customer->profile->status === 'pending')->count(),
                'verified' => $customers->filter(fn ($customer) => $customer->profile->status === 'verified')->count(),
                'incomplete' => $customers->filter(fn ($customer) => ! $customer->profile->is_profile_complete)->count(),
            ];
            $roles      = Role::all();

            return view('admin.user.index', compact('customers', 'roles', 'userSummary'));
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    public function exportUsers(): StreamedResponse
    {
        try {
            $results = User::whereHas('profile')->with('profile')->get()->map(function ($row) {
                $registrationType = $row->profile->is_company ? 'Company' : 'Personal';
                $fullAddress = $row->profile->full_address ?? '-';

                return [
                    $row->id,
                    $row->profile->full_name ?? '-',
                    $row->email ?? '-',
                    $row->profile->phone ?? '-',
                    $registrationType,
                    $row->profile->companyname ?? '-',
                    $row->profile->position ?? '-',
                    $fullAddress,
                    $row->profile->ktp_num ?? '-',
                    $row->profile->npwppersonal_num ?? '-',
                    $row->profile->npwpcompany_num ?? '-',
                    $row->profile->nibcompany_num ?? '-',
                    $row->profile->bankname ?? '-',
                    $row->profile->accountname ?? '-',
                    $row->profile->accountnumber ?? '-',
                    $row->profile->feature_aggregator ? 'Yes' : 'No',
                    $row->profile->feature_publishing ? 'Yes' : 'No',
                    $row->profile->feature_mcn ? 'Yes' : 'No',
                    $row->profile->aggregator_rate ?? '-',
                    $row->profile->publishing_rate ?? '-',
                    $row->profile->mcn_rate ?? '-',
                    $row->email_verified_at ? 'Verified' : 'Not Verified',
                    $row->profile->status ?? '-',
                    $row->created_at ?? '-',
                ];
            });

            $results = collect([[
                'ID',
                'Nama Lengkap',
                'Email',
                'No. Telepon',
                'Tipe Registrasi',
                'Nama Perusahaan',
                'Posisi',
                'Alamat Lengkap',
                'No. KTP',
                'No. NPWP Personal',
                'No. NPWP Perusahaan',
                'No. NIB Perusahaan',
                'Bank',
                'Nama Rekening',
                'No. Rekening',
                'Fitur Aggregator',
                'Fitur Publishing',
                'Fitur MCN',
                'Rate Aggregator (%)',
                'Rate Publishing (%)',
                'Rate MCN (%)',
                'Status Email',
                'Status Akun',
                'Tanggal Registrasi',
            ]])->merge($results);

            $headers = [
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=users.csv",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0",
            ];

            $callback = function() use ($results) {
                $handle = fopen('php://output', 'w');

                foreach ($results->toArray() as $row) {
                    $convertedRow = array_map(function ($field) {
                        if (is_numeric($field) && str_contains((string) $field, '.')) {
                            return str_replace('.', ',', $field);
                        }
                        return $field;
                    }, $row);

                    fputcsv($handle, $convertedRow, ';', '"');
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors('Failed to export users.');
        }
    }

    public function show(string $id)
    {
        try  {
            $user = User::findOrFail($id);
            $identitymap = $required = [];

            if($user->profile) {
                $identitymap = [
                    'ktp'           => __('Personal ID (KTP) Photo of PIC'),
                    'signature'     => __('Signature'),
                    'npwppersonal'  => __('Personal Tax ID (NPWP) Photo'),
                    'npwpcompany'   => __('Company Tax ID (NPWP) Photo'),
                    'nibcompany'    => __('Company ID (NIB) Photo'),
                    'deed'          => __('Company Deed (Akta Perusahaan) Photo'),
                    'establishment' => __('Company Decree of Establishment (SK Kementerian) Photo')
                ];
                $required = $user->profile->is_company ? ['ktp','signature', 'npwpcompany', 'nibcompany', 'deed', 'establishment'] : ['ktp','signature', 'npwppersonal'];
            }

            return view('admin.user.show', compact('user', 'identitymap', 'required'));
        }
        catch( \Illuminate\Database\Eloquent\ModelNotFoundException $e ) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.users.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    public function edit(string $id)
    {
        // Define middleware

        try  {
            $user = User::findOrFail($id);

            $identitymap = [
                'ktp'           => __('Personal ID (KTP) Photo of PIC'),
                'signature'     => __('Signature'),
                'npwppersonal'  => __('Personal Tax ID (NPWP) Photo'),
                'npwpcompany'   => __('Company Tax ID (NPWP) Photo'),
                'nibcompany'    => __('Company ID (NIB) Photo'),
                'deed'          => __('Company Deed (Akta Perusahaan) Photo'),
                'establishment' => __('Company Decree of Establishment (SK Kementerian) Photo')
            ];

            $required = $user->profile->is_company ? ['ktp','signature', 'npwpcompany', 'nibcompany', 'deed', 'establishment'] : ['ktp','signature', 'npwppersonal'];
    
            return view('admin.user.edit', compact('user', 'identitymap', 'required'));
        }
        catch( \Illuminate\Database\Eloquent\ModelNotFoundException $e ) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.users.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    public function update(string $id, ProfileUpdateRequest $request)
    {
        // Define middleware

        try {
            // Get user and check profile
            $user = User::findOrFail($id);
            if(!$user->profile) {
                throw new \Exception("User ID " . $request->userid . " does not have a profile");
            }
            
            DB::transaction(function () use ($user, $request) {
                $user->profile->update([
                    'firstname'             => strtoupper($request->firstname),
                    'lastname'              => strtoupper($request->lastname),
                    'phone'                 => preg_replace('/\D/', '', $request->phone),
                    'ktp_num'               => $request->ktp_num,
                    'companyname'           => strtoupper($request->companyname),
                    'address1'              => $request->address1,
                    'address2'              => $request->address2,
                    'city'                  => $request->city,
                    'state'                 => $request->state,

                    'provinceid'            => $request->provinceid,
                    'cityid'                => $request->cityid,
                    'districtid'            => $request->districtid,
                    'villageid'             => $request->villageid,
                    
                    'zip'                   => $request->zip,
                    'country'               => $request->country,
                    'bankname'              => $request->bankname,
                    'bankbranch'            => $request->bankbranch,
                    'accountname'           => $request->accountname,
                    'accountnumber'         => $request->accountnumber,
                    'nibcompany_num'        => $request->nibcompany_num,
                    'npwpcompany_num'       => $request->npwpcompany_num,
                    'npwppersonal_num'      => $request->npwppersonal_num,
                    'position'              => $request->position,
                ]);
    
                // Determine registration type
                $newType        = $request->registrationtype == 'company' ? 1 : 0;
    
                // Update registration type
                $user->profile->is_company = $newType;
                if(!$newType) {
                    $user->profile->companyname = null;
                }
                $user->profile->save();
            });

            Log::info('User ID ' . $user->id . ' has been updated by Admin ID ' . auth()->user()->id);

            return redirect()->back()->with('success', 'Profile has been updated. Please review your identity documents if you have made any changes to registration type.');
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function update_rate(string $id, UpdateProfileRateRequest $request)
    {
        // Define middleware

        try {
            // Get user and check profile
            $user = User::findOrFail($id);
            if(!$user->profile) {
                throw new \Exception("User ID " . $request->userid . " does not have a profile");
            }

            DB::transaction(function () use ($user, $request) {
                $user->profile->update([
                    'aggregator_rate'   => $request->aggregator_rate,
                    'publishing_rate'   => $request->publishing_rate,
                    'mcn_rate'          => $request->mcn_rate
                    
                ]);
                $user->profile->save();
            });

            Log::info('User ID ' . $user->id . ' revenue share rate has been updated by Admin ID ' . auth()->user()->id);

            return redirect()->back()->with('success', 'Revenue share rate has been updated.');
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function update_identity(Request $request)
    {
        // Define middleware

        // Validate request
        $this->validate($request, [
            'file'      => 'required_if:type,in:npwppersonal,ktp,nibcompany,npwpcompany,deed,establishment|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'type'      => 'required|string|in:signature,npwppersonal,ktp,nibcompany,npwpcompany,deed,establishment',
            'signature' => 'required_if:type,signature|nullable',
        ]);

        try {
            // Get profile
            $user = User::findOrFail($request->userid);

            // Define variables
            $type       = $request->type;
            $newfile    = $request->type == 'signature' ? $request->signature : $request->file('file');
            $profile    = $user->profile;

            DB::transaction(function () use ($profile, $type, $newfile) {
    
                // Store file
                FileService::store($newfile, $profile, $profile, $type, 'files');
            });

            Log::info('User ID ' . $user->id . ' identity ' . $type . ' has been updated by Admin ID ' . auth()->user()->id);
            
            return redirect()->back()->with('success', 'Identity document has been updated.');
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    public function delete_identity(Request $request)
    {
        // Define middleware

        // Validate request
        $this->validate($request, [
            'userid'    => 'required|integer',
            'type'      => 'required|string|in:signature,npwppersonal,ktp,nibcompany,npwpcompany,deed,establishment',
        ]);

        try {
            $user   = User::findOrFail($request->userid);
            $type   = $request->type;

            DB::transaction(function () use ($user, $type) {
                File::where('type', $type)
                    ->where('owner_type', 'App\Models\User\Profile')
                    ->where('owner_id', $user->profile->id)
                    ->delete();
            });

            Log::info('User ID ' . $user->id . ' identity ' . $type . ' has been deleted by Admin ID ' . auth()->user()->id);

            return redirect()->back()->with('success', 'Identity document has been deleted.');
        }
        catch( \Illuminate\Database\Eloquent\ModelNotFoundException $e ) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.users.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    public function destroy($id)
    {
        // Define middleware

        try {
            $user = User::findOrFail($id);

            if (auth()->user()->id == $user->id) {
                throw new \Exception("You cannot delete your own account.");
            }

            DB::transaction(function () use ($user) {
                $user->delete();
            });

            Log::info('User ID ' . $user->id . ' has been deleted by Admin ID ' . auth()->user()->id);

            return redirect()->route('admin.users.index')->with('success', 'User ID ' . $id . ' has been deleted.');
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.users.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

}
