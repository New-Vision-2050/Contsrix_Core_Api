<?php

declare(strict_types=1);

namespace Modules\Forum\Enums;

enum ForumSendPermission: string
{
    case ADMINS_ONLY = 'admins_only';
    case ALL_MEMBERS = 'all_members';
    case SPECIFIC_MEMBERS = 'specific_members';
}
