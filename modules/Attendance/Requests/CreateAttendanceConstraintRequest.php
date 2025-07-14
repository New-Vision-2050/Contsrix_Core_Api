<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\DTO\CreateAttendanceConstraintDTO;
use Modules\Attendance\DataClasses\MultiplePeriodsConfig;
use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;

class CreateAttendanceConstraintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    // public function authorize(): bool
    // {
    //     return $this->user()->can('create_attendance_constraints');
    // }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'department_id' => ['nullable', 'string', 'max:255'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['exists:management_hierarchies,id'],
            'branch_locations' => ['nullable', 'array'],
            'branch_locations.*' => ['array'],
            'branch_locations.*.name' => ['required_with:branch_locations.*', 'string', 'max:255'],
            'branch_locations.*.branch_id' => ['required_with:branch_locations.*', 'string', 'max:50'],
            'branch_locations.*.address' => ['required_with:branch_locations.*', 'string', 'max:500'],
            'branch_locations.*.latitude' => ['required_with:branch_locations.*', 'numeric', 'between:-90,90'],
            'branch_locations.*.longitude' => ['required_with:branch_locations.*', 'numeric', 'between:-180,180'],
            'branch_locations.*.radius' => ['required_with:branch_locations.*', 'integer', 'min:1', 'max:10000'],
            'constraint_type' => [
                'required',
                'string',
                 'in:' . implode(',', array_keys(AttendanceConstraint::getConstraintArrayTypes()))
            ],
            'constraint_name' => 'required|string|max:255',
            'constraint_config' => 'nullable|array',
            'is_active' => 'boolean',
            'inherit_from_parent' => ['boolean'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:10'],
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'constraint_type.in' => 'The selected constraint type is invalid.',
            'constraint_config.required' => 'Constraint configuration is required.',
            'start_date.after_or_equal' => 'Start date must be today or later.',
            'end_date.after' => 'End date must be after start date.',
            'priority.min' => 'Priority must be at least 1.',
            'priority.max' => 'Priority cannot exceed 10.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateConstraintConfig($validator);
        });
    }

    /**
     * Validate constraint configuration based on type and name.
     */
    protected function validateConstraintConfig($validator): void
    {
        $type = $this->input('constraint_type');
        $name = $this->input('constraint_name');
        $config = $this->input('constraint_config', []);

        switch ($type) {
            case AttendanceConstraint::TYPE_LOCATION:
                $this->validateLocationConfig($validator, $name, $config);
                break;

            case AttendanceConstraint::TYPE_TIME:
                $this->validateTimeConfig($validator, $name, $config);
                break;

            case AttendanceConstraint::TYPE_DEVICE:
                $this->validateDeviceConfig($validator, $name, $config);
                break;

            case AttendanceConstraint::TYPE_BEHAVIORAL:
                $this->validateBehavioralConfig($validator, $name, $config);
                break;
        }
    }

    /**
     * Validate location constraint configuration.
     */
    protected function validateLocationConfig($validator, string $name, array $config): void
    {
        switch ($name) {
            case AttendanceConstraint::LOCATION_GEOFENCING:
                if (!isset($config['allowed_zones']) || !is_array($config['allowed_zones'])) {
                    $validator->errors()->add('constraint_config.allowed_zones', 'Allowed zones are required for geofencing.');
                    return;
                }

                foreach ($config['allowed_zones'] as $index => $zone) {
                    if (!isset($zone['latitude']) || !is_numeric($zone['latitude'])) {
                        $validator->errors()->add("constraint_config.allowed_zones.{$index}.latitude", 'Latitude is required and must be numeric.');
                    }
                    if (!isset($zone['longitude']) || !is_numeric($zone['longitude'])) {
                        $validator->errors()->add("constraint_config.allowed_zones.{$index}.longitude", 'Longitude is required and must be numeric.');
                    }
                    if (!isset($zone['radius']) || !is_numeric($zone['radius']) || $zone['radius'] <= 0) {
                        $validator->errors()->add("constraint_config.allowed_zones.{$index}.radius", 'Radius is required and must be a positive number.');
                    }
                }
                break;

            case AttendanceConstraint::LOCATION_IP_RESTRICTION:
                if (empty($config['allowed_ips']) && empty($config['allowed_ranges'])) {
                    $validator->errors()->add('constraint_config', 'Either allowed IPs or allowed IP ranges must be specified.');
                }
                break;
        }
    }

    /**
     * Validate time constraint configuration.
     */
    protected function validateTimeConfig($validator, string $name, array $config): void
    {
        switch ($name) {
            case AttendanceConstraint::TIME_SHIFT_ENFORCEMENT:
                if (!isset($config['shift_start_time']) || !isset($config['shift_end_time'])) {
                    $validator->errors()->add('constraint_config', 'Shift start and end times are required.');
                }
                break;

            case AttendanceConstraint::TIME_BREAK_LIMITS:
                if (!isset($config['max_break_duration']) || !is_numeric($config['max_break_duration'])) {
                    $validator->errors()->add('constraint_config.max_break_duration', 'Maximum break duration is required and must be numeric.');
                }
                break;

            case AttendanceConstraint::TIME_MULTIPLE_PERIODS:
                $this->validateMultiplePeriodsConfig($validator, $config);
                break;
        }
    }

    /**
     * Validate device constraint configuration.
     */
    protected function validateDeviceConfig($validator, string $name, array $config): void
    {
        switch ($name) {
            case AttendanceConstraint::DEVICE_AUTHORIZED_ONLY:
                if (!isset($config['authorized_devices']) || !is_array($config['authorized_devices'])) {
                    $validator->errors()->add('constraint_config.authorized_devices', 'Authorized devices list is required.');
                }
                break;
        }
    }

    /**
     * Validate behavioral constraint configuration.
     */
    protected function validateBehavioralConfig($validator, string $name, array $config): void
    {
        switch ($name) {
            case AttendanceConstraint::BEHAVIORAL_CONSECUTIVE_LIMIT:
                if (!isset($config['max_consecutive_days']) || !is_numeric($config['max_consecutive_days']) || $config['max_consecutive_days'] <= 0) {
                    $validator->errors()->add('constraint_config.max_consecutive_days', 'Maximum consecutive days is required and must be a positive number.');
                }
                break;

            case AttendanceConstraint::BEHAVIORAL_WEEKLY_HOURS:
                if (!isset($config['max_weekly_hours']) || !is_numeric($config['max_weekly_hours']) || $config['max_weekly_hours'] <= 0) {
                    $validator->errors()->add('constraint_config.max_weekly_hours', 'Maximum weekly hours is required and must be a positive number.');
                }
                break;
        }
    }

    /**
     * Validate multiple periods constraint configuration.
     */
    protected function validateMultiplePeriodsConfig($validator, array $config): void
    {
        try {
            // Use the data class for strict validation
            MultiplePeriodsConfig::fromArray($config);
        } catch (InvalidArgumentException $e) {
            $validator->errors()->add('constraint_config', 'Multiple periods configuration error: ' . $e->getMessage());
            return;
        }

        // Additional business logic validation can be added here
        $multiplePeriodsConfig = MultiplePeriodsConfig::fromArray($config);

        // Validate reasonable work hours
        $weeklyHours = $multiplePeriodsConfig->getTotalWeeklyWorkHours();
        if ($weeklyHours > 80) {
            $validator->errors()->add('constraint_config', "Weekly work hours ({$weeklyHours}) exceed reasonable limits (80 hours).");
        }

        // Validate at least one enabled day
        if (count($multiplePeriodsConfig->getEnabledDays()) === 0) {
            $validator->errors()->add('constraint_config', 'At least one day must be enabled in the weekly schedule.');
        }

        // Check for potential scheduling conflicts with cross-day periods
        if ($multiplePeriodsConfig->hasCrossDayPeriods()) {
            $validationIssues = $multiplePeriodsConfig->weeklySchedule->validate();
            foreach ($validationIssues as $issue) {
                $validator->errors()->add('constraint_config', $issue);
            }
        }
    }

    /**
     * Create DTO from validated request data.
     */
    public function createConstraintDTO(UuidInterface $companyId, UuidInterface $createdBy): CreateAttendanceConstraintDTO
    {
        $validated = $this->validated();

        return new CreateAttendanceConstraintDTO(
            constraint_type: $validated['constraint_type'],
            name: $validated['constraint_name'],
            description: $validated['notes'] ?? '',
            config: $validated['constraint_config'] ?? [],
            company_id: $companyId,
            created_by: $createdBy,
            user_id: $validated['user_id'] ?? null,
            department_id: $validated['department_id'] ?? null,
            branch_ids: $validated['branch_ids'] ?? [],
            branch_locations: $validated['branch_locations'] ?? null,
            priority: $validated['priority'] ?? 1,
            is_active: $validated['is_active'] ?? true,
            inherit_from_parent: $validated['inherit_from_parent'] ?? false,
            effective_from: $validated['start_date'] ?? null,
            effective_to: $validated['end_date'] ?? null,
        );
    }
}
