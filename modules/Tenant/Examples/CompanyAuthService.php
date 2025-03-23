<?php

declare(strict_types=1);

namespace Modules\Tenant\Examples;

use Illuminate\Support\Facades\Hash;
use Modules\CompanyUser\Models\CompanyUser;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Example of a company authentication service.
 * This is an alternative approach to creating a separate TenantAuthService.
 */
class CompanyAuthService
{
    /**
     * Attempt to authenticate a company user.
     *
     * @param string $email
     * @param string $password
     * @return array|null
     */
    public function login(string $email, string $password): ?array
    {
        // Check if we're in a company context
        if (!tenant()) {
            throw new \Exception('Company context not initialized', 403);
        }

        // Find the user by email
        $user = CompanyUser::where('email', $email)->first();

        if (!$user) {
            return null;
        }

        // Check if the user belongs to the current company
        $companyIds = $user->companies->pluck('id')->toArray();
        if (!in_array(tenant()->id, $companyIds)) {
            return null;
        }

        // Get the user's role for this company
        $companyUserRelation = $user->companies->where('id', tenant()->id)->first()->pivot;
        $role = $companyUserRelation->role ?? 'user';
        $status = $companyUserRelation->status ?? 'active';

        // Check if the user is active in this company
        if ($status !== 'active') {
            return null;
        }

        // Verify password
        if (!Hash::check($password, $user->password)) {
            return null;
        }

        // Generate token with company context
        try {
            // Create custom claims with company information
            $customClaims = [
                'company_id' => tenant()->id,
                'role' => $role,
                'company_user_id' => $user->id
            ];
            
            $token = JWTAuth::claims($customClaims)->fromUser($user);
            
            return [
                'token' => $token,
                'user' => $user,
                'company_id' => tenant()->id,
                'role' => $role
            ];
        } catch (JWTException $e) {
            return null;
        }
    }

    /**
     * Get the authenticated user.
     *
     * @return CompanyUser|null
     */
    public function getAuthenticatedUser(): ?CompanyUser
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

    /**
     * Refresh the JWT token.
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
     * Invalidate the JWT token.
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