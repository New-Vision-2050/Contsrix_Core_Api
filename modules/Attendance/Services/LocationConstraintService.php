<?php

namespace Modules\Attendance\Services;

use Illuminate\Support\Facades\Log;
use Modules\Attendance\Contracts\LocationConstraintServiceInterface;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\RadiusEnforcementService;
use Modules\Attendance\Services\TaskService;

/**
 * Service for location-related attendance constraint validations.
 */
class LocationConstraintService extends BaseConstraintService implements LocationConstraintServiceInterface
{
    /**
     * Constructor
     */
    public function __construct(
        private AttendanceService $attendanceService,
        private RadiusEnforcementService $radiusEnforcementService,
        private TaskService $taskService
    ) {}

    /**
     * Validate location constraints for attendance.
     * This is a dispatcher method that handles different types of location constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint to validate
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateLocationConstraint(Attendance $attendance,AttendanceConstraint $constraint ): bool|array
    {
        $config = $constraint->constraint_config;

        if($constraint->branch_locations) {
                return $this->validateMultiLocation($attendance, $constraint);
        }
        // Get constraint subtype
        $subtype = $config['subtype'] ?? '';
        // Switch based on constraint name to handle different location validations
        switch ($subtype) {
            case AttendanceConstraint::LOCATION_GEOFENCING:
                return $this->validateGeofencing($attendance, $config);

            case AttendanceConstraint::LOCATION_IP_RESTRICTION:
                return $this->validateIpRestriction($attendance,$config);

            case AttendanceConstraint::LOCATION_OFFICE_VERIFICATION:
                return $this->validateOfficeVerification($attendance, $config);

            case AttendanceConstraint::LOCATION_REMOTE_ZONES:
                return $this->validateRemoteZones($attendance, $config);

            case AttendanceConstraint::LOCATION_MULTI_LOCATION:
                return $this->validateMultiLocation($attendance, $constraint);

            case AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT:
                // Dispatch to the specialized RadiusEnforcementService
                $validationResult = $this->radiusEnforcementService->validateRadiusEnforcement($attendance, $config);

                // If there's a constraint violation, create a task for handling the exception
                if (is_array($validationResult)) {
                    $this->createTaskForViolation($attendance, $config, $validationResult);
                }

                return $validationResult;

            default:
                // Unknown constraint name, return false (no violation)
                // We're not logging here to avoid facade issues in unit tests
                return false;
        }
    }

    /**
     * Validate geofencing constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint to validate
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateGeofencing(Attendance $attendance, array $config): bool|array
    {
        // Check if geofencing is enabled
        $geofencingEnabled = $config['geofencing_enabled'] ?? false;
        if (!$geofencingEnabled) {
            return false;
        }
        // Get attendance location
        $attendanceLocation = $attendance->clock_in_location ?? null;
        if (!$attendanceLocation || !isset($attendanceLocation['latitude'], $attendanceLocation['longitude'])) {
            return [
                'constraint_type' => AttendanceConstraint::LOCATION_GEOFENCING,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Location data is required for geofencing but is missing.',
                'details' => [
                    'geofencing_enabled' => true,
                    'location_provided' => false
                ]
            ];
        }


        $userLat = (float)$attendanceLocation['latitude'];
        $userLon = (float)$attendanceLocation['longitude'];

        // Check against allowed zones
        if (isset($config['allowed_zones']) && is_array($config['allowed_zones'])) {
            $withinAllowedZone = false;

            foreach ($config['allowed_zones'] as $zone) {
                if (!isset($zone['center_latitude'], $zone['center_longitude'], $zone['radius_meters'])) {
                    continue;
                }

                $zoneLat = (float)$zone['center_latitude'];
                $zoneLon = (float)$zone['center_longitude'];
                $radiusMeters = (float)$zone['radius_meters'];

                $distance = $this->calculateDistance($userLat, $userLon, $zoneLat, $zoneLon) * 1000; // Convert to meters

                if ($distance <= $radiusMeters) {
                    $withinAllowedZone = true;
                    break;
                }
            }

            if (!$withinAllowedZone) {
                return [
                    'constraint_type' => AttendanceConstraint::LOCATION_GEOFENCING,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'User location is outside all allowed geofenced zones.',
                    'details' => [
                        'user_location' => $attendanceLocation,
                        'allowed_zones' => $config['allowed_zones'],
                        'within_zone' => false
                    ]
                ];
            }
        }

        // Check against restricted zones
        if (isset($config['restricted_zones']) && is_array($config['restricted_zones'])) {
            foreach ($config['restricted_zones'] as $zone) {
                if (!isset($zone['center_latitude'], $zone['center_longitude'], $zone['radius_meters'])) {
                    continue;
                }

                $zoneLat = (float)$zone['center_latitude'];
                $zoneLon = (float)$zone['center_longitude'];
                $radiusMeters = (float)$zone['radius_meters'];

                $distance = $this->calculateDistance($userLat, $userLon, $zoneLat, $zoneLon) * 1000; // Convert to meters

                if ($distance <= $radiusMeters) {
                    return [
                        'constraint_type' => AttendanceConstraint::LOCATION_GEOFENCING,
                        'severity' => $this->getSeverityFromConfig($config),
                        'message' => 'User location is within a restricted geofenced zone.',
                        'details' => [
                            'user_location' => $attendanceLocation,
                            'restricted_zone' => $zone,
                            'distance_meters' => $distance
                        ]
                    ];
                }
            }
        }

        return false;
    }

    /**
     * Validate IP restriction constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint to validate
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateIpRestriction(Attendance $attendance, array $config): bool|array
    {
        // Check if IP restriction is enabled
        $ipRestrictionEnabled = $config['ip_restriction_enabled'] ?? false;

        if (!$ipRestrictionEnabled) {
            return false;
        }

        // Get user's IP address
        $userIp = $attendance->ip_address ?? null;
        if (!$userIp) {
            return [
                'constraint_type' => AttendanceConstraint::LOCATION_IP_RESTRICTION,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'IP address is required for IP restriction but is missing.',
                'details' => [
                    'ip_restriction_enabled' => true,
                    'ip_provided' => false
                ]
            ];
        }

        // Check against allowed IP addresses
        if (isset($config['allowed_ips']) && is_array($config['allowed_ips'])) {
            $ipAllowed = false;

            foreach ($config['allowed_ips'] as $allowedIp) {
                if ($this->ipMatches($userIp, $allowedIp)) {
                    $ipAllowed = true;
                    break;
                }
            }

            if (!$ipAllowed) {
                return [
                    'constraint_type' => AttendanceConstraint::LOCATION_IP_RESTRICTION,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'User IP address is not in the allowed list.',
                    'details' => [
                        'user_ip' => $userIp,
                        'allowed_ips' => $config['allowed_ips'],
                        'ip_allowed' => false
                    ]
                ];
            }
        }

        // Check against blocked IP addresses
        if (isset($config['blocked_ips']) && is_array($config['blocked_ips'])) {
            foreach ($config['blocked_ips'] as $blockedIp) {
                if ($this->ipMatches($userIp, $blockedIp)) {
                    return [
                        'constraint_type' => AttendanceConstraint::LOCATION_IP_RESTRICTION,
                        'severity' => $this->getSeverityFromConfig($config),
                        'message' => 'User IP address is in the blocked list.',
                        'details' => [
                            'user_ip' => $userIp,
                            'blocked_ip' => $blockedIp
                        ]
                    ];
                }
            }
        }

        return false;
    }

    /**
     * Validate remote zones constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint to validate
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateRemoteZones(Attendance $attendance, array $config): bool|array
    {

        // Check if remote work is allowed
        $remoteWorkAllowed = $config['remote_work_allowed'] ?? false;

        // Get attendance location
        $attendanceLocation = $attendance->location ?? null;
        $isRemoteLocation = $attendance->is_remote_location ?? false;

        // If remote work is not allowed and user is in remote location
        if (!$remoteWorkAllowed && $isRemoteLocation) {
            return [
                'constraint_type' => AttendanceConstraint::LOCATION_REMOTE_ZONES,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Remote work is not allowed.',
                'details' => [
                    'remote_work_allowed' => false,
                    'is_remote_location' => true
                ]
            ];
        }

        // If remote work is allowed, check remote zone restrictions
        if ($remoteWorkAllowed && $isRemoteLocation) {
            // Check against allowed remote zones
            if (isset($config['allowed_remote_zones']) && is_array($config['allowed_remote_zones'])) {
                if (!$attendanceLocation || !isset($attendanceLocation['latitude'], $attendanceLocation['longitude'])) {
                    return [
                        'constraint_type' => AttendanceConstraint::LOCATION_REMOTE_ZONES,
                        'severity' => $this->getSeverityFromConfig($config),
                        'message' => 'Location data is required for remote zone validation but is missing.',
                        'details' => [
                            'remote_work_allowed' => true,
                            'location_provided' => false
                        ]
                    ];
                }

                $userLat = (float)$attendanceLocation['latitude'];
                $userLon = (float)$attendanceLocation['longitude'];
                $withinAllowedRemoteZone = false;

                foreach ($config['allowed_remote_zones'] as $zone) {
                    if (!isset($zone['center_latitude'], $zone['center_longitude'], $zone['radius_meters'])) {
                        continue;
                    }

                    $zoneLat = (float)$zone['center_latitude'];
                    $zoneLon = (float)$zone['center_longitude'];
                    $radiusMeters = (float)$zone['radius_meters'];

                    $distance = $this->calculateDistance($userLat, $userLon, $zoneLat, $zoneLon) * 1000; // Convert to meters

                    if ($distance <= $radiusMeters) {
                        $withinAllowedRemoteZone = true;
                        break;
                    }
                }

                if (!$withinAllowedRemoteZone) {
                    return [
                        'constraint_type' => AttendanceConstraint::LOCATION_REMOTE_ZONES,
                        'severity' => $this->getSeverityFromConfig($config),
                        'message' => 'Remote location is outside all allowed remote zones.',
                        'details' => [
                            'user_location' => $attendanceLocation,
                            'allowed_remote_zones' => $config['allowed_remote_zones'],
                            'within_zone' => false
                        ]
                    ];
                }
            }

            // Check remote work time restrictions
            if (isset($config['remote_work_hours']) && is_array($config['remote_work_hours'])) {
                $clockInTime = \Carbon\Carbon::parse($attendance->clock_in_time)->format('H:i');
                $startTime = $config['remote_work_hours']['start_time'] ?? '00:00';
                $endTime = $config['remote_work_hours']['end_time'] ?? '23:59';

                if (!$this->isTimeWithinRange($clockInTime, $startTime, $endTime)) {
                    return [
                        'constraint_type' => AttendanceConstraint::LOCATION_REMOTE_ZONES,
                        'severity' => $this->getSeverityFromConfig($config),
                        'message' => 'Remote work is not allowed during this time.',
                        'details' => [
                            'clock_in_time' => $clockInTime,
                            'allowed_hours' => $config['remote_work_hours']
                        ]
                    ];
                }
            }
        }

        return false;
    }
   private function getLatestUserLocation(Attendance $attendance): ?array
    {
        $trackingPoints = $attendance->location_tracking ?? [];

        // If there are tracking points, return the very last one.
        if (!empty($trackingPoints) && is_array($trackingPoints)) {
            return end($trackingPoints); // end() gets the last element of an array
        }

        // If there's no tracking data, fall back to the clock-in location.
        return $attendance->clock_in_location ?? null;
    }

    /**
     * Validate multi-location constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint to validate
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
      public function validateMultiLocation(Attendance $attendance, AttendanceConstraint $constraint): bool|array
    {
        // 1. Get the constraint's configuration
        $config = $constraint->constraint_config ?? [];

        // 2. Get the list of allowed branch locations directly from the constraint itself.
        // This is the main fix: We use the data from the constraint, not from the user.
        $allowedBranchLocations = $constraint->branch_locations ?? [];
        // If no branch locations are defined in this constraint, we cannot validate.
        if (empty($allowedBranchLocations)) {
            // This is not a violation, it just means this constraint is misconfigured or not applicable.
            return false;
        }
        // 3. Get the user's current location from the clock-in data.
        // $userLocation = $attendance->clock_in_location;
        $userLocation  = $this->getLatestUserLocation($attendance);

        // If the user did not provide a location, we must return a violation.
        if (!$userLocation || !isset($userLocation['latitude'], $userLocation['longitude'])) {
            return [
                'constraint_type' => $constraint->constraint_name,
                'severity' => $config['severity'] ?? 'high',
                'message' => 'Location data is required to validate against branch locations but was not provided.',
                'details' => ['reason' => 'Missing GPS data from user.']
            ];
        }
        $userLat = (float) $userLocation['latitude'];
        $userLon = (float) $userLocation['longitude'];
        // 4. Loop through each defined branch location and check if the user is within its radius.
        $isWithinAnyAllowedBranch = false;
        foreach ($allowedBranchLocations as $branchLocation) {
            // Ensure the branch location data is complete
            if (!isset($branchLocation['latitude'], $branchLocation['longitude'], $branchLocation['radius'])) {
                continue; // Skip misconfigured branch locations
            }

            $branchLat = (float) $branchLocation['latitude'];
            $branchLon = (float) $branchLocation['longitude'];
            $branchRadius = (float) $branchLocation['radius'];
            // Calculate the distance between the user and the branch center.
            $distanceInMeters = $this->calculateDistance($userLat, $userLon, $branchLat, $branchLon);
            // Check if the distance is within the allowed radius for this branch.
            if ($distanceInMeters <= $branchRadius) {
                $isWithinAnyAllowedBranch = true;
                // Since the user is within a valid location, we can stop checking.
                break;
            }
        }
        // 5. If the user was not within the radius of ANY of the defined branches, return a violation.
        if (!$isWithinAnyAllowedBranch) {
            if($attendance->clock_in_time && $attendance->clock_out_time){
                $this->attendanceService->endShiftAutomatically(
                    (string) $attendance->id,
                    'auto_multi_location_enforcement', // A specific reason for this type of checkout
                    "Shift ended: User was not within any assigned branch location.",
                );
            }
            return [
                'constraint_type' => $constraint->constraint_name,
                'severity' => $config['severity'] ?? 'high',
                'message' => 'Your location is outside of all assigned work branches.',
                'details' => [
                    'user_location' => [
                        'latitude' => $userLat,
                        'longitude' => $userLon
                    ],

                ]
            ];
        }
        // 6. If the user is within at least one allowed branch location, the validation passes.
        return false;
    }

    /**
     * Validate office verification constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint to validate
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateOfficeVerification(Attendance $attendance, array $config): bool|array
    {
        // Check if this type of verification is enabled in the constraint's config.
        $officeVerificationEnabled = $config['office_verification_enabled'] ?? false;
        if (!$officeVerificationEnabled) {
            return false; // Constraint is not active, so no violation.
        }

        // Get the verification data sent by the user during the clock-in request.
        $providedVerification = $attendance->verification_data ?? [];

        // Get the list of required verification methods from the constraint's config.
        $requiredMethods = $config['required_verification'] ?? [];
        // Check if all required verification methods were provided by the user.
        $missingMethods = [];
        foreach ($requiredMethods as $method => $isRequired) {
            if ($isRequired && !isset($providedVerification[$method])) {
                $missingMethods[] = $method;
            }
        }

        // If any required methods are missing, return a violation immediately.
        if (!empty($missingMethods)) {
            return [
                'constraint_type' => AttendanceConstraint::LOCATION_OFFICE_VERIFICATION,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Office verification failed. Required verification methods are missing.',
                'details' => [
                    'missing_methods' => $missingMethods,
                    'required_methods' => array_keys(array_filter($requiredMethods)),
                    'provided_data' => $providedVerification,
                ]
            ];
        }
        // Validate the details of each provided verification method.
        foreach ($providedVerification as $method => $data) {
            if (!($requiredMethods[$method] ?? false)) {
                continue;
            }
            switch ($method) {
                // Scenario: Wi-Fi Network Verification
                case 'wifi_verification':
                    $allowedSSIDs = $config['wifi_details']['allowed_ssids'] ?? [];
                    $userSSID = $data['ssid'] ?? null;
                    if (empty($allowedSSIDs) || !in_array($userSSID, $allowedSSIDs)) {
                        return [
                            'constraint_type' => AttendanceConstraint::LOCATION_OFFICE_VERIFICATION,
                            'severity' => $this->getSeverityFromConfig($config),
                            'message' => 'Connected to an unauthorized Wi-Fi network.',
                            'details' => [
                                'method' => 'wifi_verification',
                                'provided_ssid' => $userSSID,
                                'allowed_ssids' => $allowedSSIDs,
                            ]
                        ];
                    }

                    break;

                // Scenario: QR Code Scan Verification
                case 'qr_code_verification':
                    $expectedQRCodePayload = $config['qr_code_details']['payload'] ?? null;
                    $scannedPayload = $data['payload'] ?? null;
                    if (!$expectedQRCodePayload || $scannedPayload !== $expectedQRCodePayload) {
                        return [
                            'constraint_type' => AttendanceConstraint::LOCATION_OFFICE_VERIFICATION,
                            'severity' => $this->getSeverityFromConfig($config),
                            'message' => 'Invalid or incorrect QR code scanned.',
                            'details' => [
                                'method' => 'qr_code_verification',
                                'scanned_payload' => $scannedPayload,
                            ]
                        ];
                    }
                    break;
            }
        }
        // If all checks pass, there is no violation.
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

    /**
     * Check if an IP address matches a pattern (supports CIDR notation).
     *
     * @param string $ip IP address to check
     * @param string $pattern IP pattern (can be single IP or CIDR)
     * @return bool True if IP matches pattern
     */
    private function ipMatches(string $ip, string $pattern): bool
    {
        // If pattern contains CIDR notation
        if (strpos($pattern, '/') !== false) {
            list($subnet, $mask) = explode('/', $pattern);
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $maskLong = -1 << (32 - (int)$mask);

            return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
        }

        // Exact IP match
        return $ip === $pattern;
    }

