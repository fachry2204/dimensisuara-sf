<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web/public.php',
            __DIR__.'/../routes/web/admin.php',
            __DIR__.'/../routes/web/customer.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectUsersTo(fn (\Illuminate\Http\Request $request) =>
            $request->user() instanceof \App\Models\User\Admin ? route('admin.home') : route('home'));
        $middleware->web(append: [\App\Http\Middleware\UseAccountGuard::class, \App\Http\Middleware\RoleMaintenance::class]);
        $middleware->appendToPriorityList(\Illuminate\Session\Middleware\StartSession::class, \App\Http\Middleware\UseAccountGuard::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // $exceptions->render(function (HttpException $exception, Request $request) {
        //     if($exception->getStatusCode() == 404) {
        //         return response()->view('errors.404', [], 404);
        //     }
        //     if($exception->getStatusCode() == 403) {
        //         return response()->view('errors.403', [], 403);
        //     }
        //     if($exception->getStatusCode() == 500) {
        //         return response()->view('errors.500', [], 500);
        //     }
        // });
    })->create();
