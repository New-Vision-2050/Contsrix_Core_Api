<?php

declare(strict_types=1);

namespace Modules\Shared\SocialIcon\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Shared\SocialIcon\Database\factories\SocialIconFactory;
use BasePackage\Shared\Traits\BaseFilterable;
//use BasePackage\Shared\Traits\HasTranslations;

class SocialIcon extends Model
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
        'web_icon',
        'mobile_icon',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): SocialIconFactory
    {
        return SocialIconFactory::new();
    }
}
