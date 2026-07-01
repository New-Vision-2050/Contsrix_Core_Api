<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\Process\Models\Process;

/**
 * Fired when a Process for a start/end lifecycle action on an employee task
 * reaches a terminal state (completed or failed), or when a lifecycle workflow
 * auto-approves (no Process created). The listener is responsible for executing
 * the actual business logic and updating any linked request record.
 *
 * When $process is null (auto-approve), $metadata and $formKey must be provided
 * so the listener can resolve the form and apply the business logic without a
 * persisted Process record.
 */
final class EmployeeTaskLifecycleProcessCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly EmployeeTaskRequest $task,
        public readonly ?Process $process = null,
        public readonly bool $approved = true,
        public readonly ?array $metadata = null,
        public readonly ?string $formKey = null,
    ) {}
}
