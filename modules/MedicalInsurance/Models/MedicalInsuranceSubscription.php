<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Models;

use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\User\Models\User;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class MedicalInsuranceSubscription extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'medical_insurance_id',
        'medical_insurance_category_id',
        'company_id',
        'amount',
        'subscription_no',
        'status',
    ];

    protected $casts = [
        'id'                            => 'string',
        'user_id'                       => 'string',
        'medical_insurance_id'          => 'string',
        'medical_insurance_category_id' => 'string',
        'company_id'                    => 'string',
        'amount'                        => 'decimal:2',
        'status'                        => 'integer',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function medicalInsurance(): BelongsTo
    {
        return $this->belongsTo(MedicalInsurance::class, 'medical_insurance_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MedicalInsuranceCategory::class, 'medical_insurance_category_id');
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(MedicalInsuranceSubscriptionFamilyMember::class, 'medical_insurance_subscription_id');
    }
}
