<?php

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Services\AttendanceService;

/**
 * Service for radius enforcement attendance constraint validations.
 * Extends the LocationConstraintService to add specialized radius enforcement capabilities.
 */
class RadiusEnforcementService
{
    /**
     * Constructor
     */
    public function __construct(
        private AttendanceService $attendanceService
    ) {}

    /**
     * Validate location radius enforcement constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint to validate against
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateRadiusEnforcement(Attendance $attendance, array $config): bool|array
    {
        $branchId = $attendance->branch_id;
        $locationTracking = $attendance->location_tracking;

        // Check if we have branch location configuration for this branch
        if (!isset($config['branch_locations'][$branchId])) {
            return [
                'constraint_type' => AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT,
                'severity' => $config['violation_severity'] ?? 'medium',
                'message' => 'No branch location configuration found for this branch.',
                'details' => [
                    'branch_id' => $branchId,
                    'available_branches' => array_keys($config['branch_locations'] ?? [])
                ]
            ];
        }

        // Skip validation if location tracking data is not available
        if (empty($locationTracking) || !is_array($locationTracking)) {
            return [
                'constraint_type' => AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT,
                'severity' => $config['violation_severity'] ?? 'medium',
                'message' => 'Location tracking data is required but missing.',
                'details' => [
                    'has_location_tracking' => false
                ]
            ];
        }

        // Get branch location configuration
        $branchLocation = $config['branch_locations'][$branchId];
        $branchLat = (float)$branchLocation['latitude'];
        $branchLon = (float)$branchLocation['longitude'];
        $allowedRadius = (float)$branchLocation['radius'];

        // Get enforcement configuration
        $enforcement = $config['enforcement'] ?? [];
        $timeThreshold = $enforcement['out_of_radius_time_threshold'] ?? 30; // Default 30 minutes
        $endShiftIfViolated = $enforcement['end_shift_if_violated'] ?? false;
        $markAbsentIfViolated = $enforcement['mark_absent_if_violated'] ?? false;
        $allowExceptions = $enforcement['allow_temporary_exceptions'] ?? false;

        // Check for temporary exceptions
        $timezone = $attendance->timezone ?? getTimeZoneByRequest() ?? config('app.timezone');
        if ($allowExceptions && !empty($attendance->exceptions)) {
            foreach ($attendance->exceptions as $exception) {
                if ($exception['type'] === 'temporary_location') {
                    $exceptionStart = Carbon::parse($exception['start_time'], $timezone);
                    $exceptionEnd = Carbon::parse($exception['end_time'], $timezone);
                    $now = Carbon::now($timezone);

                    // If current time is within exception period, use temporary location instead
                    if ($now->between($exceptionStart, $exceptionEnd)) {
                        // Check if employee is within temporary location radius
                        if (isset($exception['temporary_location'])) {
                            $tempLocation = $exception['temporary_location'];
                            $tempLat = (float)$tempLocation['latitude'];
                            $tempLon = (float)$tempLocation['longitude'];
                            $tempRadius = (float)$tempLocation['radius'];

                            // Check last known location against temporary location
                            $lastLocation = end($locationTracking);
                            $userLat = (float)$lastLocation['latitude'];
                            $userLon = (float)$lastLocation['longitude'];

                            $distance = $this->calculateDistance(
                                $userLat,
                                $userLon,
                                $tempLat,
                                $tempLon
                            ) * 1000; // Convert to meters

                            // If within temporary location radius, no violation
                            if ($distance <= $tempRadius) {
                                return false;
                            }
                        } else {
                            // Exception doesn't have location data but is still valid
                            return false;
                        }
                    }
                }
            }
        }

        // Track time spent outside radius
        $timeOutsideRadius = 0;
        $firstOutsideTime = null;
        $lastInsideTime = null;
        $currentlyOutside = false;
        $outsideLocations = [];

        // Sort location tracking data by timestamp
        usort($locationTracking, function($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });

        // Analyze location tracking data
        foreach ($locationTracking as $trackPoint) {
            $userLat = (float)$trackPoint['latitude'];
            $userLon = (float)$trackPoint['longitude'];
            $timestamp = Carbon::parse($trackPoint['timestamp']);

            $distance = $this->calculateDistance(
                $userLat,
                $userLon,
                $branchLat,
                $branchLon
            ) * 1000; // Convert to meters

            if ($distance > $allowedRadius) {
                // Employee is outside allowed radius
                $outsideLocations[] = [
                    'latitude' => $userLat,
                    'longitude' => $userLon,
                    'timestamp' => $timestamp->toDateTimeString(),
                    'distance' => $distance
                ];

                if (!$currentlyOutside) {
                    // Just went outside radius
                    $firstOutsideTime = $timestamp;
                    $currentlyOutside = true;
                }
            } else {
                // Employee is inside allowed radius
                if ($currentlyOutside) {
                    // Just came back inside radius
                    $currentlyOutside = false;
                    $duration = $lastInsideTime ? $firstOutsideTime->diffInMinutes($timestamp) : 0;
                    $timeOutsideRadius += $duration;
                }
                $lastInsideTime = $timestamp;
            }
        }

        // If still outside, calculate time from first outside to now
        if ($currentlyOutside && $firstOutsideTime) {
            $timeOutsideRadius += $firstOutsideTime->diffInMinutes(Carbon::now($timezone));
        }

        // Check if time outside radius exceeds threshold
        if ($timeOutsideRadius > $timeThreshold) {
            // Violation detected - time outside radius exceeds threshold

            // If configured to end shift automatically
            if ($endShiftIfViolated) {
                // End the shift automatically using the AttendanceService
                $this->attendanceService->endShiftAutomatically(
                    $attendance->id,
                    'auto_radius_enforcement',
                    'Shift automatically ended due to being outside allowed radius for ' .
                    $timeOutsideRadius . ' minutes (threshold: ' . $timeThreshold . ' minutes)',
                    $markAbsentIfViolated // Pass the mark absent configuration directly to the service
                );
            }

            // Return violation details
            return [
                'constraint_type' => AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT,
                'severity' => $config['violation_severity'] ?? 'high',
                'message' => 'Employee has been outside allowed radius for longer than allowed threshold.',
                'details' => [
                    'branch_location' => [
                        'name' => $branchLocation['name'],
                        'latitude' => $branchLat,
                        'longitude' => $branchLon,
                        'radius' => $allowedRadius
                    ],
                    'minutes_outside_radius' => $timeOutsideRadius,
                    'threshold_minutes' => $timeThreshold,
                    'enforcement_action' => $endShiftIfViolated ? 'end_shift' : null,
                    'day_marked_absent' => $markAbsentIfViolated,
                    'outside_locations' => $outsideLocations
                ]
            ];
        }

        // No violation
        return false;
    }

    /**
     * Calculate distance between two geographic points using Haversine formula.
     *
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in kilometers
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
