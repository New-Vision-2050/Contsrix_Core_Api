<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WorkFlow extends Model
{
    use UuidTrait;
    use BelongsToTenant;

    protected $table = 'work_flows';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'name',
        'type',
    ];

    protected $casts = [
        'id'         => 'string',
        'company_id' => 'string',
        'type'       => 'string',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

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

    public function procedureSettings()
    {
        return $this->hasMany(ProcedureSetting::class, 'work_flow_id');
    }

    /**
     * Default workflow per company (aligned with {@see \Modules\ProcedureSetting\Database\Seeders\WorkFlowForBranchesSeeder}).
     *
     * Bypasses the tenant global scope because callers may ask for a company
     * other than the currently initialized tenant (e.g. central seeders). Uses
     * insertOrIgnore so concurrent/rerun callers never hit the unique index.
     */
    public static function defaultForCompany(
        string $companyId,
        string $type = ProcedureSettingType::ClientRequest->value
    ): self
    {
        $workFlow = static::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('name', 'default')
            ->where('type', $type)
            ->first();

        if ($workFlow !== null) {
            return $workFlow;
        }

        $id = (string) Str::uuid();
        $now = now();

        DB::table('work_flows')->insertOrIgnore([
            'id'         => $id,
            'company_id' => $companyId,
            'name'       => 'default',
            'type'       => $type,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $workFlow = static::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('name', 'default')
            ->where('type', $type)
            ->first();

        if ($workFlow === null) {
            throw new \RuntimeException(
                "Unable to create or resolve default work flow for company [{$companyId}] type [{$type}]."
            );
        }

        return $workFlow;
    }
}
