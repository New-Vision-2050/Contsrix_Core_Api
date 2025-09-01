<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrderDetail\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\EcoOrderDetail\Database\factories\EcoOrderDetailFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Ecommerce\EcoOrder\Models\EcoOrder;

//use BasePackage\Shared\Traits\HasTranslations;

class EcoOrderDetail extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'eco_order_id',
        'eco_product_id',
        'shipping_method_id',
        'warehouse_id',
        'digital_file_after_sell',
        'product_details',
        'qty',
        'price',
        'tax',
        'discount',
        'tax_model',
        'delivery_status',
        'payment_status',
        'variant',
        'variation',
        'discount_type',
        'is_stock_decreased',
        'refund_request'
    ];

    protected $casts = [
        'id' => 'string',
        'product_details' => 'array'
    ];

    protected static function newFactory(): EcoOrderDetailFactory
    {
        return EcoOrderDetailFactory::new();
    }
    public function order()
    {
        return $this->belongsTo(EcoOrder::class, 'eco_order_id');
    }
}
