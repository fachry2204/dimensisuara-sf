<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Models\Contract;
use App\Models\User\User;

use App\Services\ContractService;
use App\Services\FileService;

use App\Http\Requests\ProfileUpdateRequest;

class ProfileController extends Controller
{

    public function edit() 
    {
        try  {
            $user = current_user();
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

            return view('customer.profile', compact('user', 'identitymap', 'required'));
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    // public function update(ProfileUpdateRequest $request)
    // {
    //     try {
    //         $user = current_user();

    //         DB::transaction(function () use ($user, $request) {
    //             $user->profile->update([
    //                 'firstname'             => strtoupper($request->firstname),
    //                 'lastname'              => strtoupper($request->lastname),
    //                 'phone'                 => preg_replace('/\D/', '', $request->phone),
    //                 'ktp_num'               => $request->ktp_num,
    //                 'companyname'           => strtoupper($request->companyname),
    //                 'address1'              => $request->address1,
    //                 'address2'              => $request->address2,
    //                 'city'                  => $request->city,
    //                 'state'                 => $request->state,

    //                 'provinceid'            => $request->provinceid,
    //                 'cityid'                => $request->cityid,
    //                 'districtid'            => $request->districtid,
    //                 'villageid'             => $request->villageid,

    //                 'zip'                   => $request->zip,
    //                 'country'               => $request->country,
    //                 'bankname'              => $request->bankname,
    //                 'bankbranch'            => $request->bankbranch,
    //                 'accountname'           => $request->accountname,
    //                 'accountnumber'         => $request->accountnumber,
    //                 'nibcompany_num'        => $request->nibcompany_num,
    //                 'npwpcompany_num'       => $request->npwpcompany_num,
    //                 'npwppersonal_num'      => $request->npwppersonal_num,
    //                 'position'              => $request->position,
    //             ]);
    
    //             // Determine registration type
    //             $existingType   = $user->profile->is_company;
    //             $newType        = $request->registrationtype == 'company' ? 1 : 0;
    
    //             // Update registration type
    //             $user->profile->is_company = $newType;
    //             if(!$newType) $user->profile->companyname = null;
    //             $user->profile->save();
    //         });

    //         Log::info('User ID ' . $user->id . ' updated their profile.');

    //         // Redirect
    //         return redirect()->route('profile')->with('success', 'Profile has been updated. Please review your identity documents if you have made any changes to registration type.');
    //     }
    //     catch (\Throwable $th) {
    //         Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
    //         return redirect()->back()->withInput()->withErrors($th->getMessage());
    //     }
    // }

    public function update_identity(Request $request)
    {
        try {
            $user = current_user();

            // Validate request
            $this->validate($request, [
                'file'      => 'required_if:type,in:npwppersonal,ktp,nibcompany,npwpcompany,deed,establishment|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'type'      => 'required|string|in:signature,npwppersonal,ktp,nibcompany,npwpcompany,deed,establishment',
                'signature' => 'required_if:type,signature|nullable',
            ]);

            // Define variables
            $type       = $request->type;
            $newfile    = $request->type == 'signature' ? $request->signature : $request->file('file');
            $profile    = $user->profile;

            // Store file
            $file = FileService::store($newfile, $profile, $profile, $type, 'files');

            Log::info('User ID ' . $user->id . ' updated their identity ' . $type);
            
            // Redirect back
            return redirect()->back()->with('success', 'Identity document has been updated.');
        } 
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());    
        }
    }

    public function retrieve_contract(int $id)
    {
        try {
            // Find contract
            $contract = Contract::where('profileid', current_user()->profile->id)->findOrFail($id);
            $file = $mime = null;

            // Check if contract is uploaded
            if($contract->file) {

                // Get file
                $file = Storage::disk($contract->file->disk)->get($contract->file->identifier);
                $mime = $contract->file->mime_type;
            }
            else {
                // Generate PDF and return
                $file = ContractService::generateAggregatorPdf($contract, false);
                $mime = 'application/pdf';
            }

            // Return response
            return response($file)
                ->header('Content-Type', $mime)
                ->header('Content-Disposition', 'inline; filename="'.$contract->docnum.'"');

        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            abort(404, $e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            abort(500, $th->getMessage());
        }
    }
}
