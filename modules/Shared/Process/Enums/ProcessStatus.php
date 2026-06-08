<?php

declare(strict_types=1);

namespace Modules\Shared\Process\Enums;
enum ProcessStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Failed = 'failed';
}
