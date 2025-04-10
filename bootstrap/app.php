<?php

use App\Http\Middleware\TenancePermision;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Exceptions\Handler;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'lang' => \App\Http\Middleware\Localization::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'domain.tenant' => \App\Http\Middleware\DomainToTenantMiddleware::class,
        ]);
        $middleware->append(\App\Http\Middleware\Localization::class);
        $middleware->append(\App\Http\Middleware\TenancePermision::class);
        $middleware->prepend(\App\Http\Middleware\DomainToTenantMiddleware::class);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, $request) {
            return Handler::handle($e); // Use the custom handler
        });
    })
    ->create();
