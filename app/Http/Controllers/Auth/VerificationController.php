<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\VerifiesEmails;

use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails { resend as protected resendVerificationEmail; }

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    
    public function show()
    {
        $user = Auth::user();
        if ($this->acceptProvisionedAdmin($user)) return redirect()->route('admin.home');

        if (!$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            return view('auth.verify');
        }

        return redirect($this->redirectPath());
    }

    public function resend(\Illuminate\Http\Request $request)
    {
        if ($this->acceptProvisionedAdmin($request->user())) return redirect()->route('admin.home');
        return $this->resendVerificationEmail($request);
    }

    public function redirectTo(): string
    {
        return Auth::user() instanceof \App\Models\User\Admin ? route('admin.home') : route('home');
    }

    private function acceptProvisionedAdmin($user): bool
    {
        if (!$user instanceof \App\Models\User\Admin) return false;
        abort_unless(\Illuminate\Support\Facades\Gate::allows('admin'),403);
        if (!$user->hasVerifiedEmail()) $user->markEmailAsVerified();
        return true;
    }
}
