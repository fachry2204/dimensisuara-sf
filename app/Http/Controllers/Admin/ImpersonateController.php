<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use App\Models\User\User;

class ImpersonateController extends Controller
{
    public function impersonate($id)
    {
        try {
            $user = User::findOrFail($id);
            if(!$user->profile) {
                throw new \Exception("This user does not have a profile");
            }

            session([
                'impersonate' => $user->id,
                'impersonate_name' => $user->profile->full_name
            ]);

            return redirect()->route('home');
        }
        catch ( \Illuminate\Database\Eloquent\ModelNotFoundException $e ) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->back()->withErrors('User ID ' . $id . ' not found');
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    public function stop_impersonate()
    {
        session()->forget('impersonate');
        return redirect()->route('admin.home');
    }
}
