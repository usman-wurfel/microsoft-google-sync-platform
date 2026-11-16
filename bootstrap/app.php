<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'prevent.back' => \App\Http\Middleware\PreventBackHistory::class,
        ]);
        
        // Exclude CSRF verification for response back URL and phishing report API (called by Outlook add-in)
        $middleware->validateCsrfTokens(except: [
            'responsebackurl',
            'api/phishing/report',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
