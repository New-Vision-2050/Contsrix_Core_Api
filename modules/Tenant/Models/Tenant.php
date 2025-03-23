<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Tenant\Database\factories\TenantFactory;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasFactory;
    use HasDatabase;
    use HasDomains;

    public static function booted()
    {
        static::creating(function ($tenant) {
            $tenant->password = bcrypt($tenant->id . now());
        });
    }

    /**
     * Get the company associated with the tenant.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }
}
