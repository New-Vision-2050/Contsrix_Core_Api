<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\EcoAddress\Database\factories\EcoAddressFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Country\Models\City;
use Modules\Country\Models\Country;
use Modules\Country\Models\State;
use Modules\Ecommerce\EcoClient\Models\EcoClient;
use App\Traits\ForcedBelongsToTenant;
//use BasePackage\Shared\Traits\HasTranslations;

class EcoAddress extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use ForcedBelongsToTenant;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'eco_client_id',
        'first_name',
        'last_name',
        'email',
        'phone_code',
        'phone',
        'country_id',
        'city_id',
        'state_id',
        'address',
        'zip_code',
        'type',
        'is_default',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): EcoAddressFactory
    {
        return EcoAddressFactory::new();
    }
        public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(EcoClient::class, 'eco_client_id', 'id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id', 'id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }
}
