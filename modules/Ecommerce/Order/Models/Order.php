<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Ecommerce\Order\Database\factories\OrderFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Ecommerce\Warehous\Models\Warehous;
use Modules\User\Models\User;

//use BasePackage\Shared\Traits\HasTranslations;

class Order extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'order_serial',
        'order_number',
        'customer_id',
        'is_guest',
        'customer_type',
        'payment_status',
        'order_status',
        'payment_method',
        'transaction_ref',
        'payment_by',
        'payment_note',
        'order_amount',
        'paid_amount',
        'bring_change_amount',
        'bring_change_amount_currency',
        'is_pause',
        'cause',
        'shipping_address',
        'discount_amount',
        'discount_type',
        'coupon_code',
        'coupon_discount_bearer',
        'shipping_responsibility',
        'shipping_method_id',
        'shipping_cost',
        'is_shipping_free',
        'order_group_id',
        'verification_code',
        'verification_status',
        'shipping_address_data',
        'delivery_man_id',
        'deliveryman_charge',
        'expected_delivery_date',
        'order_note',
        'billing_address',
        'billing_address_data',
        'order_type',
        'extra_discount',
        'extra_discount_type',
        'refer_and_earn_discount',
        'free_delivery_bearer',
        'checked',
        'shipping_type',
        'delivery_type',
        'delivery_service_name',
        'third_party_delivery_tracking_id',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'warehouse_id' => 'string',
        'order_number' => 'integer',
        'customer_id' => 'string',
        'is_guest' => 'boolean',
        'order_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'bring_change_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'is_shipping_free' => 'boolean',
        'verification_status' => 'boolean',
        'deliveryman_charge' => 'decimal:2',
        'expected_delivery_date' => 'date',
        'extra_discount' => 'decimal:2',
        'refer_and_earn_discount' => 'decimal:2',
        'checked' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehous::class, 'warehouse_id');
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(OrderTransaction::class);
    }

    // Accessors
    public function getOrderDateAttribute(): string
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    public function getCustomerInfoAttribute(): array
    {
        if ($this->is_guest) {
            return [
                'type' => 'guest',
                'name' => 'عميل زائر',
                'email' => null,
                'phone' => null,
            ];
        }

        return [
            'type' => 'registered',
            'name' => $this->customer?->name ?? 'غير محدد',
            'email' => $this->customer?->email ?? null,
            'phone' => $this->customer?->phone ?? null,
        ];
    }

    public function getTotalPriceAttribute(): float
    {
        return (float) $this->order_amount;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->order_status) {
            'pending' => 'في الانتظار',
            'confirmed' => 'مؤكد',
            'processing' => 'قيد المعالجة',
            'out_for_delivery' => 'خرج للتوصيل',
            'delivered' => 'تم التوصيل',
            'returned' => 'مرتجع',
            'failed' => 'فشل',
            'canceled' => 'ملغي',
            default => $this->order_status,
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            'paid' => 'مدفوع',
            'unpaid' => 'غير مدفوع',
            'partially_paid' => 'مدفوع جزئياً',
            'refunded' => 'مسترد',
            default => $this->payment_status,
        };
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}
