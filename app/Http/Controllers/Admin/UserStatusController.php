<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\User\User;

class UserStatusController extends Controller
{
    public function __construct()
    {
        // Middleware are set on the routes
    }

    /**
     * User status
     */
    public function updateWhitelabel(string $id, Request $request)
    {
        $data = $request->validate(['whitelabel_enabled' => ['required', 'boolean']]);
        $user = User::findOrFail($id);
        $profile = $user->profile;
        abort_unless($profile, 404);

        $profile->whitelabel_enabled = (bool) $data['whitelabel_enabled'];
        $profile->save();
        Log::info('User whitelabel setting updated.', [
            'user_id' => $user->id,
            'admin_user_id' => auth()->id(),
            'whitelabel_enabled' => $profile->whitelabel_enabled,
        ]);

        return back()->with('success', $profile->whitelabel_enabled
            ? 'Whitelabel aktif. Pengguna dapat mengisi Nama Label sendiri.'
            : 'Whitelabel nonaktif. Nama Label pada pengisian rilis menggunakan Dimensi Suara.');
    }

    public function verify(string $id, Request $request)
    {
        $rates = $request->validate([
            'aggregator_rate' => ['required', 'numeric', 'multiple_of:1', 'between:0,100'],
            'system_rate' => ['required', 'numeric', 'multiple_of:1', 'between:0,100'],
        ]);
        if ((int) $rates['aggregator_rate'] + (int) $rates['system_rate'] !== 100) {
            throw \Illuminate\Validation\ValidationException::withMessages(['aggregator_rate' => 'Total persentase user dan system harus 100%.']);
        }
        try {
            // Get user
            $user = User::findOrFail($id);
            if(!$user->profile) {
                throw new \Exception("User ID " . $id . " does not have a profile");
            }

            // Check if user is already verified
            if($user->profile->status == 'verified') {
                throw new \Exception("This user is already verified");
            }

            DB::transaction(function () use ($user, $rates) {
                // Update profile
                $user->profile->aggregator_rate = $rates['aggregator_rate'];
                $user->profile->status      = 'verified';
                $user->profile->reason      = null;
                $user->profile->delete_by   = null;
                $user->profile->save();
            });

            // Log
            Log::info('User ID ' . $id . ' has been verified by Admin ID ' . auth()->user()->id);

            // Return
            return redirect()->back()->with('success', 'User ID ' . $id . ' has been verified.');
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

    public function verify_email(string $id)
    {
        try {
            // Get user
            $user = User::findOrFail($id);

            if ($user->hasVerifiedEmail()) {
                return redirect()->back()->with('success', 'Email pengguna sudah terverifikasi.');
            }

            DB::transaction(function () use ($user) {
                $user->markEmailAsVerified();
            });

            // Log
            Log::info('User ID ' . $id . ' email has been verified by Admin ID ' . auth()->user()->id);

            // Return
            return redirect()->back()->with('success', 'User ID ' . $id . ' email has been verified.');
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

    public function notify(string $id, Request $request)
    {
        
        try {
            $request->validate([
                'reason' => 'required|string|max:255'
            ]);
            
            // Get user
            $user = User::findOrFail($id);
            if(!$user->profile) {
                throw new Exception("User ID " . $id . " does not have a profile");
            }

            DB::transaction(function () use ($user, $request) {
                // Update profile
                $user->profile->reason = $request->reason;
                $user->profile->save();
            });

            // Send notification

            // Log
            Log::info('User ID ' . $id . ' has been notified by Admin ID ' . auth()->user()->id);

            // Return
            return redirect()->back()->with('success', 'Notification has been sent for User ID ' . $id . '.');
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

    public function block(string $id, Request $request)
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:255'
            ]);

            // Get user
            $user = User::findOrFail($id);
            if(!$user->profile) {
                throw new \Exception("User ID " . $id . " does not have a profile");
            }

            // Check if user is already blocked
            if($user->profile->status == 'blocked') {
                throw new \Exception("This user is already blocked");
            }

            DB::transaction(function () use ($user, $request) {
                // Update profile
                $user->profile->status      = 'blocked';
                $user->profile->reason      = $request->reason;
                $user->profile->delete_by   = now()->addDays(7);
                $user->profile->save();
            });

            // Send notification

            // Log
            Log::info('User ID ' . $id . ' has been blocked by Admin ID ' . auth()->user()->id);

            // Return
            return redirect()->back()->with('success', 'User ID ' . $id . ' has been blocked.');
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

    public function unblock(string $id, Request $request)
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:255'
            ]);
            
            // Get user
            $user = User::findOrFail($id);
            if(!$user->profile) {
                throw new \Exception("User ID " . $id . " does not have a profile");
            }

            // Check if user is already blocked
            if($user->profile->status != 'blocked') {
                throw new \Exception("This user is not blocked");
            }

            DB::transaction(function () use ($user, $request) {
                // Update profile
                $user->profile->status      = 'pending';
                $user->profile->reason      = $request->reason;
                $user->profile->delete_by  = null;
                $user->profile->save();
            });

            // Send notification

            // Log
            Log::info('User ID ' . $id . ' has been unblocked by Admin ID ' . auth()->user()->id);

            // Return
            return redirect()->back()->with('success', 'User ID ' . $id . ' has been unblocked.');
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

    /**
     * Feature methods
     */
    public function enable_aggregator($id)
    {
        try {
            $user = User::findOrFail($id);
            if($user->profile->feature_aggregator) {
                throw new \Exception("User ID " . $id . " is already an aggregator");
            }

            DB::transaction(function () use ($user) {
                $user->profile->feature_aggregator = 1;
                $user->profile->save();
            });

            Log::info('User ID ' . $id . ' has been enabled as an aggregator by Admin ID ' . auth()->user()->id);

            return redirect()->route('admin.users.show', $id)->with('success', 'User ID ' . $id . ' has been enabled as an aggregator.');
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

    public function disable_aggregator($id)
    {
        try {
            $user = User::findOrFail($id);
            if(!$user->profile->feature_aggregator) {
                throw new \Exception("User ID " . $id . " is not an aggregator");
            }

            DB::transaction(function () use ($user) {
                $user->profile->feature_aggregator = 0;
                $user->profile->save();
            });

            Log::info('User ID ' . $id . ' has been disabled as an aggregator by Admin ID ' . auth()->user()->id);

            return redirect()->route('admin.users.show', $id)->with('success', 'User ID ' . $id . ' has been disabled as an aggregator.');
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
    
    public function enable_publishing($id)
    {
        try {
            $user = User::findOrFail($id);
            if($user->profile->feature_publishing) {
                throw new \Exception("User ID " . $id . " is already a publisher");
            }

            DB::transaction(function () use ($user) {
                $user->profile->feature_publishing = 1;
                $user->profile->save();
            });

            return redirect()->route('admin.users.show', $id)->with('success', 'User ID ' . $id . ' has been enabled as a publisher.');
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

    public function disable_publishing($id)
    {
        try {
            $user = User::findOrFail($id);
            if(!$user->profile->feature_publishing) {
                throw new \Exception("User ID " . $id . " is not a publisher");
            }

            DB::transaction(function () use ($user) {
                $user->profile->feature_publishing = 0;
                $user->profile->save();
            });

            return redirect()->route('admin.users.show', $id)->with('success', 'User ID ' . $id . ' has been disabled as a publisher.');
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

    public function enable_mcn($id)
    {
        try {
            $user = User::findOrFail($id);
            if($user->profile->feature_mcn) {
                throw new \Exception("User ID " . $id . " is already an MCN");
            }

            DB::transaction(function () use ($user) {
                $user->profile->feature_mcn = 1;
                $user->profile->save();
            });

            return redirect()->route('admin.users.show', $id)->with('success', 'User ID ' . $id . ' has been enabled as an MCN.');
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

    public function disable_mcn($id)
    {
        try {
            $user = User::findOrFail($id);
            if(!$user->profile->feature_mcn) {
                throw new \Exception("User ID " . $id . " is not an MCN");
            }

            DB::transaction(function () use ($user) {
                $user->profile->feature_mcn = 0;
                $user->profile->save();
            });
            
            return redirect()->route('admin.users.show', $id)->with('success', 'User ID ' . $id . ' has been disabled as an MCN.');
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
    
}
