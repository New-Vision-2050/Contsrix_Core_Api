<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserAbout\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserInfo\UserAbout\Database\factories\UserAboutFactory;
use BasePackage\Shared\Traits\BaseFilterable;
//use BasePackage\Shared\Traits\HasTranslations;

class UserAbout extends Model
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
        'global_id',
        'about_me'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): UserAboutFactory
    {
        return UserAboutFactory::new();
    }
}
