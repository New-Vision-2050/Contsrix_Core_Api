<?php

declare(strict_types=1);

namespace Modules\UserInfo\EmploymentContract\Models;

use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Country\Models\Country;
use Modules\Shared\NatureWork\Models\NatureWork;
use Modules\Shared\RightTerminate\Models\RightTerminate;
use Modules\Shared\TimeUnit\Models\TimeUnit;
use Modules\Shared\TypeWorkingHour\Models\TypeWorkingHour;
use Modules\UserInfo\EmploymentContract\Database\factories\EmploymentContractFactory;
// use BasePackage\Shared\Traits\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EmploymentContract extends Model implements HasMedia
{
    use BaseFilterable;
    use HasFactory;
    use InteractsWithMedia;
    use UuidTrait;
    // use HasTranslations;
    // use SoftDeletes;

    // public array $translatable = [];

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
        'nature_work_id', //
        'type_working_hour_id', //

        'working_hours',
        'annual_leave',
        'country_id',
        'latitude',
        'longitude',
        'right_terminate_id', //

        'contract_duration_unit',
        'notice_period_unit',
        'probation_period_unit',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): EmploymentContractFactory
    {
        return EmploymentContractFactory::new();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $media->getFullUrl();
    }
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function natureWork()
    {
        return $this->belongsTo(NatureWork::class);
    }

    public function typeWorkingHour()
    {
        return $this->belongsTo(TypeWorkingHour::class);
    }

    public function rightTerminate()
    {
        return $this->belongsTo(RightTerminate::class);
    }

    public function contractDurationUnit()
{
    return $this->belongsTo(TimeUnit::class, 'contract_duration_unit');
}

public function noticePeriodUnit()
{
    return $this->belongsTo(TimeUnit::class, 'notice_period_unit');
}

public function probationPeriodUnit()
{
    return $this->belongsTo(TimeUnit::class, 'probation_period_unit');
}
}
