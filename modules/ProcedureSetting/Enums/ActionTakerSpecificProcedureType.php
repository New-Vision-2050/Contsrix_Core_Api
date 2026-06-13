<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Enums;

enum ActionTakerSpecificProcedureType: string
{
    case Branch = 'branch';
    case Management = 'management';
    case JobTitle = 'job_title';
    case JobRole = 'job_role';
}
