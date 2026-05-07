<?php

declare(strict_types=1);

namespace Modules\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Enums\TokenAbility;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckTokenAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $token = JWTAuth::getToken();

        if (!$token) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        $payload = JWTAuth::getPayload($token);
        $tokenAbility = $payload->get('token_ability');

        if ($tokenAbility !== $ability) {
            return response()->json(['message' => 'Invalid token type'], 403);
        }

        return $next($request);
    }
}
