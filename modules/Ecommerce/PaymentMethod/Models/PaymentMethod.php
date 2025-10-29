<?php

declare(strict_types=1);

namespace Modules\Ecommerce\PaymentMethod\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\PaymentMethod\Database\factories\PaymentMethodFactory;
use BasePackage\Shared\Traits\BaseFilterable;
//use BasePackage\Shared\Traits\HasTranslations;

class PaymentMethod extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    //use HasTranslations;
    //use SoftDeletes;

    // public array $translatable = [''];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'type',
        'is_active'
    ];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): PaymentMethodFactory
    {
        return PaymentMethodFactory::new();
    }
}
