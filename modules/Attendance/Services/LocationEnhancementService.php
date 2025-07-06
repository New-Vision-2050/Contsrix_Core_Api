<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\DTO\GeolocationDTO;
use Modules\Company\CompanyCore\Services\CompanyProfileService;

/**
 * LocationEnhancementService enhances attendance location data using geocoding capabilities.
 * It connects the AttendanceService with CompanyProfileService's geocoding functionality to:
 * 1. Resolve addresses from coordinates
 * 2. Find nearest city/state matches
 * 3. Validate location against company branches
 */
class LocationEnhancementService
{
    /**
     * @var CompanyProfileService
     */
    protected CompanyProfileService $companyProfileService;
    
    /**
     * @var LocationTrackingService
     */
    protected LocationTrackingService $locationTrackingService;

    /**
     * Constructor
     */
    public function __construct(
        CompanyProfileService $companyProfileService,
        LocationTrackingService $locationTrackingService
    ) {
        $this->companyProfileService = $companyProfileService;
        $this->locationTrackingService = $locationTrackingService;
    }

    /**
     * Enhance location data with full address details for a given attendance record
     *
     * @param Attendance $attendance The attendance record to enhance
     * @return array The enhanced location data with address details
     */
    public function enhanceLocationData(Attendance $attendance): array
    {
        $locationData = [];
        
        // Check if we have clock in location data
        if ($attendance->clock_in_location && 
            isset($attendance->clock_in_location['latitude'], $attendance->clock_in_location['longitude'])) {
            
            try {
                // Create a GeolocationDTO for the clock in location
                $geolocationDTO = new GeolocationDTO(
                    $attendance->clock_in_location['latitude'],
                    $attendance->clock_in_location['longitude']
                );
                
                // Get enhanced location data from the company profile service
                $enhancedData = $this->companyProfileService->geoCoding($geolocationDTO);
                
                // Update location data with enhanced information
                $locationData['clock_in'] = array_merge(
                    $attendance->clock_in_location,
                    [
                        'address' => $enhancedData['address'] ?? $attendance->clock_in_location['address'] ?? null,
                        'city' => $enhancedData['city'] ?? null,
                        'state' => $enhancedData['state'] ?? null,
                        'country' => $enhancedData['country'] ?? null,
                        'enhanced' => true
                    ]
                );
                
                // Update attendance with enhanced data
                $this->updateAttendanceLocation($attendance, $locationData['clock_in'], 'clock_in_location');
                
            } catch (\Exception $e) {
                Log::error('Failed to enhance clock in location data', [
                    'attendance_id' => $attendance->id,
                    'error' => $e->getMessage()
                ]);
                
                $locationData['clock_in'] = $attendance->clock_in_location;
            }
        }
        
        // Check if we have clock out location data
        if ($attendance->clock_out_location && 
            isset($attendance->clock_out_location['latitude'], $attendance->clock_out_location['longitude'])) {
            
            try {
                // Create a GeolocationDTO for the clock out location
                $geolocationDTO = new GeolocationDTO(
                    $attendance->clock_out_location['latitude'],
                    $attendance->clock_out_location['longitude']
                );
                
                // Get enhanced location data from the company profile service
                $enhancedData = $this->companyProfileService->geoCoding($geolocationDTO);
                
                // Update location data with enhanced information
                $locationData['clock_out'] = array_merge(
                    $attendance->clock_out_location,
                    [
                        'address' => $enhancedData['address'] ?? $attendance->clock_out_location['address'] ?? null,
                        'city' => $enhancedData['city'] ?? null,
                        'state' => $enhancedData['state'] ?? null,
                        'country' => $enhancedData['country'] ?? null,
                        'enhanced' => true
                    ]
                );
                
                // Update attendance with enhanced data
                $this->updateAttendanceLocation($attendance, $locationData['clock_out'], 'clock_out_location');
                
            } catch (\Exception $e) {
                Log::error('Failed to enhance clock out location data', [
                    'attendance_id' => $attendance->id,
                    'error' => $e->getMessage()
                ]);
                
                $locationData['clock_out'] = $attendance->clock_out_location;
            }
        }
        
        return $locationData;
    }
    
    /**
     * Calculate the distance between a location and company office/branch
     *
     * @param array $location Location data with latitude and longitude
     * @param array $branchLocation Branch location data with latitude and longitude
     * @return float Distance in meters
     */
    public function calculateDistanceToOffice(array $location, array $branchLocation): float
    {
        if (!isset($location['latitude'], $location['longitude'], 
                  $branchLocation['latitude'], $branchLocation['longitude'])) {
            return PHP_FLOAT_MAX;
        }
        
        return $this->calculateDistance(
            $location['latitude'],
            $location['longitude'],
            $branchLocation['latitude'],
            $branchLocation['longitude']
        );
    }
    
    /**
     * Update attendance location data with enhanced information
     *
     * @param Attendance $attendance The attendance record to update
     * @param array $enhancedLocation The enhanced location data
     * @param string $field The field to update (clock_in_location or clock_out_location)
     * @return bool Success status
     */
    protected function updateAttendanceLocation(Attendance $attendance, array $enhancedLocation, string $field): bool
    {
        try {
            $attendance->$field = $enhancedLocation;
            $attendance->save();
            
            // If we're updating clock_in_location, also add to tracking points
            if ($field === 'clock_in_location') {
                $this->locationTrackingService->addTrackingPoints($attendance, [
                    [
                        'latitude' => $enhancedLocation['latitude'],
                        'longitude' => $enhancedLocation['longitude'],
                        'address' => $enhancedLocation['address'] ?? null,
                        'timestamp' => $attendance->clock_in_time->toDateTimeString(),
                        'type' => 'clock_in',
                        'enhanced' => true
                    ]
                ]);
            }
            
            // If we're updating clock_out_location, also add to tracking points
            if ($field === 'clock_out_location' && $attendance->clock_out_time) {
                $this->locationTrackingService->addTrackingPoints($attendance, [
                    [
                        'latitude' => $enhancedLocation['latitude'],
                        'longitude' => $enhancedLocation['longitude'],
                        'address' => $enhancedLocation['address'] ?? null,
                        'timestamp' => $attendance->clock_out_time->toDateTimeString(),
                        'type' => 'clock_out',
                        'enhanced' => true
                    ]
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update attendance location', [
                'attendance_id' => $attendance->id,
                'field' => $field,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Calculate the distance between two geographic coordinates using Haversine formula
     *
     * @param float $lat1 First latitude point
     * @param float $lon1 First longitude point
     * @param float $lat2 Second latitude point
     * @param float $lon2 Second longitude point
     * @return float Distance in meters
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // Convert degrees to radians
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);
        
        // Haversine formula
        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;
        $a = sin($dLat / 2) * sin($dLat / 2) + cos($lat1) * cos($lat2) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        // Earth radius in meters
        $radius = 6371000;
        
        // Calculate distance in meters
        return $radius * $c;
    }
}
