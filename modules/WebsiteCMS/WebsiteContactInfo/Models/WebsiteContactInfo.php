<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactInfo\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\WebsiteCMS\WebsiteContactInfo\Database\factories\WebsiteContactInfoFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Company\CompanyCore\Models\Company;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WebsiteContactInfo extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'email',
        'phone',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected static function newFactory(): WebsiteContactInfoFactory
    {
        return WebsiteContactInfoFactory::new();
    }
}
