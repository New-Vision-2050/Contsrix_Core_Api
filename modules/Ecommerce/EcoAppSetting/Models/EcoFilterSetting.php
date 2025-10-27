<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyCore\Models\Company;
use App\Traits\ForcedBelongsToTenant;

class EcoFilterSetting extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use ForcedBelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'filter_name',
        'filter_key',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
    ];

    /**
     * Company relationship
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByKey($query, string $filterKey)
    {
        return $query->where('filter_key', $filterKey);
    }
}
