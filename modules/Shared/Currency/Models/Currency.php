<?php

declare(strict_types=1);

namespace Modules\Shared\Currency\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Country\Models\Country;
use Modules\Shared\Currency\Database\factories\CurrencyFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;

class Currency extends Model
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
        'short_name'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function country(){
        return $this->belongsTo(Country::class,'short_name','currency');
    }

    protected static function newFactory(): CurrencyFactory
    {
        return CurrencyFactory::new();
    }
}
