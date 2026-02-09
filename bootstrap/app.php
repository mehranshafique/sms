<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SetLocale;
// Import your custom middleware
use App\Http\Middleware\TenantApiMiddleware; 
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // Register aliases here
        $middleware->alias([
            'tenant.api' => TenantApiMiddleware::class,
            // Add other aliases if needed, e.g., 'role' => \Spatie\Permission\Middleware\RoleMiddleware::class
        ]);
        // Register the "web" middleware group
        $middleware->web(append: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // ADD THIS LINE HERE:
            \App\Http\Middleware\LoadInstitutionSettings::class, 
            \App\Http\Middleware\CheckSubscription::class, 
            \App\Http\Middleware\CheckModuleAccess::class,
            // Your locale middleware (language switch)
            SetLocale::class,
        ]);

        // API middleware group remains unchanged unless you modify it
        $middleware->api(append: [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
