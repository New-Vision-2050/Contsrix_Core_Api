<?php

declare(strict_types=1);

namespace Modules\Forum\Enums;

enum ForumMemberRole: string
{
    case ADMIN = 'admin';
    case MEMBER = 'member';
}
