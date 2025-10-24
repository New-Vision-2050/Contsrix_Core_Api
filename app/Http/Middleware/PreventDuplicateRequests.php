<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PreventDuplicateRequests
{
    /**
     * Handle an incoming request.
     * Prevents duplicate requests within a short time window.
     * Returns cached response or success message instead of error.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param int $seconds Time window for deduplication (default: 2 seconds)
     * @return mixed
     */
    public function handle(Request $request, Closure $next, int $seconds = 2)
    {
        // Only apply to GET requests (to avoid blocking legitimate POST/PUT/DELETE operations)
        // You can remove this check if you want to deduplicate all requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Create unique key for this request based on:
        // - User ID (if authenticated)
        // - Request path
        // - Query parameters
        $key = $this->generateRequestKey($request);

        // Check if this exact request was made recently
        $cachedResponse = Cache::get($key);
        
        if ($cachedResponse !== null) {
            Log::info('Duplicate request detected and handled', [
                'user_id' => $request->user()?->id,
                'path' => $request->path(),
                'key' => $key,
            ]);

            // Return cached response with 200 status
            // This prevents frontend from seeing it as an error
            return response()->json(
                $cachedResponse,
                200,
                ['X-Request-Cached' => 'true']
            );
        }

        // Process the request normally
        $response = $next($request);

        // Cache successful responses only (200-299 status codes)
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $responseData = json_decode($response->getContent(), true);
            
            if ($responseData !== null) {
                // Store response for the specified time window
                Cache::put($key, $responseData, $seconds);
            }
        }

        return $response;
    }

    /**
     * Generate a unique cache key for the request
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    private function generateRequestKey(Request $request): string
    {
        // Include user ID, path, and all query parameters
        $components = [
            'user:' . ($request->user()?->id ?? 'guest'),
            'path:' . $request->path(),
            'params:' . json_encode($request->query()),
        ];

        return 'request:' . md5(implode('|', $components));
    }
}
