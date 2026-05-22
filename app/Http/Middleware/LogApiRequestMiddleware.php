<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\ApiRequestLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequestMiddleware
{
    public function __construct(
        private readonly ApiRequestLogService $logService,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldLog($request)) {
            return $next($request);
        }

        $startedAt = microtime(true);
        $response  = $next($request);

        $this->logService->store($request, $response, $startedAt);

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        if (! config('api_request_log.enabled', true)) {
            return false;
        }

        if (! $request->is('api/*')) {
            return false;
        }

        $path = $request->path();

        foreach (config('api_request_log.excluded_paths', []) as $excluded) {
            if (str_ends_with($excluded, '/*')) {
                $prefix = rtrim($excluded, '/*');
                if (str_starts_with($path, $prefix)) {
                    return false;
                }
            } elseif ($path === $excluded) {
                return false;
            }
        }

        return true;
    }
}
