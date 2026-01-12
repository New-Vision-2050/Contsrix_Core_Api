<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAddress\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WebsiteCMS\WebsiteAddress\Database\factories\WebsiteAddressFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Modules\Country\Models\City;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WebsiteAddress extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    use BelongsToTenant;

    public array $translatable = ['title'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'city_id',
        'company_id',
        'title',
        'address',
        'latitude',
        'longitude',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'city_id' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'status' => 'integer',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    protected static function newFactory(): WebsiteAddressFactory
    {
        return WebsiteAddressFactory::new();
    }
}
