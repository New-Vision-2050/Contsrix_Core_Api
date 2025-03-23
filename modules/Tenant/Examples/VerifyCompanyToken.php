<?php

declare(strict_types=1);

namespace Modules\Tenant\Examples;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Example of a middleware to verify the company token.
 * This is an alternative approach to creating a separate VerifyTenantToken middleware.
 */
class VerifyCompanyToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Authenticate the user
            $user = JWTAuth::parseToken()->authenticate();
            
            // Get the company_id from the token
            $payload = JWTAuth::parseToken()->getPayload();
            $tokenCompanyId = $payload->get('company_id');
            
            // Check if the token's company_id matches the current company
            if (!tenant() || $tokenCompanyId !== tenant()->id) {
                return response()->json(['message' => 'Invalid company access'], 403);
            }
            
            // Add the user's role to the request for use in controllers
            $request->attributes->add(['user_role' => $payload->get('role', 'user')]);
            
            return $next($request);
        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Token expired'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token absent'], 401);
        }
    }
}