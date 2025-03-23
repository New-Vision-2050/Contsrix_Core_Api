<?php

declare(strict_types=1);

namespace Modules\Tenant\Examples;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\CompanyUser\Models\CompanyUser as BaseCompanyUser;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Example of how to extend the CompanyUser model to support JWT authentication.
 * This is an alternative approach to creating a separate TenantUser model.
 */
class CompanyUserWithJWT extends BaseCompanyUser implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

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
            'company_user_id' => $this->id
        ];
    }

    /**
     * Get the companies that the user belongs to.
     */
    public function companies()
    {
        return $this->belongsToMany(
            \Modules\Company\CompanyCore\Models\Company::class,
            'company_users_companies',
            'company_user_id',
            'company_id'
        )->withPivot('role', 'status');
    }

    /**
     * Get the roles for a specific company.
     *
     * @param string $companyId
     * @return \Illuminate\Support\Collection
     */
    public function rolesForCompany($companyId)
    {
        return $this->companies->where('id', $companyId)->sortByDesc('role')->pluck("pivot");
    }
}