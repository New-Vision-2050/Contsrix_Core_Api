<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TenantCompatibilityMiddleware
{
    /**
     * Routes that should be excluded from tenant validation
     *
     * @var array
     */
    protected array $excludedRoutes = [
        // Auth module routes (from modules/Auth/Resources/routes/api.php)
        'api/v1/auth/login',
        'api/v1/auth/login-as-admin',
        'api/v1/auth/login-step',
        'api/v1/auth/login-otp',
        'api/v1/auth/validate-reset-password-otp',
        'api/v1/auth/alternative-step-login',
        'api/v1/auth/reset-password',
        'api/v1/auth/get-login-ways',
        'api/v1/auth/forget-password',
        'api/v1/auth/resend-otp',
        'api/v1/auth/check-answers-questions',
        'api/v1/auth/change-email',
        'api/v1/auth/logout',
        'api/v1/auth/get-data-for-login-as-admin',

        // Password reset routes
        'password/*',
        'email/*',

        // Health check and system routes
        'up',
        'api/health',

        // Public routes
        'api/v1/public/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Skip validation for excluded routes
        if ($this->shouldSkipValidation($request)) {
            return $next($request);
        }

        // Skip validation if user is not authenticated
        if (!auth('api')->check()) {
            return $next($request);
        }

        $user = auth('api')->user();

        // Get tenant ID from X-Tenant header (set by DomainToTenantMiddleware)
        $tenantId = $request->header('X-Tenant');

        // If no tenant header, allow request to proceed
        if (!$tenantId) {
            return $next($request);
        }

        // Check if user's company_id matches the tenant
        if ($user->company_id != $tenantId) {
            return $this->forbiddenResponse(
                "User company ({$user->company_id}) is not compatible with tenant ({$tenantId})"
            );
        }

        return $next($request);
    }

    /**
     * Check if the current request should skip tenant validation
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldSkipValidation(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->excludedRoutes as $excludedRoute) {
            // Handle wildcard routes
            if (str_ends_with($excludedRoute, '/*')) {
                $prefix = rtrim($excludedRoute, '/*');
                if (str_starts_with($path, $prefix)) {
                    return true;
                }
            }

            // Handle exact matches
            if ($path === $excludedRoute) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return a forbidden response
     *
     * @param  string  $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json([
            'error' => 'Forbidden',
            'message' => $message,
            'code' => 404
        ], Response::HTTP_FORBIDDEN);
    }
}
