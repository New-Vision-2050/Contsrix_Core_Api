<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoPayment\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ecommerce\EcoPayment\Database\factories\EcoPaymentFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Shared\Payment\Models\Payment;

class EcoPayment extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'payment_id',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'payment_id' => 'string',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): EcoPaymentFactory
    {
        return EcoPaymentFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
