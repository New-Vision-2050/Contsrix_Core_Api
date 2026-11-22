<?php

declare(strict_types=1);

namespace Modules\Forum\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Forum\Enums\ForumSendPermission;
use Modules\Forum\Enums\ForumStatus;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ForumGroup extends Model implements HasMedia
{
    use CustomBelongsToTenant;
    use InteractsWithMedia;
    use UuidTrait;

    public const ICON_COLLECTION = 'icon';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'forum_id',
        'name',
        'description',
        'status',
        'category',
        'show_name_in_chat',
        'send_permission',
        'allow_images',
        'allow_voice',
        'allow_files',
        'created_by',
    ];

    protected $attributes = [
        'status' => 'active',
        'show_name_in_chat' => true,
    ];

    protected $casts = [
        'id' => 'string',
        'status' => ForumStatus::class,
        'show_name_in_chat' => 'boolean',
        'send_permission' => ForumSendPermission::class,
        'allow_images' => 'boolean',
        'allow_voice' => 'boolean',
        'allow_files' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::ICON_COLLECTION)->singleFile();
    }

    public function forum(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Forum::class, 'forum_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ForumGroupMember::class, 'group_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ForumMessage::class, 'group_id');
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(ForumMessage::class, 'group_id')->latestOfMany();
    }
}
