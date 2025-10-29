<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\ForcedBelongsToTenant;
class Feature extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use ForcedBelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'type',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
    ];


    // Relationships
    // Removed settingPage relationship

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
