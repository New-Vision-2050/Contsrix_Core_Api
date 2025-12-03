<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\HasTranslations;
use Modules\WebsiteCMS\WebsiteOurService\Enums\ServiceTypeEnum;
use Modules\WebsiteCMS\WebsiteService\Models\WebsiteService;

class WebsiteOurServiceDepartment extends Model
{
    use UuidTrait;
    use HasTranslations;

    protected $table = 'website_our_service_departments';

    public array $translatable = ['title', 'description'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'website_our_service_id',
        'title',
        'description',
        'type',
    ];

    protected $casts = [
        'id' => 'string',
        'website_our_service_id' => 'string',
        'type' => ServiceTypeEnum::class,
    ];

    public function websiteOurService()
    {
        return $this->belongsTo(WebsiteOurService::class, 'website_our_service_id');
    }

    public function websiteServices()
    {
        return $this->belongsToMany(
            WebsiteService::class,
            'website_our_service_department_website_service',
            'website_our_service_department_id',
            'website_service_id'
        )->withTimestamps();
    }
}
