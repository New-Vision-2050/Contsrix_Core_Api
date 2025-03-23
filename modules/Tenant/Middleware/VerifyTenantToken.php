<?php

declare(strict_types=1);

namespace Modules\Tenant\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Tenant;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class VerifyTenantToken
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
            // Check if user is authenticated
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                // Get the user ID from the token
                $payload = JWTAuth::parseToken()->getPayload();
                $userId = $payload->get('company_user_id');

                // Try to find the user in the central database
                $centralUser = null;

                // We need to explicitly use the central connection without changing the default
                try {
                    $centralUser = \Modules\CompanyUser\Models\CompanyUser::on('central')->find($userId);
                    \Log::info('Checking central database for user: ' . $userId . ' - Found: ' . ($centralUser ? 'Yes' : 'No'));
                    
                    // Check if tenant database exists and has been initialized
                    if (!tenant()->database()->manager()->databaseExists(tenant()->database()->getName())) {
                        \Log::warning('Tenant database does not exist: ' . tenant()->database()->getName());
                        
                        // Create the tenant database
                        tenant()->database()->manager()->createDatabase(tenant()->database()->getName());
                        \Log::info('Created tenant database: ' . tenant()->database()->getName());
                        
                        // Run migrations for the tenant
                        \Artisan::call('tenants:migrate', [
                            '--tenants' => [tenant()->id]
                        ]);
                        \Log::info('Ran migrations for tenant: ' . tenant()->id);
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to find user in central database or check tenant database: ' . $e->getMessage());
                    \Log::error($e->getTraceAsString());
                }

                // If user exists in central database, create it in tenant database
                if ($centralUser) {

                        \Log::info('Creating user in tenant database: ' . $userId . ' (Tenant: ' . tenant()->id . ')');
                        $tenant = Tenant::findOrFail($payload->get('tenant_id'));
                        dd(tenant('name'));
                        $tenantUser = new \Modules\Tenant\Models\TenantUser();


                        \Log::info('User created successfully in tenant database');

                        // Associate the user with the tenant's company
                        $tenantUser->companies()->attach(tenant()->company_id, [
                            'role' => $payload->get('role', '1'),
                            'status' => 'active'
                        ]);

                        \Log::info('User associated with company: ' . tenant()->company_id);

                        // Try to authenticate again
                        $user = JWTAuth::parseToken()->authenticate();

                        if ($user) {
                            \Log::info('User authenticated successfully after creation');
                        } else {
                            \Log::warning('User still not authenticated after creation');
                        }

                } else {
                    \Log::warning('User not found in central database: ' . $userId);
                }

                // If still no user, return error
                if (!$user) {
                    return response()->json(['message' => 'User not found'], 404);
                }

}
            // Get the tenant_id from the token
            $payload = JWTAuth::parseToken()->getPayload();
            $tokenTenantId = $payload->get('tenant_id');

            // Get the company_id from the token
            $tokenCompanyId = $payload->get('company_id');

            // Check if the token's tenant_id matches the current tenant
            if (!tenant() || $tokenTenantId !== tenant()->id) {
                return response()->json(['message' => 'Invalid tenant access'], 403);
            }

            // Check if the token's company_id matches the current tenant's company_id
            if ($tokenCompanyId !== tenant()->company_id) {
                return response()->json(['message' => 'Invalid company access'], 403);
            }

            // Add the user's role to the request for use in controllers
            $request->attributes->add(['user_role' => $payload->get('role', 'user')]);

        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Token expired'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalid'], 401);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token absent'], 401);
        }

        return $next($request);
    }
}
