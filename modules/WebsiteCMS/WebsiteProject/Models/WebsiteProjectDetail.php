<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Models;

use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WebsiteCMS\WebsiteService\Models\WebsiteService;

class WebsiteProjectDetail extends Model
{
    use HasFactory;
    use UuidTrait;
    use HasTranslations;

    public array $translatable = ['name'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'website_project_id',
        'website_service_id',
        'name',
    ];

    protected $casts = [
        'id' => 'string',
        'website_project_id' => 'string',
        'website_service_id' => 'string',
    ];

    // Relationships
    public function websiteProject()
    {
        return $this->belongsTo(WebsiteProject::class, 'website_project_id');
    }

    public function websiteService()
    {
        return $this->belongsTo(WebsiteService::class, 'website_service_id');
    }
}
