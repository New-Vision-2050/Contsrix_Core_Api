<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoLanguage\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\EcoLanguage\Database\factories\EcoLanguageFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Shared\Language\Models\Language;
use App\Traits\ForcedBelongsToTenant;

class EcoLanguage extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use ForcedBelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'language_id',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'language_id' => 'string',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): EcoLanguageFactory
    {
        return EcoLanguageFactory::new();
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id', 'id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
