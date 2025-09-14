<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Ecommerce\EcoOrder\Models\EcoOrder;
use Ramsey\Uuid\Uuid;
use BasePackage\Shared\Traits\BaseFilterable;

class EcoDiscount extends Model
{
    use SoftDeletes, BaseFilterable;

    protected $table = 'eco_discounts';

    protected $fillable = [
        'id',
        'name',
        'description',
        'code',
        'type', // 'percentage', 'fixed_amount', 'buy_x_get_y'
        'value', // percentage or fixed amount
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'used_count',
        'start_date',
        'end_date',
        'is_active',
        'applies_to', // 'all_products', 'specific_products', 'categories'
        'created_by',
        'type_discount',//code,order,time,package
        'priority', // 'basic', 'premium', 'vip'
    ];

    protected $casts = [
        'id' => 'string',
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Uuid::uuid4()->toString();
            }
        });
    }

    /**
     * Get the products that this discount applies to
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            EcoProduct::class,
            'eco_discount_products',
            'eco_discount_id',
            'eco_product_id'
        );
    }

    /**
     * Get the orders that used this discount
     */
    public function orders(): HasMany
    {
        return $this->hasMany(EcoOrder::class, 'discount_id');
    }

    /**
     * Check if discount is currently valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if discount can be applied to given amount
     */
    public function canApplyToAmount(float $amount): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->min_order_amount && $amount < $this->min_order_amount) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount amount for given order amount
     */
    public function calculateDiscountAmount(float $orderAmount): float
    {
        if (!$this->canApplyToAmount($orderAmount)) {
            return 0;
        }

        $discountAmount = 0;

        switch ($this->type) {
            case 'percentage':
                $discountAmount = ($orderAmount * $this->value) / 100;
                break;
            case 'fixed_amount':
                $discountAmount = $this->value;
                break;
        }

        // Apply maximum discount limit if set
        if ($this->max_discount_amount && $discountAmount > $this->max_discount_amount) {
            $discountAmount = $this->max_discount_amount;
        }

        // Ensure discount doesn't exceed order amount
        if ($discountAmount > $orderAmount) {
            $discountAmount = $orderAmount;
        }

        return round($discountAmount, 2);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    /**
     * Scope for active discounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for valid discounts (active and within date range)
     */
    public function scopeValid($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $now);
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                  ->orWhereRaw('used_count < usage_limit');
            });
    }
}
