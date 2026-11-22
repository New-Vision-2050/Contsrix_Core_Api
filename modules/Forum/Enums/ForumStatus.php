<?php

declare(strict_types=1);

namespace Modules\Forum\Enums;

enum ForumStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
}
