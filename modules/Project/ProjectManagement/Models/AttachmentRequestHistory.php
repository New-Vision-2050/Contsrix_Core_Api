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
        'dedupe_key',
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
        ?array $metadata = null,
        ?\DateTimeInterface $createdAt = null
    ): self {
        $attributes = [
            'attachment_request_id' => $requestId,
            'attachment_request_item_id' => $itemId,
            'action' => $action,
            'description' => $description,
            'user_id' => $userId,
            'metadata' => $metadata,
            'created_at' => $createdAt ?? now(),
        ];

        $dedupeKey = self::dedupeKey($requestId, $action, $metadata);

        if ($dedupeKey === null) {
            return self::create($attributes);
        }

        return self::query()->createOrFirst(
            ['dedupe_key' => $dedupeKey],
            $attributes
        );
    }

    /**
     * Return the domain identity for history events that must be idempotent.
     */
    private static function dedupeKey(string $requestId, string $action, ?array $metadata): ?string
    {
        if (in_array($action, ['request_created', 'request_approved', 'request_declined'], true)) {
            return hash('sha256', implode('|', [$requestId, $action]));
        }

        if (in_array($action, ['workflow_step_approved', 'workflow_step_rejected'], true)) {
            $processId = $metadata['process_id'] ?? null;
            $processStepId = $metadata['process_step_id'] ?? null;
            $status = $metadata['status'] ?? null;

            if ($processId === null || $processStepId === null) {
                return null;
            }

            return hash('sha256', implode('|', [
                $requestId,
                $action,
                (string) $processId,
                (string) $processStepId,
                (string) $status,
            ]));
        }

        return null;
    }
}
