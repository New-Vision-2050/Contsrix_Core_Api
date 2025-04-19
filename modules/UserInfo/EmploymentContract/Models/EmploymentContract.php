<?php

declare(strict_types=1);

namespace Modules\UserInfo\EmploymentContract\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserInfo\EmploymentContract\Database\factories\EmploymentContractFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Country\Models\Country;
//use BasePackage\Shared\Traits\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class EmploymentContract extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use InteractsWithMedia;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'global_id',

        'contract_number',
        'start_date',
        'commencement_date',
        'contract_duration',

        'notice_period',
        'probation_period',
        'nature_work',
        'type_working_hours',

        'working_hours',
        'annual_leave',
        'country_id',
        'right_terminate',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): EmploymentContractFactory
    {
        return EmploymentContractFactory::new();
    }
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $media->getFullUrl();
    }
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
