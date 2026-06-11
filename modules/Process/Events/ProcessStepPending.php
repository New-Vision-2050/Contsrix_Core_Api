<?php
declare(strict_types=1);
namespace Modules\Process\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Process\Models\ProcessStep;

class ProcessStepPending
{
    use Dispatchable;

    public function __construct(
        public ProcessStep $processStep
    ) {}
}
