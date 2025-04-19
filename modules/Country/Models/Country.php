<?php

declare(strict_types=1);

namespace Modules\Country\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Country\Database\factories\CountryFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Setting\Models\Driver;

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
        return $query->where("status",1);
    }
}
