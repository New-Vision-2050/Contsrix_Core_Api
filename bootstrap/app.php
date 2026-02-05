<?php

use App\Http\Middleware\TenancePermision;
use App\Http\Middleware\Localization;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleOrPermissionMiddleware;
use App\Http\Middleware\DomainToTenantMiddleware;
use App\Http\Middleware\TenantCompatibilityMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Exceptions\Handler;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )->withCommands([
        \App\Console\Commands\TestMailSendCommand::class,
        \App\Console\Commands\AuditAbsencesCommand::class,
                           ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'lang' => \App\Http\Middleware\Localization::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'domain.tenant' => \App\Http\Middleware\DomainToTenantMiddleware::class,
            'tenant.compatibility' => TenantCompatibilityMiddleware::class,
        ]);
        $middleware->append(\App\Http\Middleware\Localization::class);
        $middleware->append(\App\Http\Middleware\TenancePermision::class);
        // $middleware->append(\Sentry\Laravel\Tracing\Middleware::class);
        $middleware->append(TenantCompatibilityMiddleware::class);
        $middleware->prepend(\App\Http\Middleware\DomainToTenantMiddleware::class);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
        $exceptions->render(function (Throwable $e, $request) {
            return Handler::handle($e); // Use the custom handler
        });
    })
    ->create();
