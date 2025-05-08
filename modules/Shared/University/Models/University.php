<?php

declare(strict_types=1);

namespace Modules\Shared\University\Models;

use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Country\Models\Country;
use Modules\Shared\University\Database\factories\UniversityFactory;
use BasePackage\Shared\Traits\HasTranslations;

class University extends Model
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
        'country_iso2',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    /**
     * Get the country that the university belongs to.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    protected static function newFactory(): UniversityFactory
    {
        return UniversityFactory::new();
    }
}
