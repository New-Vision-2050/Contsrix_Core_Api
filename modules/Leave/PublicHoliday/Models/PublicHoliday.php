<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Leave\PublicHoliday\Database\factories\PublicHolidayFactory;
use BasePackage\Shared\Traits\BaseFilterable;

//use BasePackage\Shared\Traits\HasTranslations;

class PublicHoliday extends Model
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
        'country_id',
        'date_start',
        'date_end',
    ];

    protected $casts = [
        'id' => 'string',
        'country_id' => 'string',
        'date_start' => 'date',
        'date_end' => 'date',
    ];

    /**
     * Get the country that owns the public holiday.
     */
    public function country()
    {
        return $this->belongsTo(\Modules\Country\Models\Country::class, 'country_id');
    }

    protected static function newFactory(): PublicHolidayFactory
    {
        return PublicHolidayFactory::new();
    }
}
