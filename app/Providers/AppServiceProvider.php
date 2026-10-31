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
        \App\Models\Release::observe(\App\Observers\StatusNotificationObserver::class);
        \App\Models\User\Profile::observe(\App\Observers\StatusNotificationObserver::class);
        \App\Models\User\User::observe(\App\Observers\StatusNotificationObserver::class);
        // Composer and installation commands must boot before a database exists.
        // Runtime requests and operational commands still load database settings.
        if ($this->shouldApplyDatabaseSettings()) {
            app(\App\Services\SystemSettings::class)->apply();
        }
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

    private function shouldApplyDatabaseSettings(): bool
    {
        if (!$this->app->runningInConsole()) return true;

        $command = (new \Symfony\Component\Console\Input\ArgvInput)->getFirstArgument();
        if (in_array($command, ['package:discover', 'key:generate', 'storage:link', 'storage:unlink', 'list', 'help', 'about', 'env', 'test', 'completion'], true)) return false;

        foreach (['migrate', 'db:', 'schema:', 'config:', 'cache:', 'route:', 'view:', 'event:', 'optimize', 'vendor:', 'make:', 'install:', 'lang:'] as $prefix) {
            if ($command !== null && str_starts_with($command, $prefix)) return false;
        }

        return true;
    }
}
