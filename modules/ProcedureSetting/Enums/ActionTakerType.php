<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Enums;

enum ActionTakerType: string
{
    case SpecificUser = 'specific_user';
    case ManagementHierarchy = 'management_hierarchy';
}
