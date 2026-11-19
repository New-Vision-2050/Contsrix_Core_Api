<?php

declare(strict_types=1);

namespace Modules\Forum\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Forum\Enums\ForumMessageType;
use Modules\User\Models\User;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ForumMessage extends Model implements HasMedia
{
    use CustomBelongsToTenant;
    use InteractsWithMedia;
    use UuidTrait;

    public const ATTACHMENT_COLLECTION = 'attachments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'group_id',
        'sender_id',
        'type',
        'body',
        'duration_seconds',
    ];

    protected $casts = [
        'id' => 'string',
        'type' => ForumMessageType::class,
        'duration_seconds' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ForumGroup::class, 'group_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
