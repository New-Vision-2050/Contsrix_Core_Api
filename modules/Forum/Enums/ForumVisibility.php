<?php

declare(strict_types=1);

namespace Modules\Forum\Enums;

enum ForumVisibility: string
{
    case COMPANY = 'company';
    case SCOPED = 'scoped';
}
