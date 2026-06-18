<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Enums;

enum InternalProcessCondition: string
{
    case AllowDuringShift   = 'allow_during_shift';
    case AllowOutsideShift  = 'allow_outside_shift';
    case AllowOnHolidays       = 'allow_on_holidays';
    case CanExitOutsideLocation = 'can_exit_outside_location';
    case HasTaskDuration       = 'has_task_duration';
    case MaxDurationHours   = 'max_duration_hours';
    case MaxAttachments     = 'max_attachments';

    public function type(): InternalProcessConditionType
    {
        return match ($this) {
            self::MaxDurationHours, self::MaxAttachments => InternalProcessConditionType::Int,
            default => InternalProcessConditionType::Bool,
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::AllowDuringShift   => 'موظف داخل الدوام',
            self::AllowOutsideShift  => 'موظف خارج الدوام',
            self::AllowOnHolidays       => 'مسموح في العطلات',
            self::CanExitOutsideLocation => 'يستطيع الخروج خارج الموقع',
            self::HasTaskDuration       => 'مدة المهمة',
            self::MaxDurationHours   => 'أقصى مدة بالساعات',
            self::MaxAttachments     => 'أقصى عدد مرفقات',
        };
    }

    /** @return array{key: string, type: string, label_ar: string} */
    public function toDefinition(): array
    {
        return [
            'key'      => $this->value,
            'type'     => $this->type()->value,
            'label_ar' => $this->labelAr(),
        ];
    }

    /** @return array<string, list<string>> */
    public static function validationRulesForForm(?string $formKey, string $prefix = 'conditions'): array
    {
        if ($formKey === null || $formKey === '') {
            return [];
        }

        $form = InternalProcessForm::tryFrom($formKey);
        if ($form === null) {
            return [];
        }

        $rules = [];
        foreach ($form->conditions() as $condition) {
            $key = "{$prefix}.{$condition->value}";

            $rules[$key] = match ($condition->type()) {
                InternalProcessConditionType::Int => $condition === self::MaxDurationHours
                    ? ['nullable', 'integer', 'min:1', 'max:24', 'required_if:' . $prefix . '.has_task_duration,true']
                    : ['nullable', 'integer', 'min:1', 'max:100'],
                InternalProcessConditionType::String => ['nullable', 'string', 'max:255'],
                InternalProcessConditionType::Bool => ['nullable', 'boolean'],
            };
        }

        return $rules;
    }

    /** @return array<string, bool|int|string|null> */
    public static function defaultValuesForForm(InternalProcessForm $form): array
    {
        $defaults = [];
        foreach ($form->conditions() as $condition) {
            $defaults[$condition->value] = match ($condition->type()) {
                InternalProcessConditionType::Int, InternalProcessConditionType::String => null,
                InternalProcessConditionType::Bool => match ($condition) {
                    self::AllowDuringShift, self::CanExitOutsideLocation => true,
                    self::AllowOutsideShift, self::AllowOnHolidays, self::HasTaskDuration => false,
                },
            };
        }

        return $defaults;
    }

    /** @return list<string> */
    public static function storageKeys(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
