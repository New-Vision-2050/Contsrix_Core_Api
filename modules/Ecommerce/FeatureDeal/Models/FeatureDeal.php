<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ecommerce\FeatureDeal\Database\factories\FeatureDealFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Modules\Company\CompanyCore\Models\Company;
use Carbon\Carbon;

class FeatureDeal extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;

    public array $translatable = ['name'];

    protected $table = 'feature_deals';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'name',
        'start_date',
        'end_date',
        'discount_type',
        'discount_value',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'discount_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): FeatureDealFactory
    {
        return FeatureDealFactory::new();
    }

    /**
     * Get the company that owns the feature deal
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope to get active feature deals
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get inactive feature deals
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to get current feature deals (within date range)
     */
    public function scopeCurrent($query)
    {
        $today = Carbon::today();
        return $query->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today);
    }

    /**
     * Check if the deal is currently active and within date range
     */
    public function getIsCurrentAttribute(): bool
    {
        $today = Carbon::today();
        return $this->is_active && 
               $this->start_date <= $today && 
               $this->end_date >= $today;
    }

    /**
     * Get the status text
     */
    public function getStatusTextAttribute(): string
    {
        if (!$this->is_active) {
            return 'غير مفعل';
        }

        $today = Carbon::today();
        if ($this->start_date > $today) {
            return 'لم يبدأ بعد';
        } elseif ($this->end_date < $today) {
            return 'منتهي';
        } else {
            return 'نشط';
        }
    }
}
