<?php

declare(strict_types=1);

namespace Modules\Company\BusinessType\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\BusinessType\Database\factories\BusinessTypeFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;

class BusinessType extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    //use SoftDeletes;

    public array $translatable = ['description','name'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): BusinessTypeFactory
    {
        return BusinessTypeFactory::new();
    }
}
