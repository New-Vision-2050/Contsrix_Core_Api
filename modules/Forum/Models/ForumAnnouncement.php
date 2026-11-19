<?php

declare(strict_types=1);

namespace Modules\Forum\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\User\Models\User;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ForumAnnouncement extends Model implements HasMedia
{
    use CustomBelongsToTenant;
    use InteractsWithMedia;
    use UuidTrait;

    public const ATTACHMENT_COLLECTION = 'attachments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'title',
        'body',
        'created_by',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(ForumAnnouncementRead::class, 'announcement_id');
    }
}
