<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\ForcedBelongsToTenant;
class SettingPage extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use ForcedBelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'type',
        'title_header',
        'description_header',
        'title_footer',
        'description_footer',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }

    public function ecoBranches(): HasMany
    {
        return $this->hasMany(EcoBranch::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(Feature::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
