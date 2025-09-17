<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ecommerce\EcoBankAccount\Database\factories\EcoBankAccountFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Country\Models\Country;
use Modules\Shared\Bank\Models\Bank;
use Modules\Shared\Currency\Models\Currency;

class EcoBankAccount extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'bank_id',
        'account_holder_name',
        'account_number',
        'iban',
        'country_id',
        'is_primary',
        'is_active',
    ];


    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    protected static function newFactory(): EcoBankAccountFactory
    {
        return EcoBankAccountFactory::new();
    }
}
