<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Enums;

enum ProjectRequirementEvaluationStatus: string
{
    case Approved = 'approved';
    case InProgress = 'in_progress';
    case Rejected = 'rejected';
    case PendingAcceptance = 'pending_acceptance';
    case UnderReview = 'under_review';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }

    public static function default(): string
    {
        return self::PendingAcceptance->value;
    }
}
