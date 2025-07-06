<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\DTO\ClockInDTO;
use Modules\Attendance\DTO\ClockOutDTO;
use Modules\Attendance\DTO\GeolocationDTO;
use Carbon\Carbon;

/**
 * Mobile-optimized attendance service that provides specialized 
 * methods for mobile applications with reduced payload sizes 
 * and optimized battery/data usage
 */
class MobileAttendanceService
{
    /**
     * @var AttendanceService
     */
    protected $attendanceService;
    
    /**
     * @var LocationEnhancementService
     */
    protected $locationEnhancementService;
    
    /**
     * @var int Maximum location accuracy in meters (higher values save battery)
     */
    protected $maxAccuracy;
    
    /**
     * Constructor
     */
    public function __construct(
        AttendanceService $attendanceService, 
        LocationEnhancementService $locationEnhancementService
    ) {
        $this->attendanceService = $attendanceService;
        $this->locationEnhancementService = $locationEnhancementService;
        $this->maxAccuracy = config('attendance.mobile.max_location_accuracy', 50); // meters
    }
    
    /**
     * Mobile-optimized clock in that handles intermittent connectivity
     * and reduces battery usage by optimizing location requests
     *
     * @param ClockInDTO $clockInDTO
     * @param array $options Mobile-specific options
     * @return array Response with attendance data and sync status
     */
    public function mobileClockIn(ClockInDTO $clockInDTO, array $options = []): array
    {
        // Use current timestamp if offline mode
        $isOfflineMode = $options['offline_mode'] ?? false;
        if ($isOfflineMode && !$clockInDTO->getClockInTime()) {
            $clockInDTO->setClockInTime(Carbon::now());
        }
        
        // Optimize location accuracy to save battery if requested
        if (isset($options['optimize_battery']) && $options['optimize_battery']) {
            $this->optimizeLocationAccuracy($clockInDTO);
        }
        
        try {
            // Perform the clock-in operation
            $attendance = $this->attendanceService->clockIn($clockInDTO);
            
            // Return minimal payload for mobile
            return [
                'success' => true,
                'attendance_id' => $attendance->id,
                'clock_in_time' => $attendance->clock_in_time->toIso8601String(),
                'sync_status' => 'synced',
                'server_time' => Carbon::now()->toIso8601String()
            ];
        } catch (\Exception $e) {
            // For offline mode, store locally and sync later
            if ($isOfflineMode) {
                return [
                    'success' => true,
                    'attendance_id' => 'local_' . time(),
                    'clock_in_time' => $clockInDTO->getClockInTime(),
                    'sync_status' => 'pending',
                    'server_time' => Carbon::now()->toIso8601String(),
                    'retry_after' => 60, // seconds to wait before retry
                ];
            }
            
            throw $e;
        }
    }
    
    /**
     * Mobile-optimized clock out with offline support
     *
     * @param ClockOutDTO $clockOutDTO
     * @param array $options Mobile-specific options
     * @return array Response with attendance data and sync status
     */
    public function mobileClockOut(ClockOutDTO $clockOutDTO, array $options = []): array
    {
        // Use current timestamp if offline mode
        $isOfflineMode = $options['offline_mode'] ?? false;
        if ($isOfflineMode && !$clockOutDTO->getClockOutTime()) {
            $clockOutDTO->setClockOutTime(Carbon::now());
        }
        
        // Optimize location accuracy to save battery if requested
        if (isset($options['optimize_battery']) && $options['optimize_battery']) {
            $this->optimizeLocationAccuracy($clockOutDTO);
        }
        
        try {
            // Perform the clock-out operation
            $attendance = $this->attendanceService->clockOut($clockOutDTO);
            
            // Return minimal payload for mobile
            return [
                'success' => true,
                'attendance_id' => $attendance->id,
                'clock_out_time' => $attendance->clock_out_time->toIso8601String(),
                'work_hours' => $attendance->work_hours,
                'sync_status' => 'synced',
                'server_time' => Carbon::now()->toIso8601String()
            ];
        } catch (\Exception $e) {
            // For offline mode, store locally and sync later
            if ($isOfflineMode) {
                return [
                    'success' => true,
                    'attendance_id' => $clockOutDTO->getAttendanceId(),
                    'clock_out_time' => $clockOutDTO->getClockOutTime(),
                    'sync_status' => 'pending',
                    'server_time' => Carbon::now()->toIso8601String(),
                    'retry_after' => 60, // seconds to wait before retry
                ];
            }
            
            throw $e;
        }
    }
    
