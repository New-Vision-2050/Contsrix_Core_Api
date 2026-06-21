<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Events;

/**
 * Fired by any module when an internal procedure is completed/approved
 * for a processable entity (task, client request, etc.).
 *
 * The ProcedureSetting module listens via RecordInternalProcedureTaken and
 * persists the taken status centrally in internal_procedure_takens.
 *
 * External modules fire this event instead of injecting ProcedureWorkflowService
 * directly — keeping module coupling at zero.
 */
final class WorkflowProcedureTaken
{
    public function __construct(
        public readonly string  $processableType,
        public readonly string  $processableId,
        public readonly string  $procedureSettingId,
        public readonly ?string $takenBy = null,
    ) {}
}
