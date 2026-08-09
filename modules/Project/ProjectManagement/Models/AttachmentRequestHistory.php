<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Process\Models\ProcessStep;
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

    public static function recordWorkflowStepPending(
        string $requestId,
        string $processId,
        ProcessStep $step
    ): self {
        $dedupeKey = self::workflowStepDedupeKey($requestId, $processId, (string) $step->id);
        $existing = self::query()->where('dedupe_key', $dedupeKey)->first();

        if ($existing !== null) {
            return $existing;
        }

        return self::create([
            'attachment_request_id' => $requestId,
            'action' => 'workflow_step_pending',
            'description' => 'Workflow step pending',
            'user_id' => null,
            'metadata' => self::workflowStepMetadata($processId, $step, 'pending'),
            'dedupe_key' => $dedupeKey,
            'created_at' => $step->created_at ?? now(),
        ]);
    }

    public static function transitionWorkflowStep(
        string $requestId,
        string $processId,
        ProcessStep $step,
        string $action,
        string $description,
        ?string $userId
    ): self {
        $status = $action === 'workflow_step_rejected' ? 'rejected' : 'approved';
        $dedupeKey = self::workflowStepDedupeKey($requestId, $processId, (string) $step->id);
        $history = self::query()->where('dedupe_key', $dedupeKey)->first();

        if ($history === null) {
            return self::create([
                'attachment_request_id' => $requestId,
                'action' => $action,
                'description' => $description,
                'user_id' => $userId,
                'metadata' => self::workflowStepMetadata($processId, $step, $status),
                'dedupe_key' => $dedupeKey,
                'created_at' => $step->created_at ?? $step->acted_at ?? now(),
            ]);
        }

        $metadata = array_merge(
            $history->metadata ?? [],
            self::workflowStepMetadata($processId, $step, $status)
        );

        $history->forceFill([
            'action' => $action,
            'description' => $description,
            'user_id' => $userId,
            'metadata' => $metadata,
        ])->save();

        return $history;
    }

    public static function deleteWorkflowStepLifecycle(
        string $requestId,
        string $processId,
        string $processStepId
    ): void {
        self::query()
            ->where('dedupe_key', self::workflowStepDedupeKey($requestId, $processId, $processStepId))
            ->delete();
    }

    /**
     * Return the domain identity for history events that must be idempotent.
     */
    private static function dedupeKey(string $requestId, string $action, ?array $metadata): ?string
    {
        if (in_array($action, ['request_created', 'request_approved', 'request_declined'], true)) {
            return hash('sha256', implode('|', [$requestId, $action]));
        }

        if (in_array($action, ['workflow_step_pending', 'workflow_step_approved', 'workflow_step_rejected'], true)) {
            $processId = $metadata['process_id'] ?? null;
            $processStepId = $metadata['process_step_id'] ?? null;

            if ($processId === null || $processStepId === null) {
                return null;
            }

            return self::workflowStepDedupeKey($requestId, (string) $processId, (string) $processStepId);
        }

        return null;
    }

    private static function workflowStepDedupeKey(string $requestId, string $processId, string $processStepId): string
    {
        return hash('sha256', implode('|', [
            $requestId,
            'workflow_step',
            $processId,
            $processStepId,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private static function workflowStepMetadata(string $processId, ProcessStep $step, string $status): array
    {
        return [
            'process_id' => $processId,
            'process_step_id' => $step->id,
            'step_id' => $step->step_id,
            'template_step_order' => $step->template_step_order,
            'assigned_user_id' => $step->assigned_user_id,
            'authorized_user_ids' => $step->authorized_user_ids,
            'status' => $status,
            'acted_at' => $step->acted_at?->toIso8601String(),
            'is_auto_approved' => false,
        ];
    }
}
