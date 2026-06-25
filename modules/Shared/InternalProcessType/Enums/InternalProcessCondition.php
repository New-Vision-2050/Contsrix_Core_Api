<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Enums;

enum InternalProcessCondition: string
{
    // ── Legacy flat-format conditions (createTask / endTask) ─────────────────
    case AllowDuringShift        = 'allow_during_shift';
    case AllowOutsideShift       = 'allow_outside_shift';
    case AllowOnHolidays         = 'allow_on_holidays';
    case CanExitOutsideLocation  = 'can_exit_outside_location';
    case MustBeInLocation        = 'must_be_in_location';
    case HasTaskDuration         = 'has_task_duration';
    case MaxDurationHours        = 'max_duration_hours';
    case MaxAttachments          = 'max_attachments';

    // ── Rich conditions for createTask ──────────────────────────────────────
    case MaxTaskDuration         = 'max_task_duration';
    case MaxScheduledDateOffset  = 'max_scheduled_date_offset';

    // ── Rich conditions with settings schema (startTask and beyond) ──────────
    case InsideShiftTime         = 'inside_shift_time';
    case InsideTaskLocation      = 'inside_task_location';
    case InsideCustomLocations   = 'inside_custom_locations';
    case EmployeeHasAttendance   = 'employee_has_attendance';
    case TaskIsApproved          = 'task_is_approved';
    case NoOpenTask              = 'no_open_task';

    // ─────────────────────────────────────────────────────────────────────────

    public function type(): InternalProcessConditionType
    {
        return match ($this) {
            self::MaxDurationHours, self::MaxAttachments => InternalProcessConditionType::Int,
            self::InsideShiftTime                        => InternalProcessConditionType::Time,
            default                                      => InternalProcessConditionType::Bool,
        };
    }

    /**
     * UI grouping for conditions within a form.
     *
     * - "precondition"  → gatekeeping rules that run before the form is accepted
     *                     (shift, location, attendance, task status, etc.)
     * - "in_form"       → constraints on individual form input fields
     *                     (duration, date, attachments, etc.)
     *
     * The frontend reads this value to split conditions into tabs automatically.
     * Adding a condition here with the correct group is all that is required;
     * no frontend code changes are needed.
     */
    public function formGroup(): string
    {
        return match ($this) {
            self::MaxTaskDuration,
            self::MaxScheduledDateOffset,
            self::InsideCustomLocations,
            self::HasTaskDuration,
            self::MaxDurationHours,
            self::MaxAttachments          => 'in_form',

            default                       => 'precondition',
        };
    }

    public function formGroupLabelAr(): string
    {
        return match ($this->formGroup()) {
            'precondition' => 'شروط قبل النموذج',
            'in_form'      => 'شروط داخل النموذج',
        };
    }

    public function category(): InternalProcessConditionCategory
    {
        return match ($this) {
            self::InsideShiftTime                             => InternalProcessConditionCategory::Time,
            self::MaxScheduledDateOffset                     => InternalProcessConditionCategory::Calendar,
            self::InsideTaskLocation,
            self::InsideCustomLocations,
            self::MustBeInLocation,
            self::AllowOutsideShift,
            self::CanExitOutsideLocation                     => InternalProcessConditionCategory::Location,
            self::EmployeeHasAttendance                      => InternalProcessConditionCategory::Attendance,
            self::TaskIsApproved                             => InternalProcessConditionCategory::TaskStatus,
            self::NoOpenTask                                 => InternalProcessConditionCategory::OpenTask,
            self::AllowDuringShift,
            self::AllowOnHolidays                            => InternalProcessConditionCategory::Shift,
            self::HasTaskDuration,
            self::MaxDurationHours,
            self::MaxTaskDuration                            => InternalProcessConditionCategory::Duration,
            self::MaxAttachments                             => InternalProcessConditionCategory::Attachment,
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::AllowDuringShift       => 'موظف داخل الدوام',
            self::AllowOutsideShift      => 'موظف خارج موقع الدوام',
            self::AllowOnHolidays        => 'مسموح في العطلات',
            self::CanExitOutsideLocation => 'يستطيع الخروج خارج الموقع',
            self::MustBeInLocation       => 'يجب أن يكون داخل الموقع عند البدء',
            self::HasTaskDuration        => 'مدة المهمة',
            self::MaxDurationHours       => 'أقصى مدة بالساعات',
            self::MaxAttachments         => 'أقصى عدد مرفقات',
            self::MaxTaskDuration        => 'الحد الأقصى لمدة المهمة',
            self::MaxScheduledDateOffset => 'الحد الأقصى لتاريخ المهمة',
            self::InsideShiftTime        => 'داخل وقت الدوام',
            self::InsideTaskLocation     => 'داخل موقع المهمة',
            self::InsideCustomLocations  => 'موقع المهمة داخل المناطق المخصصة',
            self::EmployeeHasAttendance  => 'الموظف مسجل حضور',
            self::TaskIsApproved         => 'المهمة معتمدة',
            self::NoOpenTask             => 'لا يوجد مهمة مفتوحة',
        };
    }

