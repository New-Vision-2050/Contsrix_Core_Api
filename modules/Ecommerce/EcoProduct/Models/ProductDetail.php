<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDetail extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;

    protected $table = 'product_details';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'product_id',
        'label',
        'value'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(EcoProduct::class, 'product_id', 'id');
    }
}
