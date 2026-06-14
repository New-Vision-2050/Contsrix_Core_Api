<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Enums;

enum InternalProcessCondition: string
{
    case AllowDuringShift    = 'allow_during_shift';
    case AllowOutsideShift   = 'allow_outside_shift';
    case AllowOnHolidays     = 'allow_on_holidays';
    case ApplyToAllBranches  = 'apply_to_all_branches';
    case MaxDurationHours    = 'max_duration_hours';

    public function valueType(): string
    {
        return match ($this) {
            self::MaxDurationHours => 'integer',
            default                => 'bool',
        };
    }

    /** @return array<string, list<string>> */
    public static function validationRules(): array
    {
        $rules = [];
        foreach (self::cases() as $condition) {
            $rules["settings.{$condition->value}"] = $condition->valueType() === 'integer'
                ? ['nullable', 'integer', 'min:1', 'max:24']
                : ['nullable', 'boolean'];
        }
        return $rules;
    }

    /** @return array<string, bool|int|null> */
    public static function defaultSettings(): array
    {
        return [
            self::AllowDuringShift->value    => true,
            self::AllowOutsideShift->value   => false,
            self::AllowOnHolidays->value     => false,
            self::ApplyToAllBranches->value => true,
            self::MaxDurationHours->value   => null,
        ];
    }
}
