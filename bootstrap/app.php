<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        /*
        |--------------------------------------------------------------------------
        | ROUTING CONFIGURATION (Laravel 11/12)
        |--------------------------------------------------------------------------
        | Semua route WEB harus didaftarkan di sini.
        | File tambahan (cashier.php) WAJIB dimasukkan agar ter-load.
        */
        web: [
            __DIR__ . '/../routes/web.php',
            __DIR__ . '/../routes/cashier.php',
            __DIR__ . '/../routes/mobile.php',
        ],

        api: __DIR__ . '/../routes/api.php',

        commands: __DIR__ . '/../routes/console.php',

        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {
        /*
        |--------------------------------------------------------------------------
        | SANCTUM API MIDDLEWARE (Laravel 11/12)
        |--------------------------------------------------------------------------
        */
        $middleware->statefulApi();

        /*
        |--------------------------------------------------------------------------
        | CORS — izinkan Flutter app dari semua origin
        |--------------------------------------------------------------------------
        */
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | MIDDLEWARE ALIAS (Laravel 11/12)
        |--------------------------------------------------------------------------
        | Pengganti Kernel.php (yang sudah tidak ada)
        */
        $middleware->alias([
            'admin'    => \App\Http\Middleware\AdminMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'customer' => \App\Http\Middleware\CustomerMiddleware::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | LITESPEED CACHE — disable caching for Livewire endpoints
        |--------------------------------------------------------------------------
        */
        $middleware->web(append: [
            \App\Http\Middleware\DisableLivewireCache::class,
        ]);
        
        /*
        |--------------------------------------------------------------------------
        | CSRF EXCEPTION (untuk Livewire)
        |--------------------------------------------------------------------------
        */
        $middleware->validateCsrfTokens(except: [
            'admin/field-docs/photo/*',
            'admin/field-docs/photos/*',
            'livewire/*',
            'livewire/message/*',
            'livewire/upload-file',
            'livewire/upload-file/*',
            'livewire/update',
            'livewire/preview-file/*',
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        // Tangani GET /livewire/update yang di-cache oleh LiteSpeed — redirect daripada error 405
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->is('livewire/*') && $request->isMethod('get')) {
                return redirect('/admin/dashboard');
            }
        });
    })

    ->create();
