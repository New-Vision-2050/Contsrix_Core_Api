<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
class Handler
{
    public static function handle(Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof ValidationException => response()->json([
                'success' => false,
                'message' => __('validation.validation_failed'),
                'errors' => $e->errors(),
            ], 422),

            $e instanceof AuthenticationException => response()->json([
                'success' => false,
                'message' => __('validation.unauthenticated'),
            ], 401),

            $e instanceof AuthorizationException => response()->json([
                'success' => false,
                'message' => __('validation.unauthorized'),
            ], 403),

            $e instanceof NotFoundHttpException => response()->json([
                'success' => false,
                'message' => __('validation.resource_not_found'),
            ], 404),

            $e instanceof CustomException => response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getStatusCode()),

            default => response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again later.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null, // Hide error details in production
                "trace"=>env('APP_DEBUG') ? $e->getTrace() : null, // Hide error details in production <==>
            ], 500),
        };
    }

}
