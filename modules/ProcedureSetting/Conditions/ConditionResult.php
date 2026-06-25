<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Conditions;

/**
 * Result of evaluating a single condition.
 *
 * - null  → condition not configured / not enforced (skip)
 * - instance → condition was evaluated; check `passed`
 */
final class ConditionResult
{
    /**
     * @param array<string, mixed> $context Extra values needed by the exception factory (e.g. maxHours, maxDays)
     */
    public function __construct(
        public readonly string  $key,
        public readonly string  $labelAr,
        public readonly bool    $passed,
        public readonly ?string $message = null,
        public readonly ?string $exception = null,
        public readonly array   $context = [],
    ) {}
}
