<?php

declare(strict_types=1);

namespace Modules\Country\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Country\Database\factories\CountryFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Setting\Models\Driver;
use Modules\Shared\TimeZone\Models\TimeZone;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;

//use BasePackage\Shared\Traits\HasTranslations;

class Country extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;

    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $table = 'countries';

    public $with = ['smsDriver'];

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'phonecode',
        'status',
        'sms_driver_id',
    ];

    protected $casts = [
        'id' => 'string',
        "timezones" => "array"
    ];

    public function smsDriver()
    {
        return $this->belongsTo(Driver::class, 'sms_driver_id');
    }

    protected static function newFactory(): CountryFactory
    {
        return CountryFactory::new();
    }

    public function scopeActive($query)
    {
        return $query->where("status", 1);
    }

    public function states()
    {
        return $this->hasMany(State::class);
    }

    public function timeZones()
    {
        return $this->hasMany(TimeZone::class);
    }

    public function companyAccessProgram()
    {
        return $this->belongsToMany(
            CompanyAccessProgram::class,
            'company_access_program_country',
            'country_id',
            'company_access_program_id'
        );
    }
}
