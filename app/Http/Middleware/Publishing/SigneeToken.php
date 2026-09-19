<?php

namespace App\Http\Middleware\Publishing;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Exceptions\Publishing\InvalidApprovalStatusException;

use App\Models\Publishing\Signee;

class SigneeToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // Get signee
        $token  = $request->route('token');
        $signee = Signee::where('token', $token)->first();

        if($signee) {

            // Attach signee to request for easy access
            $request->merge([
                'signee'    => $signee,
                'token'     => $request->route('token')
            ]);

            return $next($request);
        }
        
        return redirect()->route('sign.invalid');
    }
}
