<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\Process\Models\Process;

/**
 * Fired when a Process for a start/end lifecycle action on an employee task
 * reaches a terminal state (completed or failed). The listener is responsible
 * for executing the actual start/end business logic and updating the linked
 * start/end request record.
 */
final class EmployeeTaskLifecycleProcessCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly EmployeeTaskRequest $task,
        public readonly Process $process,
        public readonly bool $approved,
    ) {}
}
