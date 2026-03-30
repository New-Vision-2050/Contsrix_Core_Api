<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Modules\Company\CompanyCore\Models\Company;
use Modules\User\Models\User;

class ProcedureSettingStep extends Model
{
    use HasFactory;
    use BaseFilterable;
    use BelongsToTenant;

    protected $table = 'procedure_setting_steps';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'employee_id',
        'is_accept',
        'is_approve',
        'duration',
        'forms',
        'procedure_setting_id',
        'company_id',
        'name',
    ];

    protected $casts = [
        'is_accept'  => 'integer',
        'is_approve' => 'integer',
        'duration'   => 'integer',
    ];

    public function getRelationshipToPrimaryModel(): string
    {
        return 'company';
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function procedureSetting()
    {
        return $this->belongsTo(ProcedureSetting::class, 'procedure_setting_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
