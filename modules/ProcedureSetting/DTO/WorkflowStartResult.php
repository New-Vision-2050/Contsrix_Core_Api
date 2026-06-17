<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\DTO;

use Modules\Process\Models\Process;

final class WorkflowStartResult
{
    public function __construct(
        public readonly bool $autoApprove,
        public readonly ?Process $activeProcess,
    ) {}
}