    /**
     * Settings fields the frontend must render for this condition.
     * Each entry describes one configurable parameter.
     *
     * @return list<array{key: string, type: string, label_ar: string, default: mixed}>
     */
    public function settingsSchema(): array
    {
        return match ($this) {
            self::AllowDuringShift => [
                [
                    'key'      => 'mode',
                    'type'     => 'select',
                    'label_ar' => 'نوع الشرط',
                    'default'  => 'shift',
                    'options'  => [
                        ['value' => 'shift',         'label_ar' => 'داخل الدوام'],
                        ['value' => 'specific_time', 'label_ar' => 'وقت محدد'],
                    ],
                ],
                [
                    'key'          => 'start_time',
                    'type'         => 'time',
                    'label_ar'     => 'من',
                    'default'      => '08:00',
                    'visible_when' => ['key' => 'mode', 'value' => 'specific_time'],
                ],
                [
                    'key'          => 'end_time',
                    'type'         => 'time',
                    'label_ar'     => 'إلى',
                    'default'      => '17:00',
                    'visible_when' => ['key' => 'mode', 'value' => 'specific_time'],
                ],
            ],
            self::InsideShiftTime => [
                ['key' => 'start_time',                 'type' => 'time', 'label_ar' => 'من',                                    'default' => '08:00'],
                ['key' => 'end_time',                   'type' => 'time', 'label_ar' => 'إلى',                                   'default' => '17:00'],
                ['key' => 'allow_before_start_minutes', 'type' => 'int',  'label_ar' => 'يسمح قبل بداية الدوام بـ (دقيقة)',  'default' => 0],
                ['key' => 'allow_before_end_minutes',   'type' => 'int',  'label_ar' => 'يسمح قبل نهاية الدوام بـ (دقيقة)', 'default' => 0],
            ],
            self::InsideTaskLocation => [
                ['key' => 'radius_meters', 'type' => 'int', 'label_ar' => 'نطاق السماح (متر)', 'default' => 100],
            ],
            self::InsideCustomLocations => [
                [
                    'key'      => 'polygons',
                    'type'     => 'map_polygons',
                    'label_ar' => 'المواقع المحددة على الخريطة',
                    'default'  => [],
                ],
            ],
            self::MaxTaskDuration => [
                ['key' => 'max_hours', 'type' => 'int', 'label_ar' => 'الحد الأقصى للمدة (ساعة)', 'default' => 8],
            ],
            self::MaxScheduledDateOffset => [
                [
                    'key'      => 'mode',
                    'type'     => 'select',
                    'label_ar' => 'نوع الشرط',
                    'default'  => 'max_task_date',
                    'options'  => [
                        ['value' => 'max_task_date', 'label_ar' => 'الحد الأقصى لتاريخ المهمة'],
                        ['value' => 'end_contract',  'label_ar' => 'نهاية عقد الموظف'],
                    ],
                ],
                [
                    'key'          => 'max_days',
                    'type'         => 'int',
                    'label_ar'     => 'الحد الأقصى للتاريخ (أيام)',
                    'default'      => 30,
                    'visible_when' => ['key' => 'mode', 'value' => 'max_task_date'],
                ],
            ],
            default => [],
        };
    }

