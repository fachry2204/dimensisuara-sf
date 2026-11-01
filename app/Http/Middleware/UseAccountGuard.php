<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UseAccountGuard
{
    public function handle(Request $request, Closure $next)
    {
        Auth::shouldUse('web');
        // Admin and customer sessions/remember cookies use independent guards.
        if (Auth::guard('admin')->check()) {
            Auth::shouldUse('admin');
            $impersonating = $request->session()->has('impersonate') && Auth::user()->can('impersonate-users');
            if ($request->routeIs('home') && !$impersonating) return redirect()->route('admin.home');
        }
        return $next($request);
    }
}
