<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class TenantAuthService
{
    /**
     * Attempt to authenticate a tenant user.
     *
     * @param string $email
     * @param string $password
     * @return array|null
     */
    public function login(string $email, string $password): ?array
    {
        // Check if we're in a tenant context
        if (!tenant()) {
            throw new \Exception('Tenant context not initialized', 403);
        }

        // Find the user by email
        $user = TenantUser::where('email', $email)->first();

        if (!$user) {
            return null;
        }

        // Check if the user belongs to the current tenant's company
        $companyIds = $user->companies->pluck('id')->toArray();
        if (!in_array(tenant()->company_id, $companyIds)) {
            return null;
        }

        // Get the user's role for this company
        $companyUserRelation = $user->companies->where('id', tenant()->company_id)->first()->pivot;
        $role = $companyUserRelation->role ?? 'user';
        $status = $companyUserRelation->status ?? 'active';

        // Generate token with tenant context
        try {
            // Create custom claims with tenant and company information
            $customClaims = [
                'tenant_id' => tenant()->id,
                'company_id' => tenant()->company_id,
                'role' => $role,
                'company_user_id' => $user->id
            ];

            $token = JWTAuth::claims($customClaims)->fromUser($user);

            return [
                'token' => $token,
                'user' => $user,
                'tenant_id' => tenant()->id,
                'company_id' => tenant()->company_id,
                'role' => $role
            ];
        } catch (JWTException $e) {
            return null;
        }
    }

    /**
     * Get the authenticated user from the token.
     *
     * @return TenantUser|null
     */
    public function getAuthenticatedUser(): ?TenantUser
    {

        try {
            $user = JWTAuth::parseToken()->authenticate();

            // Verify the user is in the current tenant context
            $claims = JWTAuth::parseToken()->getPayload()->get('tenant_id');
            if ($claims !== tenant()->id) {
                return null;
            }

            return $user;
        } catch (JWTException $e) {
            return null;
        }
    }

    /**
     * Refresh the token.
     *
     * @return string|null
     */
    public function refreshToken(): ?string
    {
        try {
            return JWTAuth::parseToken()->refresh();
        } catch (JWTException $e) {
            return null;
        }
    }

    /**
     * Invalidate the token.
     *
     * @return bool
     */
    public function logout(): bool
    {
        try {
            JWTAuth::parseToken()->invalidate();
            return true;
        } catch (JWTException $e) {
            return false;
        }
    }
}
