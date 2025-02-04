<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Exceptions\CustomException;

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
        ]);
        $middleware->append(\App\Http\Middleware\Localization::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle specific exceptions
        $exceptions->render(function (Throwable $e, $request) {
            return match (true) {
                $e instanceof ValidationException => response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422),
                $e instanceof AuthenticationException => response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401),
                $e instanceof AuthorizationException => response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403),
                $e instanceof NotFoundHttpException => response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                ], 404),
                $e instanceof CustomException => response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400),
                default => response()->json([
                    'success' => false,
                    'message' => 'Something went wrong, please try again later.',
                    'error' => env('APP_DEBUG') ? $e->getMessage() : null, // Hide error details in production
                ], 500),
            };
        });
    })
    ->create();
