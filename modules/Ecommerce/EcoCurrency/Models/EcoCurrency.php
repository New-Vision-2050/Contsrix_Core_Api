<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCurrency\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ecommerce\EcoCurrency\Database\factories\EcoCurrencyFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Shared\Currency\Models\Currency;

class EcoCurrency extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'currency_id',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'currency_id' => 'string',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
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

    protected static function newFactory(): EcoCurrencyFactory
    {
        return EcoCurrencyFactory::new();
    }
}
