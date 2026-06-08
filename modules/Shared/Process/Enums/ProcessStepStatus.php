<?php

declare(strict_types=1);

namespace Modules\Shared\Process\Enums;

enum ProcessStepStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
