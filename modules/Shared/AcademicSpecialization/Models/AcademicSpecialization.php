<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Shared\AcademicSpecialization\Database\factories\AcademicSpecializationFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;

class AcademicSpecialization extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    //use SoftDeletes;

    public array $translatable = ['name'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'code',
        'academic_qualification_id',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): AcademicSpecializationFactory
    {
        return AcademicSpecializationFactory::new();
    }
}
