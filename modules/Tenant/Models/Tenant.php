<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Modules\Company\CompanyCore\Models\Company;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    protected $guarded = [];

    public static function booted()
    {
        static::creating(function ($tenant) {
            $tenant->password = bcrypt($tenant->id . now());
        });
    }

    /**
     * Get the company associated with the tenant.
     * Note: company_id is stored in the data column.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Define which columns are stored directly in the database table
     * and which ones are stored in the data JSON column.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'created_at',
            'updated_at',
            'data',
            'password',
            'company_id'
        ];
    }
}
