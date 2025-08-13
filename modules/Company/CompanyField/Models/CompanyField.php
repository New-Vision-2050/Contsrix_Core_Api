<?php

declare(strict_types=1);

namespace Modules\Company\CompanyField\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\CompanyField\Database\factories\CompanyFieldFactory;
use BasePackage\Shared\Traits\BaseFilterable;
//use BasePackage\Shared\Traits\HasTranslations;

class CompanyField extends Model
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
        'name',
        'description'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): CompanyFieldFactory
    {
        return CompanyFieldFactory::new();
    }
}
