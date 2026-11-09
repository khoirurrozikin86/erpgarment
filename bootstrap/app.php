<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Spatie\Permission\Middleware\{RoleMiddleware, PermissionMiddleware, RoleOrPermissionMiddleware};
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SetCompanyContext;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);


        // Set active company untuk seluruh request web
        $middleware->web(append: [
            SetCompanyContext::class,
        ]);



        // User yang sudah login tidak boleh kembali ke /login
        $middleware->redirectUsersTo(
            fn() => route('super.dashboard')
        );

        // User yang belum login diarahkan ke /login
        $middleware->redirectGuestsTo(
            fn() => route('login')
        );
    })

    ->withCommands([
        \App\Console\Commands\MakeDtoCommand::class,
        \App\Console\Commands\MakeServiceCommand::class,
        \App\Console\Commands\MakeDomainCommand::class,
    ])

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
