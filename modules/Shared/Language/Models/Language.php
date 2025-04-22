<?php

declare(strict_types=1);

namespace Modules\Shared\Language\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Shared\Language\Database\factories\LanguageFactory;
use BasePackage\Shared\Traits\BaseFilterable;
//use BasePackage\Shared\Traits\HasTranslations;

class Language extends Model
{
    use HasFactory;
//    use UuidTrait;
    use BaseFilterable;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        "id",
        'lang',
        'lang_ar',
        'native',
        'iso_code',
        'is_active',
        'is_rtl',
        'is_default',
        'status'
    ];



    protected static function newFactory(): LanguageFactory
    {
        return LanguageFactory::new();
    }
}
