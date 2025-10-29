<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use BasePackage\Shared\Traits\UuidTrait;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class OrderDetail extends Model
{
    use HasFactory;
    use UuidTrait;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'order_id',
        'product_id',
        'digital_file_after_sell',
        'product_details',
        'qty',
        'price',
        'tax',
        'discount',
        'tax_model',
        'delivery_status',
        'payment_status',
        'shipping_method_id',
        'variant',
        'variation',
        'discount_type',
        'is_stock_decreased',
        'refund_request',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'order_id' => 'string',
        'product_id' => 'string',
        'shipping_method_id' => 'string',
        'qty' => 'integer',
        'price' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'product_details' => 'array',
        'is_stock_decreased' => 'boolean',
        'refund_request' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(EcoProduct::class, 'product_id');
    }

    // Accessors
    public function getSubtotalAttribute(): float
    {
        return (float) ($this->qty * $this->price);
    }

    public function getFinalTotalAttribute(): float
    {
        return (float) ($this->subtotal - $this->discount + $this->tax);
    }

    public function getDiscountPercentageAttribute(): float
    {
        if ($this->subtotal > 0) {
            return ($this->discount / $this->subtotal) * 100;
        }
        return 0;
    }

    // Helper accessors for backward compatibility
    public function getQuantityAttribute(): int
    {
        return $this->qty;
    }

    public function getUnitPriceAttribute(): float
    {
        return (float) $this->price;
    }

    public function getDiscountAmountAttribute(): float
    {
        return (float) $this->discount;
    }

    public function getTaxAmountAttribute(): float
    {
        return (float) $this->tax;
    }

    public function getTotalPriceAttribute(): float
    {
        return $this->final_total;
    }
}
