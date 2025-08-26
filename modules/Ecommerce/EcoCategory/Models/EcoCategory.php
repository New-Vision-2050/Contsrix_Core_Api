<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\EcoCategory\Database\factories\EcoCategoryFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;

class EcoCategory extends Model
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
        'company_id',
        'name',
        'description',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): EcoCategoryFactory
    {
        return EcoCategoryFactory::new();
    }
}
