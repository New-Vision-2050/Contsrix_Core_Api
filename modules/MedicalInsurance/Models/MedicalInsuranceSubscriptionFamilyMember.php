<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalInsuranceSubscriptionFamilyMember extends Model
{
    use HasFactory;
    use UuidTrait;
    use SoftDeletes;

    protected $table = 'medical_insurance_subscription_family_members';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'medical_insurance_subscription_id',
        'name',
        'national_id',
        'relation',
        'amount',
        'subscription_no',
    ];

    protected $casts = [
        'id'                                => 'string',
        'medical_insurance_subscription_id' => 'string',
        'amount'                            => 'decimal:2',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MedicalInsuranceSubscription::class, 'medical_insurance_subscription_id');
    }
}