    /**
     * Full definition sent to the frontend via GET /procedure-settings/forms-conditions.
     *
     * @return array{key: string, type: string, category: string, category_label_ar: string, label_ar: string, form_group: string, form_group_label_ar: string, settings_schema: list<array>}
     */
    public function toDefinition(): array
    {
        return [
            'key'                 => $this->value,
            'type'                => $this->type()->value,
            'category'            => $this->category()->value,
            'category_label_ar'   => $this->category()->labelAr(),
            'label_ar'            => $this->labelAr(),
            'form_group'          => $this->formGroup(),
            'form_group_label_ar' => $this->formGroupLabelAr(),
            'settings_schema'     => $this->settingsSchema(),
        ];
    }

    /**
     * Validation rules for the NEW rich-array conditions format:
     *   conditions = [ { key, is_active, sort_order, settings: {...} }, ... ]
     *
     * @return array<string, list<string>>
     */
    public static function validationRulesForForm(?string $formKey, string $prefix = 'conditions'): array
    {
        if ($formKey === null || $formKey === '') {
            return [];
        }

        $form = InternalProcessForm::tryFrom($formKey);
        if ($form === null) {
            return [];
        }

        $validKeys = array_map(static fn (self $c) => $c->value, $form->conditions());

        if (empty($validKeys)) {
            return [];
        }

        return [
            "{$prefix}"              => ['nullable', 'array'],
            "{$prefix}.*.key"        => ['required', 'string', 'in:' . implode(',', $validKeys)],
            "{$prefix}.*.is_active"  => ['nullable', 'boolean'],
            "{$prefix}.*.sort_order" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.*.settings"   => ['nullable', 'array'],
        ];
    }

    /**
     * Default condition objects for a form — used when creating a new internal procedure.
     * Returns a list of condition objects with defaults from settingsSchema().
     *
     * @return list<array{key: string, is_active: bool, sort_order: int, settings: array<string, mixed>}>
     */
    public static function defaultValuesForForm(InternalProcessForm $form): array
    {
        $result = [];
        foreach ($form->conditions() as $i => $condition) {
            $result[] = [
                'key'        => $condition->value,
                'is_active'  => false,
                'sort_order' => $i + 1,
                'settings'   => array_column($condition->settingsSchema(), 'default', 'key'),
            ];
        }

        return $result;
    }

    /**
     * Generate a preview array for in-form display from stored settings.
     *
     * Automatically:
     *   - extracts 'mode' if a mode field exists in the schema
     *   - fills in defaults from settingsSchema() for missing keys
     *   - respects visible_when filters (only includes fields relevant to the active mode)
     *   - passes through raw settings when schema is empty (legacy conditions)
     *
     * @return array{mode: ?string, constraints: array<string, mixed>}
     */
    public function toPreview(array $settings): array
    {
        $schema = $this->settingsSchema();

        // ── Extract mode (any schema field with key='mode') ───────────────
        $mode = null;
        foreach ($schema as $field) {
            if (($field['key'] ?? null) === 'mode') {
                $mode = $settings['mode'] ?? $field['default'] ?? null;
                break;
            }
        }

        // ── Build constraints from schema (excluding mode itself) ─────────
        $constraints = [];
        foreach ($schema as $field) {
            $key = $field['key'] ?? null;
            if ($key === null || $key === 'mode') {
                continue;
            }

            // Skip fields that are conditionally visible for a different mode
            if (isset($field['visible_when']) && ($field['visible_when']['value'] ?? null) !== $mode) {
                continue;
            }

            $constraints[$key] = $settings[$key] ?? $field['default'] ?? null;
        }

        // ── Legacy conditions with no schema: pass through raw settings ───
        if ($schema === [] && $settings !== []) {
            $constraints = $settings;
        }

        return [
            'mode'        => $mode,
            'constraints' => $constraints,
        ];
    }

    /** @return list<string> */
    public static function storageKeys(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
