<?php

namespace Modules\Attendance\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Attendance\Services\MobileAttendanceService;
use Modules\Attendance\Services\LocationEnhancementService;
use Modules\Attendance\Requests\MobileClockInRequest;
use Modules\Attendance\Requests\MobileClockOutRequest;
use Modules\Attendance\DTO\ClockInDTO;
use Modules\Attendance\DTO\ClockOutDTO;
use Modules\Attendance\DTO\GeolocationDTO;
use Modules\Company\CompanyCore\Services\CompanyProfileService;
use App\Http\Controllers\Traits\ApiResponseTrait;
use Carbon\Carbon;

class MobileAttendanceController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var MobileAttendanceService
     */
    protected $mobileAttendanceService;
    
    /**
     * @var LocationEnhancementService
     */
    protected $locationEnhancementService;
    
    /**
     * @var CompanyProfileService
     */
    protected $companyProfileService;

    /**
     * Constructor
     */
    public function __construct(
        MobileAttendanceService $mobileAttendanceService,
        LocationEnhancementService $locationEnhancementService,
        CompanyProfileService $companyProfileService
    ) {
        $this->mobileAttendanceService = $mobileAttendanceService;
        $this->locationEnhancementService = $locationEnhancementService;
        $this->companyProfileService = $companyProfileService;
    }

    /**
     * Mobile-optimized clock in endpoint
     *
     * @param MobileClockInRequest $request
     * @return JsonResponse
     */
    public function clockIn(MobileClockInRequest $request): JsonResponse
    {
        $user = auth()->user();
        
        // Create DTO with mobile-optimized properties
        $clockInDTO = new ClockInDTO(
            $user->id,
            $user->company_id,
            $request->input('clock_in_time', Carbon::now()),
            $request->input('location'),
            $request->input('notes')
        );
        
        // Use GeolocationDTO to enhance location data if coordinates provided
        $location = $request->input('location');
        if ($location && isset($location['latitude'], $location['longitude'])) {
            try {
                // Leverage existing OpenRouter integration via GeolocationDTO
                $geolocationDTO = new GeolocationDTO(
                    $location['latitude'], 
                    $location['longitude'], 
                    $location['address'] ?? null
                );
                
                // Use company profile service to enhance with geocoding data
                $enhancedData = $this->companyProfileService->geoCoding($geolocationDTO);
                
                // Update location data in the request with enhanced information
                $enhancedLocation = array_merge($location, [
                    'address' => $enhancedData['address'] ?? $location['address'] ?? null,
                    'city' => $enhancedData['city'] ?? null,
                    'state' => $enhancedData['state'] ?? null,
                    'country' => $enhancedData['country'] ?? null,
                    'enhanced' => true
                ]);
                
                $clockInDTO->setLocation($enhancedLocation);
                
            } catch (\Exception $e) {
                // Proceed with original location data if enhancement fails
                \Illuminate\Support\Facades\Log::info('Location enhancement failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Set mobile-specific options
        $options = [
            'offline_mode' => $request->input('offline_mode', false),
            'optimize_battery' => $request->input('optimize_battery', true),
            'low_data_mode' => $request->input('low_data_mode', false),
        ];
        
        // Perform mobile-optimized clock in
        $result = $this->mobileAttendanceService->mobileClockIn($clockInDTO, $options);
        
        return $this->respondSuccess($result, 'Clock in successful');
    }

    /**
     * Mobile-optimized clock out endpoint
     *
     * @param MobileClockOutRequest $request
     * @return JsonResponse
     */
    public function clockOut(MobileClockOutRequest $request): JsonResponse
    {
        $user = auth()->user();
        
        // Create DTO with mobile-optimized properties
        $clockOutDTO = new ClockOutDTO(
            $user->id,
            $request->input('attendance_id'),
            $request->input('clock_out_time', Carbon::now()),
            $request->input('location'),
            $request->input('notes')
        );
        
        // Use GeolocationDTO to enhance location data if coordinates provided
        $location = $request->input('location');
        if ($location && isset($location['latitude'], $location['longitude'])) {
            try {
                // Leverage existing OpenRouter integration via GeolocationDTO
                $geolocationDTO = new GeolocationDTO(
                    $location['latitude'], 
                    $location['longitude'], 
                    $location['address'] ?? null
                );
                
                // Use company profile service to enhance with geocoding data
                $enhancedData = $this->companyProfileService->geoCoding($geolocationDTO);
                
                // Update location data in the request with enhanced information
                $enhancedLocation = array_merge($location, [
                    'address' => $enhancedData['address'] ?? $location['address'] ?? null,
                    'city' => $enhancedData['city'] ?? null,
                    'state' => $enhancedData['state'] ?? null,
                    'country' => $enhancedData['country'] ?? null,
                    'enhanced' => true
                ]);
                
                $clockOutDTO->setLocation($enhancedLocation);
                
            } catch (\Exception $e) {
                // Proceed with original location data if enhancement fails
                \Illuminate\Support\Facades\Log::info('Location enhancement failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Set mobile-specific options
        $options = [
            'offline_mode' => $request->input('offline_mode', false),
            'optimize_battery' => $request->input('optimize_battery', true),
            'low_data_mode' => $request->input('low_data_mode', false),
        ];
        
        // Perform mobile-optimized clock out
        $result = $this->mobileAttendanceService->mobileClockOut($clockOutDTO, $options);
        
        return $this->respondSuccess($result, 'Clock out successful');
    }

    /**
     * Get mobile-optimized dashboard data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDashboard(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // Set mobile-specific options
        $options = [
            'days' => $request->input('days', 7),
            'include_details' => $request->input('include_details', false),
            'low_data_mode' => $request->input('low_data_mode', false),
        ];
        
        $dashboardData = $this->mobileAttendanceService->getMobileDashboard($user->id, $options);
        
        return $this->respondSuccess($dashboardData);
    }

    /**
     * Sync offline attendance records
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function syncOfflineRecords(Request $request): JsonResponse
    {
        $user = auth()->user();
        $offlineRecords = $request->input('records', []);
        
        $syncResults = $this->mobileAttendanceService->syncOfflineAttendance($user->id, $offlineRecords);
        
        return $this->respondSuccess($syncResults, 'Offline records synced');
    }
    
    /**
     * Get nearest office location based on current coordinates
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getNearestOffice(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        
        if (!$latitude || !$longitude) {
            return $this->respondError('Latitude and longitude are required', 422);
        }
        
        try {
            // Use existing geocoding functionality through CityRepository
            $geolocationDTO = new GeolocationDTO($latitude, $longitude);
            
            // Get location data from geocoding service
            $locationData = $this->companyProfileService->geoCoding($geolocationDTO);
            
            // Get nearby offices - simplified for mobile with only essential fields
            $nearbyOffices = \DB::table('company_branches')
                ->select([
                    'id', 
                    'name', 
                    'address', 
                    'latitude', 
                    'longitude',
                    \DB::raw('(
                        6371 * acos(
                            cos(radians(' . $latitude . ')) * 
                            cos(radians(latitude)) * 
                            cos(radians(longitude) - radians(' . $longitude . ')) + 
                            sin(radians(' . $latitude . ')) * 
                            sin(radians(latitude))
                        )
                    ) AS distance')
                ])
                ->where('company_id', $user->company_id)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->orderBy('distance')
                ->limit(3)
                ->get();
            
            return $this->respondSuccess([
                'current_location' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'address' => $locationData['address'] ?? null,
                    'city' => $locationData['city'] ?? null,
                    'state' => $locationData['state'] ?? null,
                ],
                'nearby_offices' => $nearbyOffices,
            ]);
            
        } catch (\Exception $e) {
            return $this->respondError('Failed to get nearest office: ' . $e->getMessage(), 500);
        }
    }
}
