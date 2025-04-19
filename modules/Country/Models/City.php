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

class City extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;

    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $table = 'cities';


    protected $keyType = 'string';

    protected $fillable = [

    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function state()
    {
        return $this->belongsTo(State::class,"state_id");

    }
    public function country()
    {
        return $this->belongsTo(Country::class , "country_id");
    }


}
