<?php

namespace App\Http\Middleware;

use App\Services\SystemSettings;
use Closure;
use Illuminate\Http\Request;

class RoleMaintenance
{
    public function handle(Request $request, Closure $next)
    {
        $settings = app(SystemSettings::class);
        if (!$settings->get('maintenance_enabled')) return $next($request);
        // Keep sign-in/sign-out and password recovery reachable. A server manager can always recover settings.
        if ($request->routeIs('login','logout','password.*','verification.*','branding.*') || $request->is('login', 'logout')) return $next($request);
        $user = $request->user();
        if ($user && $user->can('manage-automation')) return $next($request);
        $targeted = !$user && $settings->get('maintenance_guests');
        if ($user) {
            $roles = $user->roles->pluck('id')->map(fn ($id) => (int) $id)->all();
            $targeted = (bool) array_intersect($roles, $settings->get('maintenance_roles'))
                || (!$roles && $settings->get('maintenance_customers'));
        }
        if (!$targeted) return $next($request);
        $headers = ['Retry-After' => '300', 'Cache-Control' => 'no-store, private'];
        $reason = $settings->get('maintenance_reason');
        if ($request->expectsJson()) return response()->json(['message' => $reason, 'maintenance' => true], 503, $headers);
        return response()->view('maintenance', ['reason' => $reason], 503, $headers);
    }
}
