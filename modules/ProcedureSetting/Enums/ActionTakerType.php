<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Enums;

enum ActionTakerType: string
{
    case SpecificUser = 'specific_user';
    case ManagementHierarchy = 'management_hierarchy';
    case SpecificProcedures = 'specific_procedures';

    /**
     * When the action taker is the request submitter (himself/نفسه).
     * Only the "approve" form is permitted for this type.
     */
    case Himself = 'himself';
}
