<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserExperience\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserInfo\UserExperience\Database\factories\UserExperienceFactory;
use BasePackage\Shared\Traits\BaseFilterable;
//use BasePackage\Shared\Traits\HasTranslations;

class UserExperience extends Model
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

        'job_name',
        'training_from',
        'training_to',
        'company_name',
        'about',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): UserExperienceFactory
    {
        return UserExperienceFactory::new();
    }
}
