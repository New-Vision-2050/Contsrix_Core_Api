<?php

declare(strict_types=1);

namespace Modules\Forum\Enums;

enum ForumMessageType: string
{
    case TEXT = 'text';
    case IMAGE = 'image';
    case VOICE = 'voice';
    case FILE = 'file';
}
