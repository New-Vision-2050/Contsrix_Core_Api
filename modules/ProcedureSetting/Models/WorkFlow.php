<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

class WorkFlow extends Model
{
    use UuidTrait;

    protected $table = 'work_flows';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'name',
    ];

    protected $casts = [
        'id'         => 'string',
        'company_id' => 'string',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function managementHierarchies()
    {
        return $this->belongsToMany(
            ManagementHierarchy::class,
            'management_hierarchy_work_flow',
            'work_flow_id',
            'management_hierarchy_id'
        )->withTimestamps();
    }

    /**
     * Default workflow per company (aligned with {@see \Modules\ProcedureSetting\Database\Seeders\WorkFlowForBranchesSeeder}).
     */
    public static function defaultForCompany(string $companyId): self
    {
        return static::query()->firstOrCreate(
            [
                'company_id' => $companyId,
                'name'       => 'default',
            ],
            ['id' => (string) Str::uuid()],
        );
    }
}
