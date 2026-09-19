<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class CustomerVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $return = null;

        if(Gate::check('customer.verified')) {
            $return = $next($request);
        }
        elseif (Gate::check('customer')){
            $return = redirect()->route('pending');
        }
        elseif (Gate::check('admin')) {
            $return = redirect()->route('admin.home');
        }
        else {
            Auth::logout();
            $return = redirect()->route('login');
        }

        return $return;
    }
}
