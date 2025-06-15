<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserEducationalCourse\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserInfo\UserEducationalCourse\Database\factories\UserEducationalCourseFactory;
use BasePackage\Shared\Traits\BaseFilterable;
<<<<<<< HEAD
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

//use BasePackage\Shared\Traits\HasTranslations;

class UserEducationalCourse extends Model implements HasMedia
=======
//use BasePackage\Shared\Traits\HasTranslations;

class UserEducationalCourse extends Model
>>>>>>> 7be6c72c (merge with stage (first version ))
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
<<<<<<< HEAD
    use InteractsWithMedia;
=======
>>>>>>> 7be6c72c (merge with stage (first version ))
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'global_id',
        'company_name',
        'authority',
        'name',
        'institute',
        'certificate',
        'date_obtain',
        'date_end',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): UserEducationalCourseFactory
    {
        return UserEducationalCourseFactory::new();
    }
}
