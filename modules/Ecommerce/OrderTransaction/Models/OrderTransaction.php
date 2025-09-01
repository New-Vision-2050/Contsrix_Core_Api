<?php

declare(strict_types=1);

namespace Modules\Ecommerce\OrderTransaction\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\OrderTransaction\Database\factories\OrderTransactionFactory;
use BasePackage\Shared\Traits\BaseFilterable;
//use BasePackage\Shared\Traits\HasTranslations;

class OrderTransaction extends Model
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
        'company_id',
        'order_id',
        'order_amount',
        'admin_commission',
        'received_by',
        'status',
        'delivery_charge',
        'tax',
        'client_id',
        'delivered_by',
        'payment_method',
        'transaction_id',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): OrderTransactionFactory
    {
        return OrderTransactionFactory::new();
    }
}
