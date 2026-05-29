<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\IPAddress;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ── Trusted proxies ──────────────────────────────────────────────────
        // Trust all proxies so $request->ip() returns the real client IP when
        // the app runs behind Nginx in Docker, a load balancer, or Cloudflare.
        // In production, restrict this to your specific proxy/LB IP range.
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES', '*'),
            headers: Request::HEADER_X_FORWARDED_FOR
                   | Request::HEADER_X_FORWARDED_HOST
                   | Request::HEADER_X_FORWARDED_PORT
                   | Request::HEADER_X_FORWARDED_PROTO
                   | Request::HEADER_X_FORWARDED_AWS_ELB
        );

        // ── Middleware aliases ────────────────────────────────────────────────
        $middleware->alias([
            'restrict.ip'        => IPAddress::class,
            // Spatie Laravel Permission middleware aliases (required for Laravel 11+)
            'role'               => RoleMiddleware::class,
            'permission'         => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
