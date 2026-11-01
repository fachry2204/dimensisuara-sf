<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BulkUserController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'ids'=>['required','array','min:1','max:10000'],
            'ids.*'=>['required','integer','min:1','distinct'],
            'changes'=>['required','array:status,feature_aggregator,feature_publishing,feature_mcn,whitelabel_enabled','min:1'],
            'changes.status'=>['sometimes','required',Rule::in(['pending','verified','blocked'])],
            'changes.feature_aggregator'=>['sometimes','required','boolean'],
            'changes.feature_publishing'=>['sometimes','required','boolean'],
            'changes.feature_mcn'=>['sometimes','required','boolean'],
            'changes.whitelabel_enabled'=>['sometimes','required','boolean'],
            'reason'=>['nullable','string','max:255',Rule::requiredIf($request->input('changes.status') === 'blocked')],
        ]);
        $changes = $data['changes'];
        if (array_key_exists('status',$changes)) Gate::authorize('manage-user-status');
        if (array_diff(array_keys($changes),['status'])) Gate::authorize('manage-user-features');

        $updated = DB::transaction(function () use ($data,$changes) {
            $profiles = Profile::whereIn('userid',$data['ids'])->orderBy('id')->lockForUpdate()->get();
            if ($profiles->count() !== count($data['ids'])) {
                throw ValidationException::withMessages(['ids'=>'Sebagian user tidak ditemukan atau tidak memiliki profil. Muat ulang daftar user.']);
            }
            $updated = 0;
            foreach ($profiles as $profile) {
                foreach ($changes as $field=>$value) {
                    $profile->$field = $field === 'status' ? $value : (int) $value;
                }
                if (array_key_exists('status',$changes)) {
                    $profile->reason = $changes['status'] === 'blocked' ? $data['reason'] : null;
                    // A status change from this screen never schedules account deletion.
                    $profile->delete_by = null;
                }
                if (!$profile->isDirty()) continue;
                $profile->save(); // Preserve status observers and configured notifications.
                $updated++;
            }
            Log::info('Admin updated selected user profiles.',[
                'admin_user_id'=>auth()->id(),'user_ids'=>$data['ids'],
                'changes'=>$changes,'updated'=>$updated,
            ]);
            return $updated;
        });

        return response()->json(['message'=>$updated.' user berhasil diperbarui.','updated'=>$updated]);
    }
}
