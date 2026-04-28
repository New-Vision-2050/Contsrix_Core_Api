<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\ProcedureSetting\Database\factories\ProcedureSettingFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Modules\Company\CompanyCore\Models\Company;
use Modules\User\Models\User;

class ProcedureSetting extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;

    protected $table = 'procedure_settings';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'type',
        'execute_type',
        'icon',
        'percentage',
        'deadline_days',
        'deadline_hours',
        'escalation_user_id',
        'company_id',
    ];

    protected $casts = [
        'id'         => 'string',
        'percentage' => 'float',
        'deadline_days' => 'integer',
        'deadline_hours' => 'integer',
        'escalation_user_id' => 'string',
    ];

    public function getRelationshipToPrimaryModel(): string
    {
        return 'company';
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function steps()
    {
        return $this->hasMany(ProcedureSettingStep::class, 'procedure_setting_id');
    }

    public function escalationUser()
    {
        return $this->belongsTo(User::class, 'escalation_user_id');
    }

    protected static function newFactory(): ProcedureSettingFactory
    {
        return ProcedureSettingFactory::new();
    }
}
