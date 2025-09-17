<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\EcoBrand\Database\factories\EcoBrandFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;

class EcoBrand extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    //use SoftDeletes;

    public array $translatable = ['name', 'description'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'description',
        'company_id',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): EcoBrandFactory
    {
        return EcoBrandFactory::new();
    }
}
