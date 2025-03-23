# Alternative Multi-Tenancy Approach

This document outlines an alternative approach to implementing multi-tenancy using the existing Company and CompanyUser models instead of creating separate Tenant and TenantUser models.

## Overview

Instead of creating new models for tenancy, we can leverage the existing models:

1. **Company** model instead of Tenant model
2. **CompanyUser** model instead of TenantUser model

This approach simplifies the implementation by reducing the number of models and leveraging the existing relationships.

## Implementation Details

### 1. Using Company Model for Tenancy

The Company model can be extended to implement the TenantWithDatabase interface:

```php
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Company extends Model implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;
    
    // Existing company properties and methods...
    
    // Add tenancy-specific methods
    public function getDomainIdentifier(): string
    {
        return $this->user_name;
    }
}
```

### 2. Using CompanyUser Model for Authentication

The CompanyUser model can implement the JWTSubject interface directly:

```php
use Tymon\JWTAuth\Contracts\JWTSubject;

class CompanyUser extends Authenticatable implements JWTSubject
{
    // Existing CompanyUser properties and methods...
    
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        // Get the current company context
        $company = tenant();
        if (!$company) {
            return [];
        }
        
        // Get the user's role for this company
        $role = 'user';
        $companyRelation = $this->companies()->where('company_id', $company->id)->first();
        if ($companyRelation) {
            $role = $companyRelation->pivot->role ?? 'user';
        }
        
        // Add company_id to JWT claims
        return [
            'company_id' => $company->id,
            'role' => $role,
        ];
    }
}
```

### 3. Authentication Service

The authentication service can be simplified to work directly with the CompanyUser model:

```php
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
}
```

### 4. Middleware

The middleware can be simplified to verify the company context:

```php
class VerifyCompanyToken
{
    public function handle($request, Closure $next)
    {
        try {
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
```

## Benefits of This Approach

1. **Simplicity**: Uses existing models instead of creating new ones
2. **Consistency**: Maintains a single source of truth for company and user data
3. **Reduced Complexity**: Fewer models and relationships to manage
4. **Easier Maintenance**: Changes to company or user data automatically reflect in the tenancy system

## Implementation Steps

1. Extend the Company model to implement TenantWithDatabase
2. Extend the CompanyUser model to implement JWTSubject
3. Create a CompanyAuthService for authentication
4. Create a VerifyCompanyToken middleware
5. Update the routes to use the new middleware
6. Update the controllers to use the new service

## Considerations

1. **Database Structure**: The existing database structure must support the tenancy requirements
2. **Migration**: Existing data may need to be migrated to support the new approach
3. **Performance**: Consider the performance implications of using the existing models
4. **Security**: Ensure proper isolation between companies

## Conclusion

Using the existing Company and CompanyUser models for multi-tenancy can simplify the implementation and reduce the complexity of the system. However, it requires careful consideration of the existing database structure and may require some migration of existing data.