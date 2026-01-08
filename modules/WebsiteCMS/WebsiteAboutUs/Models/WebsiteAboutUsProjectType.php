<?php

namespace Modules\WebsiteCMS\WebsiteAboutUs\Models;

use BasePackage\Shared\Traits\HasTranslations;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteAboutUsProjectType extends Model
{
    use  UuidTrait, HasTranslations;

    protected $table = 'website_about_us_project_types';

    protected array $translatable = ['title'];

    protected $fillable = [
        'website_about_us_id',
        'title',
        'count',
    ];
    public $incrementing = false;

    protected $keyType = 'string';
    protected $casts = [
        'count' => 'integer',
        'id' => 'string',
    ];

    /**
     * Get the website about us that owns the project type.
     */
    public function websiteAboutUs(): BelongsTo
    {
        return $this->belongsTo(WebsiteAboutUs::class, 'website_about_us_id');
    }
}
