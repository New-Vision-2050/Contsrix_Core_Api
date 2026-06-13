<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Enums;

enum ActionTakerManagementHierarchyType: string
{
    case BranchManager = 'branch_manager';
    case ManagementManager = 'management_manager';
    case ProjectManager = 'project_manager';
}
