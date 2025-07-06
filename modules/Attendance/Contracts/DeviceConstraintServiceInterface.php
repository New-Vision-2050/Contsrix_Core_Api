<?php

namespace Modules\Attendance\Contracts;

use Modules\Attendance\Models\Attendance;

/**
 * Interface for device-related attendance constraint validations.
 */
interface DeviceConstraintServiceInterface
{
    /**
     * Validate device constraints for attendance.
     * This is a dispatcher method that handles different types of device constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateDeviceConstraint(Attendance $attendance, array $config): bool|array;

    /**
     * Validate authorized device constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateAuthorizedOnly(Attendance $attendance, array $config): bool|array;

    /**
     * Validate device fingerprinting constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateFingerprinting(Attendance $attendance, array $config): bool|array;

    /**
     * Validate single policy constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateSinglePolicy(Attendance $attendance, array $config): bool|array;

    /**
     * Validate app restrictions constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateAppRestrictions(Attendance $attendance, array $config): bool|array;

    /**
     * Validate browser restrictions constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateBrowserRestrictions(Attendance $attendance, array $config): bool|array;
}
