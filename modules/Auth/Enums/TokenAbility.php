<?php

declare(strict_types=1);

namespace Modules\Auth\Enums;

enum TokenAbility: string
{
    case ACCESS_API = 'access-api';
    case ISSUE_ACCESS_TOKEN = 'issue-access-token';
}
