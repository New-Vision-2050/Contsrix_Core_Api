<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

/**
 * Single normaliser for early-clock-in rules.
 *
 * BUG-1: the rules API historically wrote `['allowed_minutes_before' => N]` while every
 * runtime consumer read `early_period` + `early_unit` — so constraints configured through
 * PATCH /rules silently had no early window. Every read and every write must go through
 * this class so the two shapes can never drift again.
 */
final class EarlyClockInRules
{
    /**
     * Minutes before shift start that clock-in is allowed, read from any historical shape.
     *
     * @param array<string, mixed>|null $rules
     */
    public static function minutes(?array $rules): int
    {
        if (!is_array($rules) || $rules === []) {
            return 0;
        }

        $value = $rules['early_period'] ?? $rules['allowed_minutes_before'] ?? 0;
        if (!is_numeric($value) || (float) $value <= 0) {
            return 0;
        }

        $unit = strtolower((string) ($rules['early_unit'] ?? 'minutes'));

        return match ($unit) {
            'hour', 'hours'   => (int) round((float) $value * 60),
            'day', 'days'     => (int) round((float) $value * 1440),
            default           => (int) round((float) $value),
        };
    }

    /**
     * Canonical write shape. Emits every key variant so legacy readers
     * (`early_period`/`early_unit`) and API readers (`allowed_minutes_before`) agree.
     *
     * @return array{allowed_minutes_before: int, early_period: int, early_unit: string, prevent_early_clock_in: bool}|null
     */
    public static function toStorage(?int $minutes, bool $preventEarly = false): ?array
    {
        if ($minutes === null) {
            return null;
        }

        $minutes = max(0, $minutes);

        return [
            'allowed_minutes_before' => $minutes,
            'early_period'           => $minutes,
            'early_unit'             => 'minutes',
            'prevent_early_clock_in' => $preventEarly,
        ];
    }
}
