<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Support;

use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessConditionType;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;

final class InternalProcessTypePayload
{
    public const FORM_KEY = 'form';
    public const FORMS_KEY = 'forms';
    public const CONDITIONS_KEY = 'conditions';
    public const APPEARS_BEFORE_KEY = 'appears_before_id';
    public const APPEARS_AFTER_KEY = 'appears_after_id';

    public static function pack(string $form, array $conditions, array $ordering = []): array
    {
        $formEnum = InternalProcessForm::tryFrom($form);
        $normalizedConditions = $formEnum !== null
            ? self::normalizeConditions($formEnum, $conditions)
            : [];

        return [
            self::FORM_KEY           => $form,
            self::CONDITIONS_KEY     => $normalizedConditions,
            self::APPEARS_BEFORE_KEY => $ordering[self::APPEARS_BEFORE_KEY] ?? null,
            self::APPEARS_AFTER_KEY  => $ordering[self::APPEARS_AFTER_KEY] ?? null,
        ];
    }

    /** @return array{form: ?string, form_detail: ?array, conditions: array<string, bool|int|string|null>, ordering: array{appears_before_id: ?string, appears_after_id: ?string}} */
    public static function unpack(?array $settings): array
    {
        $settings ??= [];
        $form = self::resolveForm($settings);

        return [
            'form'         => $form,
            'form_detail'  => self::presentForm($form),
            'conditions'   => self::extractConditions($settings, $form),
            'ordering'     => [
                self::APPEARS_BEFORE_KEY => $settings[self::APPEARS_BEFORE_KEY] ?? null,
                self::APPEARS_AFTER_KEY  => $settings[self::APPEARS_AFTER_KEY] ?? null,
            ],
        ];
    }

    /** @return array<string, bool|int|string|null> */
    public static function normalizeConditions(InternalProcessForm $form, array $conditions): array
    {
        $defaults = InternalProcessCondition::defaultValuesForForm($form);

        foreach ($form->conditions() as $condition) {
            $key = $condition->value;
            if (! array_key_exists($key, $conditions)) {
                continue;
            }

            $defaults[$key] = match ($condition->type()) {
                InternalProcessConditionType::Int => $conditions[$key] !== null ? (int) $conditions[$key] : null,
                InternalProcessConditionType::String => $conditions[$key] !== null ? (string) $conditions[$key] : null,
                InternalProcessConditionType::Bool => (bool) $conditions[$key],
            };
        }

        if (! ($defaults[InternalProcessCondition::HasTaskDuration->value] ?? false)) {
            $defaults[InternalProcessCondition::MaxDurationHours->value] = null;
        }

        return $defaults;
    }

    /** @return array<string, bool|int|string|null> */
    private static function extractConditions(array $settings, ?string $form): array
    {
        $stored = $settings[self::CONDITIONS_KEY] ?? null;
        if (is_array($stored)) {
            return $stored;
        }

        $formEnum = $form !== null ? InternalProcessForm::tryFrom($form) : null;
        $defaults = $formEnum !== null
            ? InternalProcessCondition::defaultValuesForForm($formEnum)
            : [];

        foreach (InternalProcessCondition::storageKeys() as $key) {
            if (array_key_exists($key, $settings)) {
                $defaults[$key] = $settings[$key];
            }
        }

        return $defaults;
    }

    private static function resolveForm(array $settings): ?string
    {
        if (! empty($settings[self::FORM_KEY])) {
            return (string) $settings[self::FORM_KEY];
        }

        $legacyForms = $settings[self::FORMS_KEY] ?? [];
        if (is_array($legacyForms) && $legacyForms !== []) {
            return (string) $legacyForms[0];
        }

        return null;
    }

    /** @return ?array{key: string, label_ar: string} */
    private static function presentForm(?string $form): ?array
    {
        if ($form === null) {
            return null;
        }

        $enum = InternalProcessForm::tryFrom($form);

        return [
            'key'      => $form,
            'label_ar' => $enum?->labelAr() ?? $form,
        ];
    }

    public static function mergeStored(?array $existing, ?string $form, ?array $conditions, ?array $ordering): array
    {
        $unpacked = self::unpack($existing);
        $resolvedForm = $form ?? $unpacked['form'] ?? '';

        return self::pack(
            form: $resolvedForm,
            conditions: $conditions ?? $unpacked['conditions'],
            ordering: $ordering ?? $unpacked['ordering'],
        );
    }
}
