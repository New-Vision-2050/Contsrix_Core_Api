<?php

declare(strict_types=1);

namespace Modules\Reports\Enums;

final class ReportStatus
{
    public const PENDING    = 'pending';
    public const PROCESSING = 'processing';
    public const READY      = 'ready';
    public const FAILED     = 'failed';

    public static function all(): array
    {
        return [self::PENDING, self::PROCESSING, self::READY, self::FAILED];
    }
}
