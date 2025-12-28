<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProjectSetting\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WebsiteCMS\WebsiteProject\Models\WebsiteProject;
use Modules\WebsiteCMS\WebsiteProjectSetting\Database\factories\WebsiteProjectSettingFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WebsiteProjectSetting extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    use BelongsToTenant;

    //use SoftDeletes;

    protected array $translatable = ['name'];
    protected $withCount = ['websiteProjects'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'company_id',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'array',
    ];

    protected static function newFactory(): WebsiteProjectSettingFactory
    {
        return WebsiteProjectSettingFactory::new();
    }

    public function websiteProjects()
    {
        return $this->hasMany(WebsiteProject::class);
    }
}
