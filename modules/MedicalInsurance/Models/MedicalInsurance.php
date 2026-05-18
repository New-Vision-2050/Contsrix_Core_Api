<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\MedicalInsurance\Database\factories\MedicalInsuranceFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;


class MedicalInsurance extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'policy_number',
        'provider',
        'employee_id',
        'company_id',
        'start_date',
        'end_date',
        'value',
        'individuals_count',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'employee_id' => 'string',
        'company_id' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'value' => 'decimal:2',
        'individuals_count' => 'integer',
        'status' => 'integer',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function category()
    {
        return $this->hasOne(MedicalInsuranceCategory::class);
    }

    protected static function newFactory(): MedicalInsuranceFactory
    {
        return MedicalInsuranceFactory::new();
    }
}
