<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Process\Models\Process;
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
        'sort_order',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sort_order' => 'integer',
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
        ?\DateTimeInterface $createdAt = null,
        ?int $sortOrder = null
    ): self {
        $sortOrder ??= self::defaultSortOrder($requestId, $action, $metadata);

        $attributes = [
            'attachment_request_id' => $requestId,
            'attachment_request_item_id' => $itemId,
            'action' => $action,
            'description' => $description,
            'user_id' => $userId,
            'metadata' => $metadata,
            'created_at' => $createdAt ?? now(),
            'sort_order' => $sortOrder,
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
        Process $process,
        ProcessStep $step
    ): self {
        return self::linkWorkflowStepPending($requestId, $process, $step);
    }

    public static function recordWorkflowTimeline(string $requestId, Process $process): void
    {
        foreach ($process->template_snapshot ?? [] as $stepConfig) {
            $dedupeKey = self::workflowStepDedupeKeyFromSnapshot($requestId, $process, $stepConfig);
            if ($dedupeKey === null) {
                continue;
            }

            $existing = self::query()->where('dedupe_key', $dedupeKey)->first();
            if ($existing !== null) {
                continue;
            }

            self::create([
                'attachment_request_id' => $requestId,
                'action' => 'workflow_step_pending',
                'description' => 'Workflow step pending',
                'user_id' => null,
                'metadata' => self::workflowStepSnapshotMetadata($process, $stepConfig, 'pending'),
                'dedupe_key' => $dedupeKey,
                'sort_order' => self::workflowStepSortOrder($process, $stepConfig['template_step_order'] ?? null),
                'created_at' => $process->created_at ?? now(),
            ]);
        }
    }

    public static function linkWorkflowStepPending(
        string $requestId,
        Process $process,
        ProcessStep $step
    ): self {
        $dedupeKey = self::workflowStepDedupeKeyFromStep($requestId, $process, $step);
        $existing = self::findWorkflowStepLifecycle($requestId, $process, $step);

        if ($existing !== null) {
            $metadata = array_merge(
                self::workflowStepMetadata($process, $step, (string) ($existing->metadata['status'] ?? 'pending')),
                $existing->metadata ?? [],
                ['process_step_id' => $step->id]
            );

            $existing->forceFill([
                'metadata' => $metadata,
                'sort_order' => $existing->sort_order ?? self::workflowStepSortOrder($process, $step->template_step_order),
            ])->save();

            return $existing;
        }

        return self::create([
            'attachment_request_id' => $requestId,
            'action' => 'workflow_step_pending',
            'description' => 'Workflow step pending',
            'user_id' => null,
            'metadata' => self::workflowStepMetadata($process, $step, 'pending'),
            'dedupe_key' => $dedupeKey,
            'sort_order' => self::workflowStepSortOrder($process, $step->template_step_order),
            'created_at' => $step->created_at ?? now(),
        ]);
    }

    public static function transitionWorkflowStep(
        string $requestId,
        Process $process,
        ProcessStep $step,
        string $action,
        string $description,
        ?string $userId
    ): self {
        $status = $action === 'workflow_step_rejected' ? 'rejected' : 'approved';
        $dedupeKey = self::workflowStepDedupeKeyFromStep($requestId, $process, $step);
        $history = self::findWorkflowStepLifecycle($requestId, $process, $step);

        if ($history === null) {
            return self::create([
                'attachment_request_id' => $requestId,
                'action' => $action,
                'description' => $description,
                'user_id' => $userId,
                'metadata' => self::workflowStepMetadata($process, $step, $status),
                'dedupe_key' => $dedupeKey,
                'sort_order' => self::workflowStepSortOrder($process, $step->template_step_order),
                'created_at' => $step->created_at ?? $step->acted_at ?? now(),
            ]);
        }

        $metadata = array_merge(
            $history->metadata ?? [],
            self::workflowStepMetadata($process, $step, $status)
        );

        $history->forceFill([
            'action' => $action,
            'description' => $description,
            'user_id' => $userId,
            'metadata' => $metadata,
            'sort_order' => $history->sort_order ?? self::workflowStepSortOrder($process, $step->template_step_order),
        ])->save();

        return $history;
    }

    public static function deleteWorkflowStepLifecycle(
        string $requestId,
        Process $process,
        ProcessStep $step
    ): void {
        self::findWorkflowStepLifecycle($requestId, $process, $step)?->delete();
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
                $stepId = $metadata['step_id'] ?? null;
                $templateStepOrder = $metadata['template_step_order'] ?? null;

                if ($processId === null || $stepId === null || $templateStepOrder === null) {
                    return null;
                }

                return self::workflowStepDedupeKey($requestId, (string) $processId, (string) $stepId, (string) $templateStepOrder);
            }

            $stepId = $metadata['step_id'] ?? null;
            $templateStepOrder = $metadata['template_step_order'] ?? null;

            if ($stepId !== null && $templateStepOrder !== null) {
                return self::workflowStepDedupeKey($requestId, (string) $processId, (string) $stepId, (string) $templateStepOrder);
            }

            return self::legacyWorkflowStepDedupeKey($requestId, (string) $processId, (string) $processStepId);
        }

        return null;
    }

    private static function workflowStepDedupeKey(string $requestId, string $processId, string $stepId, string $templateStepOrder): string
    {
        return hash('sha256', implode('|', [
            $requestId,
            'workflow_step',
            $processId,
            $stepId,
            $templateStepOrder,
        ]));
    }

    private static function legacyWorkflowStepDedupeKey(string $requestId, string $processId, string $processStepId): string
    {
        return hash('sha256', implode('|', [
            $requestId,
            'workflow_step',
            $processId,
            $processStepId,
        ]));
    }

    private static function workflowStepDedupeKeyFromStep(string $requestId, Process $process, ProcessStep $step): string
    {
        return self::workflowStepDedupeKey(
            $requestId,
            (string) $process->id,
            (string) $step->step_id,
            (string) $step->template_step_order
        );
    }

    private static function workflowStepDedupeKeyFromSnapshot(string $requestId, Process $process, array $stepConfig): ?string
    {
        $stepId = $stepConfig['step_id'] ?? null;
        $templateStepOrder = $stepConfig['template_step_order'] ?? null;

        if ($stepId === null || $templateStepOrder === null) {
            return null;
        }

        return self::workflowStepDedupeKey($requestId, (string) $process->id, (string) $stepId, (string) $templateStepOrder);
    }

    private static function findWorkflowStepLifecycle(string $requestId, Process $process, ProcessStep $step): ?self
    {
        $dedupeKey = self::workflowStepDedupeKeyFromStep($requestId, $process, $step);

        $history = self::query()->where('dedupe_key', $dedupeKey)->first();
        if ($history !== null) {
            return $history;
        }

        return self::query()
            ->where('attachment_request_id', $requestId)
            ->where('metadata->process_id', (string) $process->id)
            ->where('metadata->process_step_id', (string) $step->id)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private static function workflowStepSnapshotMetadata(Process $process, array $stepConfig, string $status): array
    {
        return [
            'process_id' => $process->id,
            'process_sort_order' => $process->sort_order,
            'process_step_id' => null,
            'step_id' => $stepConfig['step_id'] ?? null,
            'template_step_order' => $stepConfig['template_step_order'] ?? null,
            'assigned_user_id' => $stepConfig['assigned_user_id'] ?? null,
            'authorized_user_ids' => $stepConfig['authorized_user_ids'] ?? null,
            'status' => $status,
            'acted_at' => null,
            'is_auto_approved' => false,
        ];
    }

    private static function workflowStepMetadata(Process $process, ProcessStep $step, string $status): array
    {
        return [
            'process_id' => $process->id,
            'process_sort_order' => $process->sort_order,
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

    private static function workflowStepSortOrder(Process $process, mixed $templateStepOrder): int
    {
        return 100000 + ((int) ($process->sort_order ?? 0) * 1000) + (int) $templateStepOrder;
    }

    private static function defaultSortOrder(string $requestId, string $action, ?array $metadata): ?int
    {
        return match ($action) {
            'request_created' => 0,
            'request_approved', 'request_declined' => 900000000,
            'workflow_step_pending', 'workflow_step_approved', 'workflow_step_rejected' => isset($metadata['process_sort_order'], $metadata['template_step_order'])
                ? 100000 + ((int) $metadata['process_sort_order'] * 1000) + (int) $metadata['template_step_order']
                : null,
            default => self::nextSortOrder($requestId),
        };
    }

    private static function nextSortOrder(string $requestId): int
    {
        return ((int) self::query()
            ->where('attachment_request_id', $requestId)
            ->whereNotNull('sort_order')
            ->max('sort_order')) + 1;
    }
}
