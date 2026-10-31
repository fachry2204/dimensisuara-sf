<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
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
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
 
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        $admin = \App\Models\User\Admin::where('email', $credentials['email'])->first();
        $guard = $admin ? 'admin' : 'web';
        if (Auth::guard($guard)->attempt($credentials, $request->boolean('remember'))) {
            Auth::shouldUse($guard);
            $request->session()->forget(['impersonate', 'impersonate_name']);

            // Allow if user is admin
            if(Gate::check('admin')) {
                Auth::guard('web')->logout();
                $this->clearLoginAttempts($request);
                $request->session()->regenerate();
                return redirect()->intended(route('admin.home'));
            }

            // Allow if user has a profile
            if($guard === 'web' && auth()->user()->profile) {
                $this->clearLoginAttempts($request);
                $request->session()->regenerate();
                return redirect()->intended(route('home'));
            }

            Auth::logout();
        }
        $this->incrementLoginAttempts($request);
 
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }
}
