<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ecommerce\DealDay\Database\factories\DealDayFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use App\Traits\ForcedBelongsToTenant;

class DealDay extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use ForcedBelongsToTenant;
    use HasTranslations;

    public array $translatable = ['name'];

    protected $table = 'deal_days';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'name',
        'product_id',
        'date_offer',
        'discount_type',
        'discount_value',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'product_id' => 'string',
        'date_offer' => 'date:Y-m-d',
        'discount_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): DealDayFactory
    {
        return DealDayFactory::new();
    }

    /**
     * Get the company that owns the deal day
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the product associated with the deal day
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(EcoProduct::class, 'product_id');
    }

    /**
     * Scope to get active deal days
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get inactive deal days
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
}
