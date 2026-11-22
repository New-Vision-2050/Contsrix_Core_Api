<?php

declare(strict_types=1);

namespace Modules\Forum\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Forum\Enums\ForumReportStatus;
use Modules\User\Models\User;

class ForumReport extends Model
{
    use CustomBelongsToTenant;
    use UuidTrait;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'forum_id',
        'group_id',
        'message_id',
        'reporter_id',
        'reason',
        'status',
        'resolution_note',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'id' => 'string',
        'status' => ForumReportStatus::class,
        'resolved_at' => 'datetime',
    ];

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'forum_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ForumGroup::class, 'group_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ForumMessage::class, 'message_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
