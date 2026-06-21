<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Listeners;

use Modules\ProcedureSetting\Events\WorkflowProcedureTaken;
use Modules\ProcedureSetting\Services\ProcedureWorkflowService;

/**
 * Persists a taken-procedure record whenever any module fires WorkflowProcedureTaken.
 * This is the single point where external-module events are converted into morph-table rows.
 */
final class RecordInternalProcedureTaken
{
    public function __construct(
        private readonly ProcedureWorkflowService $workflowService,
    ) {}

    public function handle(WorkflowProcedureTaken $event): void
    {
        $this->workflowService->markProcedureTaken(
            $event->processableType,
            $event->processableId,
            $event->procedureSettingId,
            $event->takenBy,
        );
    }
}
