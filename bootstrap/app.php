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
            'ai.access'  => \App\Http\Middleware\EnsureAiAccess::class,
            // Add other aliases if needed, e.g., 'role' => \Spatie\Permission\Middleware\RoleMiddleware::class
        ]);
        $middleware->validateCsrfTokens(except: [
            'webhooks/payments/*',
        ]);

        // Append only app-specific middleware (Laravel registers defaults separately).
        $middleware->web(append: [
            \App\Http\Middleware\LoadInstitutionSettings::class,
            \App\Http\Middleware\CheckSubscription::class,
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
