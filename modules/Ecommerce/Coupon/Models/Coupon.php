<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Models;

use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Ecommerce\Coupon\Database\Factories\CouponFactory;
use Modules\Ecommerce\Coupon\Filters\CouponFilter;
use Carbon\Carbon;
use App\Traits\ForcedBelongsToTenant;

class Coupon extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use ForcedBelongsToTenant;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'coupon_type',
        'title',
        'code',
        'customer_id',
        'max_usage_per_user',
        'discount_type',
        'discount_amount',
        'min_purchase',
        'max_discount',
        'start_date',
        'expire_date',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'customer_id' => 'string',
        'discount_amount' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'start_date' => 'date',
        'expire_date' => 'date',
        'is_active' => 'boolean',
        'max_usage_per_user' => 'integer',
    ];

    // Coupon Types Constants
    const TYPE_DISCOUNT_ON_PURCHASE = 'discount_on_purchase';
    const TYPE_FREE_DELIVERY = 'free_delivery';
    const TYPE_FIRST_ORDER = 'first_order';

    // Discount Types Constants
    const DISCOUNT_PERCENTAGE = 'percentage';
    const DISCOUNT_FIXED = 'fixed';

    protected static function newFactory(): CouponFactory
    {
        return CouponFactory::new();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $now = Carbon::now()->toDateString();
        return $query->where('start_date', '<=', $now)
                    ->where('expire_date', '>=', $now);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('coupon_type', $type);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    // Helper Methods
    public function isValid(): bool
    {
        $now = Carbon::now()->toDateString();
        return $this->is_active && 
               $this->start_date <= $now && 
               $this->expire_date >= $now;
    }

    public function isExpired(): bool
    {
        return Carbon::now()->toDateString() > $this->expire_date;
    }

    public function isDiscountType(): bool
    {
        return $this->coupon_type === self::TYPE_DISCOUNT_ON_PURCHASE;
    }

    public function isFreeDeliveryType(): bool
    {
        return $this->coupon_type === self::TYPE_FREE_DELIVERY;
    }

    public function isFirstOrderType(): bool
    {
        return $this->coupon_type === self::TYPE_FIRST_ORDER;
    }

    public function getDiscountAmountForOrder(float $orderTotal): float
    {
        if (!$this->isDiscountType()) {
            return 0;
        }

        if ($orderTotal < $this->min_purchase) {
            return 0;
        }

        if ($this->discount_type === self::DISCOUNT_PERCENTAGE) {
            $discount = ($orderTotal * $this->discount_amount) / 100;
            return $this->max_discount > 0 ? min($discount, $this->max_discount) : $discount;
        }

        return $this->discount_amount;
    }
}
