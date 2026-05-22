<?php

namespace App\Exceptions;

use Spatie\Permission\Exceptions\UnauthorizedException;
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
                'message' =>  request()->all(),
                'errors' => $e->errors(),
                'payload' => request()->all(),
            ], 422),

            $e instanceof AuthenticationException => response()->json([
                'success' => false,
                'message' => __('validation.unauthenticated'),
            ], 401),

            $e instanceof AuthorizationException => response()->json([
                'success' => false,
                'message' => __('validation.unauthorized'),
            ], 403),
            $e instanceof UnauthorizedException => response()->json([
                'success' => false,
                'message' => __('validation.unauthorized'),
                'error' => $e->getMessage() , // Hide error details in production
                "trace"=> $e->getTrace(), // Hide error details in production <==>
            ], 404),

            $e instanceof NotFoundHttpException => response()->json([
                'success' => false,
                'message' => __('validation.resource_not_found'),
                'error' => $e->getMessage() , // Hide error details in production
                "trace"=> $e->getTrace(), // Hide error details in production <==>
            ], 404),

            $e instanceof CustomException => response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getStatusCode()),

            default => response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again later.',
                'error' =>  $e->getMessage() , // Hide error details in production
                "trace"=> $e->getTrace() , // Hide error details in production <==>
            ], 500),
        };
    }

}
