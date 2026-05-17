<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class MedicalInsuranceCategory extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;

    protected $table = 'medical_insurance_categories';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'medical_insurance_id',
        'company_id',
        'name',
        'type',
        'coverage_limit',
        'description',
    ];

    protected $casts = [
        'id'                   => 'string',
        'medical_insurance_id' => 'string',
        'company_id'           => 'string',
        'coverage_limit'       => 'decimal:2',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function medicalInsurance(): BelongsTo
    {
        return $this->belongsTo(MedicalInsurance::class, 'medical_insurance_id');
    }
}
