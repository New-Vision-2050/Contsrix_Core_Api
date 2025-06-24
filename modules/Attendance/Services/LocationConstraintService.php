<?php

namespace Modules\Attendance\Services;

use Modules\Attendance\Contracts\LocationConstraintServiceInterface;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;

/**
 * Service for location-related attendance constraint validations.
 */
class LocationConstraintService extends BaseConstraintService implements LocationConstraintServiceInterface
{
    /**
     * Validate location constraints for attendance.
     * This is a dispatcher method that handles different types of location constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateLocationConstraint(Attendance $attendance, array $config): bool|array
    {
        // Get constraint subtype
        $subtype = $config['subtype'] ?? '';
        
        switch ($subtype) {
            case AttendanceConstraint::LOCATION_GEOFENCING:
                return $this->validateGeofencing($attendance, $config);
                
            case AttendanceConstraint::LOCATION_IP_RESTRICTION:
                return $this->validateIpRestriction($attendance, $config);
                
            case AttendanceConstraint::LOCATION_REMOTE_ZONES:
                return $this->validateRemoteZones($attendance, $config);
                
            case AttendanceConstraint::LOCATION_MULTI_LOCATION:
                return $this->validateMultiLocation($attendance, $config);
                
            default:
                return false;
        }
    }

    /**
     * Validate geofencing constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
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
        $attendanceLocation = $attendance->location ?? null;
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
     * @param array $config The constraint configuration
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
     * @param array $config The constraint configuration
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

    /**
     * Validate multi-location constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateMultiLocation(Attendance $attendance, array $config): bool|array
    {
        // Check if multi-location work is allowed
        $multiLocationAllowed = $config['multi_location_allowed'] ?? false;
        
        // Get user's assigned locations
        $userLocations = $attendance->user->assigned_locations ?? [];
        $attendanceLocationId = $attendance->location_id ?? null;
        
        // If multi-location is not allowed, check if user is at their primary location
        if (!$multiLocationAllowed) {
            $primaryLocationId = $attendance->user->primary_location_id ?? null;
            
            if ($attendanceLocationId && $attendanceLocationId !== $primaryLocationId) {
                return [
                    'constraint_type' => AttendanceConstraint::LOCATION_MULTI_LOCATION,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'Multi-location work is not allowed. Must work from primary location.',
                    'details' => [
                        'multi_location_allowed' => false,
                        'primary_location_id' => $primaryLocationId,
                        'attendance_location_id' => $attendanceLocationId
                    ]
                ];
            }
        }
        
        // If multi-location is allowed, check if location is in user's assigned locations
        if ($multiLocationAllowed && $attendanceLocationId) {
            $assignedLocationIds = array_column($userLocations, 'id');
            
            if (!in_array($attendanceLocationId, $assignedLocationIds)) {
                return [
                    'constraint_type' => AttendanceConstraint::LOCATION_MULTI_LOCATION,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'User is not assigned to work at this location.',
                    'details' => [
                        'attendance_location_id' => $attendanceLocationId,
                        'assigned_location_ids' => $assignedLocationIds
                    ]
                ];
            }
            
            // Check location-specific restrictions
            foreach ($userLocations as $location) {
                if ($location['id'] === $attendanceLocationId) {
                    // Check if location has time restrictions
                    if (isset($location['allowed_hours']) && is_array($location['allowed_hours'])) {
                        $clockInTime = \Carbon\Carbon::parse($attendance->clock_in_time)->format('H:i');
                        $startTime = $location['allowed_hours']['start_time'] ?? '00:00';
                        $endTime = $location['allowed_hours']['end_time'] ?? '23:59';
                        
                        if (!$this->isTimeWithinRange($clockInTime, $startTime, $endTime)) {
                            return [
                                'constraint_type' => AttendanceConstraint::LOCATION_MULTI_LOCATION,
                                'severity' => $this->getSeverityFromConfig($config),
                                'message' => 'Clock-in time is outside allowed hours for this location.',
                                'details' => [
                                    'location_id' => $attendanceLocationId,
                                    'clock_in_time' => $clockInTime,
                                    'allowed_hours' => $location['allowed_hours']
                                ]
                            ];
                        }
                    }
                    break;
                }
            }
        }
        
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
}
