<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

class AttachmentRequestHistory extends Model
{
    use HasUuids;

    protected $table = 'attachment_request_history';

    public $timestamps = false;

    protected $fillable = [
        'attachment_request_id',
        'attachment_request_item_id',
        'action',
        'description',
        'user_id',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(AttachmentRequest::class, 'attachment_request_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(AttachmentRequestItem::class, 'attachment_request_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScopes();
    }

    /**
     * Static method to log history
     */
    public static function log(
        string $requestId,
        string $action,
        string $description,
        ?string $userId = null,
        ?string $itemId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'attachment_request_id' => $requestId,
            'attachment_request_item_id' => $itemId,
            'action' => $action,
            'description' => $description,
            'user_id' => $userId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
