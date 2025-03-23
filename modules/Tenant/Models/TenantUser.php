<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\CompanyUser\Models\CompanyUser;
use Tymon\JWTAuth\Contracts\JWTSubject;

class TenantUser extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'company_users';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

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
        // Get the current tenant
        $tenant = tenant();
        if (!$tenant) {
            return [];
        }

        // Get the user's role for this company
        $role = 'user';
        $companyRelation = $this->companies()->where('company_id', $tenant->company_id)->first();
        if ($companyRelation) {
            $role = $companyRelation->pivot->role ?? 'user';
        }

        // Add tenant_id and company_id to JWT claims
        return [
            'tenant_id' => $tenant->id,
            'company_id' => $tenant->company_id,
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
