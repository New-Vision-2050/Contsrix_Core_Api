<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WebsiteCMS\WebsiteOurService\Database\factories\WebsiteOurServiceFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WebsiteOurService extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    use BelongsToTenant;

    public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'title',
        'description',
        'company_id',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'status' => 'integer',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function departments()
    {
        return $this->hasMany(WebsiteOurServiceDepartment::class, 'website_our_service_id');
    }

    protected static function newFactory(): WebsiteOurServiceFactory
    {
        return WebsiteOurServiceFactory::new();
    }
}
