<?php

declare(strict_types=1);

namespace Modules\Shared\TimeZone\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Shared\TimeZone\Database\factories\TimeZoneFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Country\Models\Country;

//use BasePackage\Shared\Traits\HasTranslations;

class TimeZone extends Model
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
        'country_id',
        'zone_name',
        'gmt_offset',
        'gmt_offset_name',
        'abbreviation',
        'tz_name',
    ];

    protected $casts = [
        'id' => 'string',
    ];
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    protected static function newFactory(): TimeZoneFactory
    {
        return TimeZoneFactory::new();
    }
}
