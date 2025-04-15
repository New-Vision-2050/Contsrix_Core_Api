<?php

declare(strict_types=1);

namespace Modules\UserInfo\Qualification\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserInfo\Qualification\Database\factories\QualificationFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Country\Models\Country;
use Modules\Shared\AcademicQualification\Models\AcademicQualification;
use Modules\Shared\AcademicSpecialization\Models\AcademicSpecialization;
use Modules\Shared\University\Models\University;
//use BasePackage\Shared\Traits\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
class Qualification extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    //use HasTranslations;
    //use SoftDeletes;
    use InteractsWithMedia;
    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'global_id',
        'country_id',
        'university_id',
        'academic_qualification_id',
        'academic_specialization_id',
        'study_rate',
        'graduation_date',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): QualificationFactory
    {
        return QualificationFactory::new();
    }
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $media->getFullUrl();
    }
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    public function university()
    {
        return $this->belongsTo(University::class);
    }
    public function academicQualification()
    {
        return $this->belongsTo(AcademicQualification::class);
    }
    public function academicSpecialization()
    {
        return $this->belongsTo(AcademicSpecialization::class);
    }

}
