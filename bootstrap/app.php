<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Herd's PHP launcher expects the required file to populate $__herd_closure; define it
// explicitly so Herd doesn't try to call an undefined variable. The closure simply returns
// the normal Laravel application instance.
$__herd_closure = static function () {
    return Application::configure(basePath: dirname(__DIR__))
        ->withRouting(
            web: __DIR__.'/../routes/web.php',
            commands: __DIR__.'/../routes/console.php',
            channels: __DIR__.'/../routes/channels.php',
            health: '/up',
        )
        ->withMiddleware(function (Middleware $middleware): void {
            // Global middleware - runs on EVERY web request
            // SECURITY: EnforceActiveUser enforces immediate access blocking for deactivated users
            $middleware->web([
                \App\Http\Middleware\EnforceActiveUser::class,
            ]);

            // Middleware aliases for route-specific application
            $middleware->alias([
                'role' => \App\Http\Middleware\RoleMiddleware::class,
            ]);
        })
        ->withExceptions(function (Exceptions $exceptions): void {
            //
        })
        ->create();
};

return $__herd_closure();
