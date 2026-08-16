<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Modules\User\Models\User;

/**
 * Per-employee attendance mode stored on user_professional_datas.
 *
 * regular  — existing shift windows (start/end, early, extension, late).
 * flexible — clock in any time during the calendar day; locations still apply;
 *            auto clock-out when required working hours are completed.
 */
final class AttendanceType
{
    public const REGULAR = 'regular';

    public const FLEXIBLE = 'flexible';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [self::REGULAR, self::FLEXIBLE];
    }

    public static function normalize(mixed $value): string
    {
        $raw = strtolower(trim((string) $value));

        if (in_array($raw, ['flexible', 'flexable', 'flex'], true)) {
            return self::FLEXIBLE;
        }

        return self::REGULAR;
    }

    public static function isFlexible(mixed $value): bool
    {
        return self::normalize($value) === self::FLEXIBLE;
    }

    public static function forUser(?User $user): string
    {
        if (! $user) {
            return self::REGULAR;
        }

        $type = $user->userProfessionalData?->attendance_type
            ?? $user->professionalData?->attendance_type
            ?? null;

        return self::normalize($type);
    }

    public static function userIsFlexible(?User $user): bool
    {
        return self::forUser($user) === self::FLEXIBLE;
    }
}
