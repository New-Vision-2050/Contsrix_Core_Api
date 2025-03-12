<?php

declare(strict_types=1);

namespace Modules\Shared\TimeZone\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Shared\TimeZone\Database\factories\TimeZoneFactory;
use BasePackage\Shared\Traits\BaseFilterable;
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
        'time_zone',
        'country_id'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): TimeZoneFactory
    {
        return TimeZoneFactory::new();
    }
}
