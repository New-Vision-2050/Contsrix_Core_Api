<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\Process\Models\ProcessStep;

/**
 * Fired when a workflow step becomes active (pending) and action takers need to be notified.
 *
 * The listener SendWorkflowStepNotification handles:
 * - Real-time broadcast (WebSocket)
 * - Push notification (FCM) (if notify_by_push)
 * - Email notification (if notify_by_email)
 * - SMS notification (if notify_by_sms)
 */
class WorkflowStepActivated
{
    use Dispatchable, SerializesModels;

    /**
     * @param array<string> $userIds  Resolved action taker user IDs
     * @param array<string, mixed> $context  Additional context (e.g., project_id)
     */
    public function __construct(
        public ProcessStep $processStep,
        public ProcedureSettingStep $templateStep,
        public array $userIds,
        public array $context = [],
    ) {}
}
