<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Structured logging for construction work-order Excel imports.
 * Logging failures must never interrupt the import.
 */
final class WorkOrderExcelImportLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function info(string $event, array $context = []): void
    {
        self::write('info', $event, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function warning(string $event, array $context = []): void
    {
        self::write('warning', $event, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function error(string $event, array $context = [], ?Throwable $exception = null): void
    {
        if ($exception !== null) {
            $context['exception_class'] = $exception::class;
            $context['exception_message'] = $exception->getMessage();
            $context['exception_trace'] = $exception->getTraceAsString();
        }

        self::write('error', $event, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function write(string $level, string $event, array $context): void
    {
        try {
            $context['event'] = $event;
            $context['operation'] = $event;

            Log::channel(config('logging.default'))->{$level}(
                '[WorkOrderExcelImport] '.$event,
                $context,
            );
        } catch (Throwable) {
            // Never break the import because logging failed.
        }
    }
}
