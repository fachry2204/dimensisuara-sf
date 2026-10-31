<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/';

    public function broker()
    {
        $admin = \App\Models\User\Admin::where('email', request('email'))->exists();
        return \Illuminate\Support\Facades\Password::broker($admin ? 'admins' : 'users');
    }

    protected function guard()
    {
        $admin = \App\Models\User\Admin::where('email', request('email'))->exists();
        return \Illuminate\Support\Facades\Auth::guard($admin ? 'admin' : 'web');
    }
}
