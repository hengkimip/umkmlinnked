<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias middleware Spatie Permission (PRD §5)
        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Cek peran SEBELUM route model binding: pengguna tanpa hak mendapat 403 dan tidak bisa
        // menebak ID mana yang ada (tanpa ini, ID yang tidak ada menghasilkan 404 lebih dulu).
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \Spatie\Permission\Middleware\RoleMiddleware::class,
        );

        // Header keamanan (nosniff, anti-clickjacking, HSTS di HTTPS, no-store untuk admin)
        $middleware->web(append: [\App\Http\Middleware\SecurityHeaders::class]);

        // Pengguna yang sudah login dan membuka /login diarahkan sesuai peran (FR-16)
        $middleware->redirectUsersTo(
            fn (Request $request) => $request->user()?->homeUrl() ?? '/'
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // fetch() di panel admin (Accept: application/json) menerima galat validasi 422 JSON, bukan redirect
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
