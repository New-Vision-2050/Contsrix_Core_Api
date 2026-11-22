<?php

declare(strict_types=1);

namespace Modules\Forum\Enums;

enum ForumReportStatus: string
{
    case NEW = 'new';
    case IN_REVIEW = 'in_review';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
}
