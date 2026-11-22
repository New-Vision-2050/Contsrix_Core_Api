<?php

declare(strict_types=1);

namespace Modules\Forum\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Forum\Enums\ForumStatus;
use Modules\Forum\Enums\ForumVisibility;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Forum extends Model implements HasMedia
{
    use CustomBelongsToTenant;
    use InteractsWithMedia;
    use UuidTrait;

    public const LOGO_COLLECTION = 'logo';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'details',
        'visibility',
        'branch_ids',
        'project_ids',
        'status',
        'archived_at',
        'created_by',
    ];

    protected $casts = [
        'id' => 'string',
        'visibility' => ForumVisibility::class,
        'status' => ForumStatus::class,
        'branch_ids' => 'array',
        'project_ids' => 'array',
        'archived_at' => 'datetime',
    ];

    protected $attributes = [
        'visibility' => 'company',
        'status' => 'active',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::LOGO_COLLECTION)->singleFile();
    }

    public function admins(): HasMany
    {
        return $this->hasMany(ForumAdmin::class, 'forum_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ForumMember::class, 'forum_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(ForumGroup::class, 'forum_id');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(ForumAnnouncement::class, 'forum_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ForumReport::class, 'forum_id');
    }

    public function activity(): HasMany
    {
        return $this->hasMany(ForumActivityLog::class, 'forum_id');
    }
}
