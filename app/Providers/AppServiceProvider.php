<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;

use App\Models\User\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\SystemSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        app(\App\Services\SystemSettings::class)->apply();
        \Illuminate\Support\Facades\Queue::before(function () {
            app(\App\Services\SystemSettings::class)->refresh();
        });

        Gate::before(function ($user, string $ability) {
            return $user->hasPermission($ability) ? true : null;
        });

        Gate::define('admin', function () {
            return auth()->user()->roles()->count() > 0 ? true : false;
        });

        Gate::define('customer', function() {
            return current_user()->profile ? true : false;
        });

        Gate::define('customer.verified', function() {
            return current_user()->profile && current_user()->profile->status == 'verified';
        });

        Auth::viaRequest('impersonate', function () {
            if (Auth::user() instanceof \App\Models\User\Admin && Auth::user()->can('impersonate-users') && Session::has('impersonate')) {
                return User::find(Session::get('impersonate'));
            }

            return Auth::guard()->user();
        });
    }
}
