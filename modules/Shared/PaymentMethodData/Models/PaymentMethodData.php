<?php

declare(strict_types=1);

namespace Modules\Shared\PaymentMethodData\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Shared\PaymentMethodData\Database\factories\PaymentMethodDataFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;

class PaymentMethodData extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;

    public array $translatable = ['name'];
    protected $table = 'payment_method_data';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'type',
        'name',
        'is_active'
    ];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    protected static function newFactory(): PaymentMethodDataFactory
    {
        return PaymentMethodDataFactory::new();
    }
}
