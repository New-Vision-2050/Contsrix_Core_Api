<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Enums;

enum ActionTakerManagementHierarchyType: string
{
    case BranchManager = 'branch_manager';
    case ManagementManager = 'management_manager';
    case ProjectManager = 'project_manager';

    /**
     * Deputy manager of the branch or management the submitter belongs to.
     * Resolved via management_hierarchy_deputy_managers table.
     */
    case DeputyManager = 'deputy_manager';
}