    /**
     * Create a task for handling constraint violations
     *
     * @param Attendance $attendance The attendance record with violation
     * @param array $config The constraint that was violated
     * @param array $violationDetails Details about the violation
     * @return void
     */
    private function createTaskForViolation(Attendance $attendance, array $config, array $violationDetails): void
    {
        try {
            // Use the TaskService to create a constraint exception task
            $this->taskService->createConstraintExceptionTask(
                $attendance,
                $config,
                $violationDetails
            );

            // Log task creation for the violation
            Log::info('Created task for constraint violation', [
                'attendance_id' => $attendance->id,
                'constraint_id' => $config->id,
                'violation_type' => $violationDetails['constraint_type'] ?? 'unknown',
                'severity' => $violationDetails['severity'] ?? 'medium'
            ]);
        } catch (\Exception $e) {
            // Log any errors that occur during task creation, but don't fail the validation
            Log::error('Failed to create task for constraint violation', [
                'attendance_id' => $attendance->id,
                'constraint_id' => $config->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate radius enforcement constraints with automatic shift ending.
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
                    $exceptionStart = \Carbon\Carbon::parse($exception['start_time'], $timezone);
                    $exceptionEnd = \Carbon\Carbon::parse($exception['end_time'], $timezone);
                    $now = \Carbon\Carbon::now($timezone);

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
            $timestamp = \Carbon\Carbon::parse($trackPoint['timestamp']);

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
            $timeOutsideRadius += $firstOutsideTime->diffInMinutes(\Carbon\Carbon::now($timezone));
        }

        // Check if time outside radius exceeds threshold
        if ($timeOutsideRadius > $timeThreshold) {
            // Violation detected - time outside radius exceeds threshold

            // If configured to end shift automatically
            if ($endShiftIfViolated) {
                // End the shift automatically based on configuration using the service
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
}
