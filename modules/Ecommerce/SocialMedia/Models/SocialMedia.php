<?php

declare(strict_types=1);

namespace Modules\Ecommerce\SocialMedia\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\SocialMedia\Database\factories\SocialMediaFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Shared\SocialIcon\Models\SocialIcon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialMedia extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'social_icons_id',
        'url',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): SocialMediaFactory
    {
        return SocialMediaFactory::new();
    }

    public function socialIcon(): BelongsTo
    {
        return $this->belongsTo(SocialIcon::class, 'social_icons_id');
    }
}
