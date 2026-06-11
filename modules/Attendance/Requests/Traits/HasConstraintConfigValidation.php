<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests\Traits;

use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\DataClasses\MultiplePeriodsConfig;
use InvalidArgumentException;

trait HasConstraintConfigValidation
{
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

            // Validate per-day hours against both the day-level cap and the constraint-level max_working_hours
            $maxWorkingHours = $this->input('max_working_hours') !== null
                ? (float) $this->input('max_working_hours')
                : 9.0;
            $this->validateDailyWorkHoursCap($validator, $config['weekly_schedule'], $maxWorkingHours);

        } catch (InvalidArgumentException $e) {
            // Catch exceptions from data class parsing (e.g., malformed time strings)
            $validator->errors()->add('constraint_config.time_rules', 'Multiple periods configuration error: ' . $e->getMessage());
            return;
        }
    }

    /**
     * Validate that each enabled day's shift periods do not exceed:
     *   1. The day-level declared total_work_hours (if set on that day).
     *   2. The constraint-level $maxWorkingHours cap (defaults to 9 h if not supplied).
     */
    protected function validateDailyWorkHoursCap($validator, array $weeklySchedule, float $maxWorkingHours = 9.0): void
    {
        foreach ($weeklySchedule as $dayName => $dayData) {
            if (!is_array($dayData) || !($dayData['enabled'] ?? false)) {
                continue;
            }

            $periods = $dayData['periods'] ?? [];
            $totalMinutes = 0;

            foreach ($periods as $period) {
                $startTime = $period['start_time'] ?? null;
                $endTime   = $period['end_time']   ?? null;
                $crossDay  = (bool) ($period['extends_to_next_day'] ?? false);

                if (!$startTime || !$endTime) {
                    continue;
                }

                [$startH, $startM] = array_map('intval', explode(':', $startTime));
                [$endH,   $endM]   = array_map('intval', explode(':', $endTime));

                $startMinutes = $startH * 60 + $startM;
                $endMinutes   = $endH   * 60 + $endM;

                if ($crossDay) {
                    $endMinutes += 24 * 60;
                } elseif ($endMinutes <= $startMinutes) {
                    continue; // malformed period — caught by DaySchedule parser
                }

                $totalMinutes += ($endMinutes - $startMinutes);
            }

            $totalHours = $totalMinutes / 60;

            // Check 1: day-level declared total_work_hours cap
            $declaredHours = isset($dayData['total_work_hours']) ? (float) $dayData['total_work_hours'] : null;

            if ($declaredHours !== null) {
                if ($declaredHours <= 0) {
                    $validator->errors()->add(
                        "constraint_config.time_rules.weekly_schedule.{$dayName}.total_work_hours",
                        "The total_work_hours for {$dayName} must be greater than 0."
                    );
                } elseif ($totalHours > $declaredHours) {
                    $validator->errors()->add(
                        "constraint_config.time_rules.weekly_schedule.{$dayName}.total_work_hours",
                        sprintf(
                            'The scheduled shifts for %s total %.2f hour(s), which exceeds the declared working hours cap of %.2f hour(s).',
                            $dayName, $totalHours, $declaredHours
                        )
                    );
                }
            }

            // Check 2: constraint-level max_working_hours cap
            if ($totalHours > $maxWorkingHours) {
                $validator->errors()->add(
                    "constraint_config.time_rules.weekly_schedule.{$dayName}",
                    sprintf(
                        'The scheduled shifts for %s total %.2f hour(s), which exceeds the maximum allowed working hours of %.2f hour(s) per day.',
                        $dayName, $totalHours, $maxWorkingHours
                    )
                );
            }
        }
    }
}
