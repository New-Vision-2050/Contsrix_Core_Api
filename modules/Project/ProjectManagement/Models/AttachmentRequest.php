<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\Company\CompanyCore\Models\Company;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\Process\Models\Process;
use Modules\Process\Models\ProcessStep;
use Modules\User\Models\User;

class AttachmentRequest extends Model
{
    use UuidTrait;

    public const PROCESSABLE_TYPE = 'attachment_request';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SEMI_APPROVED = 'semi-approved';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DECLINED = 'declined';

    protected $table = 'attachment_requests';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'serial_number',
        'name',
        'date',
        'project_id',
        'procedure_setting_id',
        'sender_company_id',
        'status',
        'created_by_user_id',
        'responded_by_user_id',
        'responded_at',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'responded_at' => 'datetime',
        'procedure_setting_id' => 'string',
    ];

    /**
     * Get the project this request belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectManagement::class, 'project_id')->withoutGlobalScopes();
    }

    /**
     * Get the project procedure selected for this request
     */
    public function procedureSetting(): BelongsTo
    {
        return $this->belongsTo(ProcedureSetting::class, 'procedure_setting_id')->withoutGlobalScopes();
    }

    /**
     * Get the project-specific metadata for the selected procedure.
     */
    public function projectProcedureSetting(): HasOne
    {
        return $this->hasOne(ProjectProcedureSetting::class, 'procedure_setting_id', 'procedure_setting_id')
            ->withoutGlobalScopes();
    }

    public function getAttachmentTypeIdAttribute(): ?string
    {
        return $this->attachmentTypeId();
    }

    public function getAttachmentSubTypeIdAttribute(): ?string
    {
        return $this->attachmentSubTypeId();
    }

    public function getAttachmentSubSubTypeIdAttribute(): ?string
    {
        return $this->attachmentSubSubTypeId();
    }

    public function getAttachmentTypeAttribute(): ?Folder
    {
        return $this->projectProcedureSetting?->attachmentType;
    }

    public function getAttachmentSubTypeAttribute(): ?Folder
    {
        return $this->projectProcedureSetting?->attachmentSubType;
    }

    public function getAttachmentSubSubTypeAttribute(): ?Folder
    {
        return $this->projectProcedureSetting?->attachmentSubSubType;
    }

    public function attachmentTypeId(): ?string
    {
        return $this->projectProcedureSetting?->attachment_type_id;
    }

    public function attachmentSubTypeId(): ?string
    {
        return $this->projectProcedureSetting?->attachment_sub_type_id;
    }

    public function attachmentSubSubTypeId(): ?string
    {
        return $this->projectProcedureSetting?->attachment_sub_sub_type_id;
    }

    /**
     * Get the company that sent the request
     */
    public function senderCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'sender_company_id')->withoutGlobalScopes();
    }

    /**
     * Get the user who created the request
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id')->withoutGlobalScopes();
    }

    /**
     * Get the user who responded to the request
     */
    public function respondedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by_user_id')->withoutGlobalScopes();
    }

    /**
     * Get all attachment items for this request
     */
    public function items(): HasMany
    {
        return $this->hasMany(AttachmentRequestItem::class, 'attachment_request_id');
    }

    /**
     * Get all history entries for this request
     */
    public function history(): HasMany
    {
        return $this->hasMany(AttachmentRequestHistory::class, 'attachment_request_id')
            ->orderByRaw('sort_order is null')
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');
    }

    public function processes(): HasMany
    {
        return $this->hasMany(Process::class, 'processable_id')
            ->where('processable_type', self::PROCESSABLE_TYPE);
    }

    public function attachmentRequestProcess(): HasOne
    {
        return $this->hasOne(Process::class, 'processable_id')
            ->where('processable_type', self::PROCESSABLE_TYPE);
    }

    /**
     * Check if request is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if request is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if request is declined
     */
    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    /**
     * Check if request is semi-approved
     */
    public function isSemiApproved(): bool
    {
        return $this->status === self::STATUS_SEMI_APPROVED;
    }

    /**
     * Update request status based on items
     */
    public function updateStatusBasedOnItems(): void
    {
        $items = $this->items;

        if ($items->isEmpty()) {
            return;
        }

        $approvedCount = $items->where('status', 'approved')->count();
        $declinedCount = $items->where('status', 'declined')->count();
        $totalCount = $items->count();

        if ($approvedCount === $totalCount) {
            $this->update(['status' => self::STATUS_APPROVED]);
        } elseif ($declinedCount === $totalCount) {
            $this->update(['status' => self::STATUS_DECLINED]);
        } elseif ($approvedCount > 0 || $declinedCount > 0) {
            $this->update(['status' => self::STATUS_SEMI_APPROVED]);
        } else {
            $this->update(['status' => self::STATUS_PENDING]);
        }
    }

    /**
     * Approve entire request and all items
     */
    public function approveAll(?string $userId): bool
    {
        $this->items()->update([
            'status' => self::STATUS_APPROVED,
            'responded_by_user_id' => $userId,
            'responded_at' => now(),
        ]);

        return $this->update([
            'status' => self::STATUS_APPROVED,
            'responded_by_user_id' => $userId,
            'responded_at' => now(),
        ]);
    }

    /**
     * Called by ProcessWorkflowService when the last process step for this
     * request completes (whether via manual approve or the skipping_period
     * auto-approve job). Finalizes the request the same way a manual final
     * approval does, since there is no further step left to act on.
     */
    public function onAllProcessesCompleted(Process $process): void
    {
        \Log::info('AttachmentRequest::onAllProcessesCompleted called', [
            'attachment_request_id' => $this->id,
            'process_id' => $process->id,
        ]);

        try {
            app(\Modules\Project\ProjectManagement\Services\AttachmentRequestService::class)
                ->completeWorkflowApproval($this, null);
        } catch (\Throwable $e) {
            \Log::error('AttachmentRequest::onAllProcessesCompleted failed', [
                'attachment_request_id' => $this->id,
                'process_id' => $process->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function onWorkflowStepActionCompleted(
        Process $process,
        ProcessStep $step,
        string $action,
        ?string $userId
    ): void {
        if ($action === 'approve' && $userId === null) {
            AttachmentRequestHistory::deleteWorkflowStepLifecycle(
                requestId: $this->id,
                process: $process,
                step: $step
            );

            return;
        }

        AttachmentRequestHistory::transitionWorkflowStep(
            requestId: $this->id,
            process: $process,
            step: $step,
            action: $action === 'reject' ? 'workflow_step_rejected' : 'workflow_step_approved',
            description: $action === 'reject' ? 'Workflow step rejected' : 'Workflow step approved',
            userId: $userId
        );
    }

    public function onWorkflowTimelineInitialized(Process $process): void
    {
        AttachmentRequestHistory::recordWorkflowTimeline(
            requestId: $this->id,
            process: $process
        );
    }

    public function onWorkflowStepActivated(Process $process, ProcessStep $step): void
    {
        AttachmentRequestHistory::recordWorkflowStepPending(
            requestId: $this->id,
            process: $process,
            step: $step
        );
    }

    /**
     * Decline entire request and all items
     */
    public function declineAll(?string $userId): bool
    {
        $this->items()->update([
            'status' => self::STATUS_DECLINED,
            'responded_by_user_id' => $userId,
            'responded_at' => now(),
        ]);

        return $this->update([
            'status' => self::STATUS_DECLINED,
            'responded_by_user_id' => $userId,
            'responded_at' => now(),
        ]);
    }
}
