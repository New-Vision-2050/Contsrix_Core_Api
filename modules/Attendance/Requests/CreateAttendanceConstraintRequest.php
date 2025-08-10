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
            'user_ids'         => ['nullable', 'array'],
            'user_ids.*'       => ['uuid', 'exists:users,id'],
            'department_ids'   => ['nullable', 'array'],
            'department_ids.*' => ['exists:management_hierarchies,id'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['exists:management_hierarchies,id'],

            // Make branch_locations nullable here, its requirement will be conditional in after() validator
            'branch_locations' => ['nullable', 'array'],
            // These rules will validate format if branch_locations is present
            'branch_locations.*.name' => ['required_with:branch_locations.*', 'string', 'max:255'],
            'branch_locations.*.branch_id' => ['required_with:branch_locations.*', 'string', 'max:50'], // Assuming branch_id refers to branch_ids
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
            'start_date.after_or_equal' => 'Start date must be today or later.',
            'end_date.after' => 'End date must be after start date.',
            'priority.min' => 'Priority must be at least 1.',
            'priority.max' => 'Priority cannot exceed 10.',
            'constraint_config.time_rules.weekly_schedule.required' => 'The weekly schedule is required for multiple periods configuration.',
            'branch_locations.required_when_location_enabled' => 'Branch locations are required when location attendance type is enabled.',
            'branch_locations.*.name.required_with' => 'Branch location name is required.',
            'branch_locations.*.branch_id.required_with' => 'Branch location ID is required.',
            'branch_locations.*.address.required_with' => 'Branch location address is required.',
            'branch_locations.*.latitude.required_with' => 'Branch location latitude is required.',
            'branch_locations.*.longitude.required_with' => 'Branch location longitude is required.',
            'branch_locations.*.radius.required_with' => 'Branch location radius is required and must be a positive integer.',
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
        // $name = $this->input('constraint_name');
        $config = $this->input('constraint_config', []);


        // This type handles 'allowed_zones' or 'allowed_ips' directly within constraint_config
        // $this->validateLocationConfig($validator, $name, $config);

        // This type handles specific time rules like shift enforcement or break limits
        $this->validateTimeConfig($validator, $config);


        $this->validateRegularTypeConfig($validator, $config);

    }


    /**
     * Validate location constraint configuration (for TYPE_LOCATION).
     * This method expects $config to be the direct content of 'constraint_config'.
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
     * Validate time constraint configuration (for TYPE_TIME).
     * This method expects $config to be the direct content of 'constraint_config'.
     */
    protected function validateTimeConfig($validator, array $config): void
    {
                // The actual periods config is nested under 'time_rules' in your example JSON structure
            if (!isset($config['time_rules']) || !is_array($config['time_rules'])) {
                $validator->errors()->add('constraint_config.time_rules', 'Time rules configuration is missing or invalid for multiple periods.');
                return;
            }
            // Pass the specific time_rules config to the validation function
            $this->validateMultiplePeriodsConfig($validator, $config['time_rules']);
    }



    /**
     * Validate regular type configuration (which might contain multiple rules).
     */
    protected function validateRegularTypeConfig($validator, array $config): void
    {
        // 1. Validate default_location
        if (!isset($config['default_location'])) {
            $validator->errors()->add('constraint_config.default_location', 'Default location setting is required for regular constraint type.');
        }

        // 2. Validate type_attendance structure and options
        if (!isset($config['type_attendance']) || !is_array($config['type_attendance'])) {
             $validator->errors()->add('constraint_config.type_attendance', 'Attendance type configuration is required for regular constraint type.');
             // If type_attendance is missing or malformed, stop further related checks
             return;
        }

        $locationEnabled = $config['type_attendance']['location'] ?? false;
        $fingerprintEnabled = $config['type_attendance']['fingerprint'] ?? false;

        // Ensure at least one attendance type is enabled
        if (!$locationEnabled && !$fingerprintEnabled) {
            $validator->errors()->add('constraint_config.type_attendance', 'At least one attendance type (location or fingerprint) must be enabled.');
        }

        // 3. Conditionally validate branch_locations if location attendance is enabled
        if ($locationEnabled) {
            $this->validateBranchLocations($validator);
        }

        // 4. Validate time_rules if subtype is multiple_periods
        if (isset($config['time_rules']) && is_array($config['time_rules']) && ($config['time_rules']['subtype'] ?? null) === 'multiple_periods') {
            $this->validateMultiplePeriodsConfig($validator, $config['time_rules']);
        }

        // Add other validation for other rules that might be under 'regular' type if any
    }

    /**
     * Validate branch_locations array (called when location attendance is enabled).
     * This method accesses branch_locations from the top-level request input.
     */
    protected function validateBranchLocations($validator): void
    {
        $branchLocations = $this->input('branch_locations');

        if (empty($branchLocations)) {
            $validator->errors()->add('branch_locations', 'Branch locations are required when location attendance type is enabled.');
            // No need to check individual items if the array itself is missing/empty
            return;
        }

        if (!is_array($branchLocations)) {
            $validator->errors()->add('branch_locations', 'Branch locations must be an array.');
            return;
        }
    }


    /**
     * Validate multiple periods constraint configuration.
     * This function now receives the content of 'time_rules' from the request.
     */
    protected function validateMultiplePeriodsConfig($validator, array $config): void
    {
        // Ensure that weekly_schedule is present as it's critical
        if (!isset($config['weekly_schedule']) || !is_array($config['weekly_schedule'])) {
            $validator->errors()->add('constraint_config.time_rules.weekly_schedule', 'The weekly schedule configuration is required for multiple periods.');
            return;
        }
        try {
            // Use the data class for strict parsing and initial validation
            $multiplePeriodsConfig = MultiplePeriodsConfig::fromArray($config);
            // Additional business logic validation
            $weeklyHours = $multiplePeriodsConfig->getTotalWeeklyWorkHours();
            if ($weeklyHours > (24 * 7)) { // Total possible hours in a week
                $validator->errors()->add('constraint_config.time_rules.weekly_schedule', "Weekly work hours ({$weeklyHours}) exceed the total hours in a week. Please check your periods.");
            }
            if ($weeklyHours < 0) { // Should not happen with current logic, but good as a safeguard
                $validator->errors()->add('constraint_config.time_rules.weekly_schedule', "Calculated weekly work hours are negative. Please check your periods.");
            }

            // Validate at least one enabled day
            if (count($multiplePeriodsConfig->getEnabledDays()) === 0) {
                $validator->errors()->add('constraint_config.time_rules.weekly_schedule', 'At least one day must be enabled in the weekly schedule.');
            }

            // Perform the overlap validation using the WeeklySchedule's validate method
            $validationIssues = $multiplePeriodsConfig->weeklySchedule->validate();
            foreach ($validationIssues as $issue) {
                // Add errors to the validator with a specific key for clarity
                $validator->errors()->add('constraint_config.time_rules.weekly_schedule', $issue);
            }

        } catch (InvalidArgumentException $e) {
            // Catch exceptions from data class parsing (e.g., malformed time strings)
            $validator->errors()->add('constraint_config.time_rules', 'Multiple periods configuration error: ' . $e->getMessage());
            return;
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
            notes: $validated['notes'] ?? '',
            config: $validated['constraint_config'] ?? [],
            company_id: $companyId,
            created_by: $createdBy,
            user_ids: $validated['user_ids'] ?? [],
            department_ids: $validated['department_ids'] ?? [],
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
