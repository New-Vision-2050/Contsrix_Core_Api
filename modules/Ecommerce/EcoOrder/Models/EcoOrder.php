<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrder\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\EcoOrder\Database\factories\EcoOrderFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Ecommerce\EcoClient\Models\EcoClient;
use Modules\Ecommerce\EcoOrderDetail\Models\EcoOrderDetail;
use App\Traits\ForcedBelongsToTenant;
//use BasePackage\Shared\Traits\HasTranslations;

class EcoOrder extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use ForcedBelongsToTenant;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'eco_client_id',
        'is_guest',
        'client_type',
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
        'admin_commission',
        'is_pause',
        'cause',
        'shipping_address',
        'discount_type',
        'discount_amount',
        'coupon_code',
        'coupon_discount_bearer',
        'shipping_responsibility',
        'shipping_method_id',
        'shipping_cost',
        'is_shipping_free',
        'order_group_id',
        'verification_code',
        'verification_status',
        'seller_id',
        'seller_is',
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
        'eco_client_id' => 'string',
        'is_guest' => 'boolean',
        'customer_type' => 'string',
        'payment_status' => 'string',
        'order_status' => 'string',
        'payment_method' => 'string',
        'transaction_ref' => 'string',
        'payment_by' => 'string',
        'payment_note' => 'string',
        'order_amount' => 'double',
        'refer_and_earn_discount' => 'double',
        'paid_amount' => 'double',
        'bring_change_amount' => 'double',
        'bring_change_amount_currency' => 'string',
        'admin_commission' => 'decimal:2',
        'is_pause' => 'boolean',
        'cause' => 'string',
        'shipping_address' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'discount_amount' => 'double',
        'discount_type' => 'string',
        'coupon_code' => 'string',
        'coupon_discount_bearer' => 'string',
        'shipping_responsibility' => 'string',
        'shipping_method_id' => 'string',
        'shipping_cost' => 'double',
        'is_shipping_free' => 'boolean',
        'order_group_id' => 'string',
        'verification_code' => 'string',
        'verification_status' => 'boolean',
        'shipping_address_data' => 'object',
        'delivery_man_id' => 'string',
        'deliveryman_charge' => 'double',
        'order_note' => 'string',
        'billing_address' => 'string',
        'billing_address_data' => 'object',
        'order_type' => 'string',
        'extra_discount' => 'double',
        'extra_discount_type' => 'string',
        'free_delivery_bearer' => 'string',
        'checked' => 'boolean',
        'shipping_type' => 'string',
        'delivery_type' => 'string',
        'delivery_service_name' => 'string',
        'third_party_delivery_tracking_id' => 'string',
    ];

    protected static function newFactory(): EcoOrderFactory
    {
        return EcoOrderFactory::new();
    }

        public function details(): HasMany
    {
        return $this->hasMany(EcoOrderDetail::class)->orderBy('warehouse_id', 'ASC');
    }
    public function client(): BelongsTo
    {
        return $this->belongsTo(EcoClient::class, 'eco_client_id');
    }

    // public function shipping(): BelongsTo
    // {
    //     return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    // }

    // public function shippingAddress(): BelongsTo
    // {
    //     return $this->belongsTo(ShippingAddress::class, 'shipping_address');
    // }

    // public function billingAddress(): BelongsTo
    // {
    //     return $this->belongsTo(ShippingAddress::class, 'billing_address_id');
    // }

    // public function deliveryMan(): BelongsTo
    // {
    //     return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    // }

    /* delivery_man_review -> deliveryManReview */
    // public function deliveryManReview(): HasOne
    // {
    //     return $this->hasOne(Review::class, 'order_id')->whereNotNull('delivery_man_id');
    // }

    /* order_transaction -> orderTransaction */
    // public function orderTransaction(): HasOne
    // {
    //     return $this->hasOne(OrderTransaction::class, 'order_id');
    // }

    // public function coupon(): BelongsTo
    // {
    //     return $this->belongsTo(Coupon::class, 'coupon_code', 'code');
    // }

    /* order_status_history -> orderStatusHistory */
    // public function orderStatusHistory(): HasMany
    // {
    //     return $this->hasMany(OrderStatusHistory::class);
    // }

    /* order_details -> orderDetails */
    // public function orderDetails(): HasMany
    // {
    //     return $this->hasMany(OrderDetail::class, 'order_id');
    // }

    /* offline_payments -> offlinePayments */
    // public function offlinePayments(): BelongsTo
    // {
    //     return $this->belongsTo(OfflinePayments::class, 'id', 'order_id');
    // }

    /* verification_images -> verificationImages */
    // public function verificationImages(): HasMany
    // {
    //     return $this->hasMany(OrderDeliveryVerification::class, 'order_id');
    // }

}