    /**
     * Get mobile user dashboard data with minimal payload
     *
     * @param string $userId User ID
     * @param array $options Mobile-specific options
     * @return array Mobile-optimized dashboard data
     */
    public function getMobileDashboard(string $userId, array $options = []): array
    {
        $daysToLoad = $options['days'] ?? 7;
        $includeDetails = $options['include_details'] ?? false;
        
        // Get recent attendance records with minimal fields
        $recentAttendance = DB::table('attendances')
            ->select([
                'id', 
                'clock_in_time', 
                'clock_out_time',
                'work_hours',
                'status'
            ])
            ->where('user_id', $userId)
            ->where('clock_in_time', '>=', Carbon::now()->subDays($daysToLoad))
            ->orderBy('clock_in_time', 'desc')
            ->limit(10)
            ->get();
            
        // Current attendance status
        $currentAttendance = DB::table('attendances')
            ->select(['id', 'clock_in_time', 'status'])
            ->where('user_id', $userId)
            ->whereNull('clock_out_time')
            ->where('status', 'active')
            ->first();
            
        // Basic stats - optimized query
        $stats = DB::table('attendances')
            ->select(DB::raw('SUM(work_hours) as total_hours'))
            ->where('user_id', $userId)
            ->where('clock_in_time', '>=', Carbon::now()->startOfMonth())
            ->first();
            
        return [
            'is_clocked_in' => !empty($currentAttendance),
            'current_attendance' => $currentAttendance ? [
                'id' => $currentAttendance->id,
                'clock_in_time' => $currentAttendance->clock_in_time,
                'duration_minutes' => Carbon::parse($currentAttendance->clock_in_time)->diffInMinutes(Carbon::now()),
            ] : null,
            'recent_attendance' => $recentAttendance,
            'month_hours' => $stats->total_hours ?? 0,
            'last_sync' => Carbon::now()->toIso8601String(),
            'server_time' => Carbon::now()->toIso8601String(),
        ];
    }
    
    /**
     * Optimize location data to reduce battery usage
     *
     * @param ClockInDTO|ClockOutDTO $dto
     * @return void
     */
    protected function optimizeLocationAccuracy($dto): void
    {
        $location = $dto->getLocation();
        
        // Skip if no location or already processed
        if (!$location || isset($location['optimized'])) {
            return;
        }
        
        // Reduce accuracy to save battery - round coordinates to fewer decimal places
        // Each decimal place is roughly 11 meters of precision
        // 3 decimal places = ~110m precision which is sufficient for most attendance needs
        if (isset($location['accuracy']) && $location['accuracy'] > $this->maxAccuracy) {
            if (isset($location['latitude'])) {
                $location['latitude'] = round($location['latitude'], 4); // ~11m precision
            }
            
            if (isset($location['longitude'])) {
                $location['longitude'] = round($location['longitude'], 4); // ~11m precision
            }
            
            $location['optimized'] = true;
            $location['original_accuracy'] = $location['accuracy'];
            $location['accuracy'] = $this->maxAccuracy;
            
            // Update the DTO with optimized location
            if ($dto instanceof ClockInDTO) {
                $dto->setLocation($location);
            } elseif ($dto instanceof ClockOutDTO) {
                $dto->setLocation($location);
            }
        }
    }
    
    /**
     * Sync offline attendance records with the server
     *
     * @param string $userId
     * @param array $offlineRecords
     * @return array Sync results
     */
    public function syncOfflineAttendance(string $userId, array $offlineRecords): array
    {
        $results = [
            'synced' => [],
            'failed' => [],
            'duplicates' => []
        ];
        
        foreach ($offlineRecords as $record) {
            try {
                $type = $record['type'] ?? null;
                
                if ($type === 'clock_in') {
                    // Check for duplicates within time window
                    $duplicate = DB::table('attendances')
                        ->where('user_id', $userId)
                        ->where('clock_in_time', '>=', Carbon::parse($record['clock_in_time'])->subMinutes(5))
                        ->where('clock_in_time', '<=', Carbon::parse($record['clock_in_time'])->addMinutes(5))
                        ->exists();
                    
                    if ($duplicate) {
                        $results['duplicates'][] = $record;
                        continue;
                    }
                    
                    // Create clock-in DTO
                    $clockInDTO = new ClockInDTO(
                        $userId,
                        $record['company_id'],
                        $record['clock_in_time'],
                        $record['location'] ?? null,
                        $record['notes'] ?? null
                    );
                    
                    $attendance = $this->attendanceService->clockIn($clockInDTO);
                    $results['synced'][] = [
                        'local_id' => $record['id'],
                        'server_id' => $attendance->id,
                        'type' => 'clock_in'
                    ];
                    
                } elseif ($type === 'clock_out' && !empty($record['attendance_id'])) {
                    // Create clock-out DTO
                    $clockOutDTO = new ClockOutDTO(
                        $userId,
                        $record['attendance_id'],
                        $record['clock_out_time'],
                        $record['location'] ?? null,
                        $record['notes'] ?? null
                    );
                    
                    $attendance = $this->attendanceService->clockOut($clockOutDTO);
                    $results['synced'][] = [
                        'local_id' => $record['id'],
                        'server_id' => $attendance->id,
                        'type' => 'clock_out'
                    ];
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'record' => $record,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}
