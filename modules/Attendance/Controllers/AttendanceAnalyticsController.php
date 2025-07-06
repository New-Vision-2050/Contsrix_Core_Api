<?php

namespace Modules\Attendance\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Attendance\Services\AttendanceAnalyticsService;
use App\Http\Controllers\Traits\ApiResponseTrait;
use Carbon\Carbon;

class AttendanceAnalyticsController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var AttendanceAnalyticsService
     */
    protected $analyticsService;

    /**
     * Constructor
     */
    public function __construct(AttendanceAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get company-wide attendance analytics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCompanyAnalytics(Request $request): JsonResponse
    {
        // Validate request
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'department_id' => 'nullable|string|exists:departments,id',
            'branch_id' => 'nullable|string|exists:branches,id',
        ]);

        $user = auth()->user();
        $companyId = $user->company_id;
        
        // Extract filters
        $filters = [
            'start_date' => $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
            'end_date' => $request->input('end_date', Carbon::now()->format('Y-m-d')),
            'department_id' => $request->input('department_id'),
            'branch_id' => $request->input('branch_id'),
        ];
        
        // Get analytics data
        $analyticsData = $this->analyticsService->getCompanyAttendanceSummary($companyId, $filters);
        
        return $this->respondSuccess([
            'data' => $analyticsData,
            'filters' => $filters
        ]);
    }

    /**
     * Get department-level analytics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDepartmentAnalytics(Request $request): JsonResponse
    {
        // Validate request
        $request->validate([
            'department_id' => 'required|string|exists:departments,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $user = auth()->user();
        $companyId = $user->company_id;
        
        // Extract filters
        $filters = [
            'start_date' => $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
            'end_date' => $request->input('end_date', Carbon::now()->format('Y-m-d')),
            'department_id' => $request->input('department_id'),
        ];
        
        // Get analytics data
        $analyticsData = $this->analyticsService->getCompanyAttendanceSummary($companyId, $filters);
        
        return $this->respondSuccess([
            'data' => $analyticsData,
            'department_id' => $request->input('department_id'),
            'filters' => $filters
        ]);
    }

    /**
     * Get user-specific attendance analytics
     *
     * @param Request $request
     * @param string $userId
     * @return JsonResponse
     */
    public function getUserAnalytics(Request $request, string $userId): JsonResponse
    {
        // Validate request
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Ensure current user has permission to view this user's data
        $currentUser = auth()->user();
        if ($currentUser->id !== $userId && !$currentUser->hasPermission('view_user_attendance')) {
            return $this->respondUnauthorized('You do not have permission to view this user\'s attendance data');
        }
        
        // Extract filters
        $filters = [
            'start_date' => $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
            'end_date' => $request->input('end_date', Carbon::now()->format('Y-m-d')),
        ];
        
        // Get user analytics data
        $userData = $this->analyticsService->getUserAttendanceStats($userId, $filters);
        
        return $this->respondSuccess([
            'data' => $userData,
            'user_id' => $userId,
            'filters' => $filters
        ]);
    }

    /**
     * Get location-based attendance analytics with geocoding insights
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLocationAnalytics(Request $request): JsonResponse
    {
        // Validate request
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'branch_id' => 'nullable|string|exists:branches,id',
            'radius' => 'nullable|numeric|min:0.1|max:50', // radius in kilometers
        ]);

        $user = auth()->user();
        $companyId = $user->company_id;
        
        // Extract filters
        $filters = [
            'start_date' => $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
            'end_date' => $request->input('end_date', Carbon::now()->format('Y-m-d')),
            'branch_id' => $request->input('branch_id'),
            'radius' => $request->input('radius', 1.0), // Default 1km radius
        ];
        
        // Get location data from attendance records
        $locationData = $this->getLocationBasedAttendance($companyId, $filters);
        
        return $this->respondSuccess([
            'data' => $locationData,
            'filters' => $filters
        ]);
    }

    /**
     * Get violation analytics and trends
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getViolationAnalytics(Request $request): JsonResponse
    {
        // Validate request
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'department_id' => 'nullable|string|exists:departments,id',
            'branch_id' => 'nullable|string|exists:branches,id',
        ]);

        $user = auth()->user();
        $companyId = $user->company_id;
        
        // Extract filters
        $filters = [
            'start_date' => $request->input('start_date', Carbon::now()->subMonths(3)->format('Y-m-d')),
            'end_date' => $request->input('end_date', Carbon::now()->format('Y-m-d')),
            'department_id' => $request->input('department_id'),
            'branch_id' => $request->input('branch_id'),
        ];
        
        // Get violation statistics
        $violationData = $this->getViolationStatistics($companyId, $filters);
        
        return $this->respondSuccess([
            'data' => $violationData,
            'filters' => $filters
        ]);
    }

    /**
     * Gather location-based attendance data
     *
     * @param string $companyId
     * @param array $filters
     * @return array
     */
    protected function getLocationBasedAttendance(string $companyId, array $filters): array
    {
        $startDate = isset($filters['start_date']) 
            ? Carbon::parse($filters['start_date']) 
            : Carbon::now()->startOfMonth();
            
        $endDate = isset($filters['end_date']) 
            ? Carbon::parse($filters['end_date']) 
            : Carbon::now();
            
        $branchId = $filters['branch_id'] ?? null;
        $radius = $filters['radius'] ?? 1.0;
        
        // Base query to get location data
        $query = \DB::table('attendances')
            ->select([
                'attendances.id',
                'attendances.user_id',
                'users.name as user_name',
                'attendances.clock_in_time',
                'attendances.clock_in_location->latitude as latitude',
                'attendances.clock_in_location->longitude as longitude',
                'attendances.clock_in_location->address as address',
                'attendances.clock_in_location->city as city',
                'attendances.clock_in_location->state as state',
                'attendances.clock_in_location->country as country',
                \DB::raw('COUNT(*) OVER (PARTITION BY attendances.clock_in_location->city, attendances.clock_in_location->state) as location_frequency')
            ])
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->where('attendances.company_id', $companyId)
            ->whereBetween('attendances.clock_in_time', [$startDate, $endDate])
            ->whereNotNull('attendances.clock_in_location->latitude')
            ->whereNotNull('attendances.clock_in_location->longitude');
            
        // If branch is specified, filter records near that branch
        if ($branchId) {
            // Get branch coordinates
            $branch = \DB::table('company_branches')
                ->select(['latitude', 'longitude'])
                ->where('id', $branchId)
                ->first();
                
            if ($branch && $branch->latitude && $branch->longitude) {
                // Use Haversine formula to calculate distance
                $query->selectRaw(
                    '(6371 * acos(cos(radians(?)) * cos(radians(JSON_UNQUOTE(JSON_EXTRACT(attendances.clock_in_location, "$.latitude")))) * 
                    cos(radians(JSON_UNQUOTE(JSON_EXTRACT(attendances.clock_in_location, "$.longitude"))) - radians(?)) + 
                    sin(radians(?)) * sin(radians(JSON_UNQUOTE(JSON_EXTRACT(attendances.clock_in_location, "$.latitude")))))) AS distance',
                    [$branch->latitude, $branch->longitude, $branch->latitude]
                )
                ->havingRaw('distance <= ?', [$radius]);
            }
        }
        
        $results = $query->limit(1000)->get();
        
        // Group by location for heatmap
        $locationGroups = [];
        foreach ($results as $record) {
            $key = "{$record->latitude}:{$record->longitude}";
            
            if (!isset($locationGroups[$key])) {
                $locationGroups[$key] = [
                    'latitude' => $record->latitude,
                    'longitude' => $record->longitude,
                    'address' => $record->address,
                    'city' => $record->city,
                    'state' => $record->state,
                    'country' => $record->country,
                    'count' => 1
                ];
            } else {
                $locationGroups[$key]['count']++;
            }
        }
        
        // Format for response
        $locationData = [
            'total_records' => count($results),
            'heatmap_data' => array_values($locationGroups),
            'top_locations' => $this->getTopLocations($companyId, $startDate, $endDate),
            'location_accuracy' => $this->getLocationAccuracyStats($companyId, $startDate, $endDate),
        ];
        
        return $locationData;
    }
    
    /**
     * Get top locations by frequency
     *
     * @param string $companyId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getTopLocations(string $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $topLocations = \DB::table('attendances')
            ->select([
                'attendances.clock_in_location->city as city',
                'attendances.clock_in_location->state as state',
                'attendances.clock_in_location->country as country',
                \DB::raw('COUNT(*) as count')
            ])
            ->where('attendances.company_id', $companyId)
            ->whereBetween('attendances.clock_in_time', [$startDate, $endDate])
            ->whereNotNull('attendances.clock_in_location->city')
            ->groupBy('city', 'state', 'country')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
            
        return $topLocations->toArray();
    }
    
    /**
     * Get location accuracy statistics
     *
     * @param string $companyId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getLocationAccuracyStats(string $companyId, Carbon $startDate, Carbon $endDate): array
    {
        // Count records with enhanced location data
        $totalRecords = \DB::table('attendances')
            ->where('company_id', $companyId)
            ->whereBetween('clock_in_time', [$startDate, $endDate])
            ->count();
            
        $enhancedRecords = \DB::table('attendances')
            ->where('company_id', $companyId)
            ->whereBetween('clock_in_time', [$startDate, $endDate])
            ->whereNotNull('clock_in_location->enhanced')
            ->where('clock_in_location->enhanced', true)
            ->count();
            
        $accurateAddressRecords = \DB::table('attendances')
            ->where('company_id', $companyId)
            ->whereBetween('clock_in_time', [$startDate, $endDate])
            ->whereNotNull('clock_in_location->address')
            ->count();
            
        return [
            'total_records' => $totalRecords,
            'enhanced_records' => $enhancedRecords,
            'address_records' => $accurateAddressRecords,
            'enhancement_rate' => $totalRecords > 0 ? ($enhancedRecords / $totalRecords) * 100 : 0,
            'address_resolution_rate' => $totalRecords > 0 ? ($accurateAddressRecords / $totalRecords) * 100 : 0,
        ];
    }
    
    /**
     * Get violation statistics
     *
     * @param string $companyId
     * @param array $filters
     * @return array
     */
    protected function getViolationStatistics(string $companyId, array $filters): array
    {
        $startDate = isset($filters['start_date']) 
            ? Carbon::parse($filters['start_date']) 
            : Carbon::now()->subMonths(3);
            
        $endDate = isset($filters['end_date']) 
            ? Carbon::parse($filters['end_date']) 
            : Carbon::now();
            
        $departmentId = $filters['department_id'] ?? null;
        $branchId = $filters['branch_id'] ?? null;
        
        // Get violations by type
        $violationsByType = $this->getViolationsByType($companyId, $startDate, $endDate, $departmentId, $branchId);
        
        // Get violations by severity
        $violationsBySeverity = $this->getViolationsBySeverity($companyId, $startDate, $endDate, $departmentId, $branchId);
        
        // Get violations trend
        $violationsTrend = $this->getViolationsTrend($companyId, $startDate, $endDate, $departmentId, $branchId);
        
        // Get top users with violations
        $topOffenders = $this->getTopViolationUsers($companyId, $startDate, $endDate, $departmentId, $branchId);
        
        return [
            'by_type' => $violationsByType,
            'by_severity' => $violationsBySeverity,
            'trend' => $violationsTrend,
            'top_offenders' => $topOffenders,
        ];
    }
    
    /**
     * Get violations grouped by type
     *
     * @param string $companyId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string|null $departmentId
     * @param string|null $branchId
     * @return array
     */
    protected function getViolationsByType(
        string $companyId, 
        Carbon $startDate, 
        Carbon $endDate, 
        ?string $departmentId = null,
        ?string $branchId = null
    ): array {
        $query = \DB::table('attendance_constraint_violations')
            ->select([
                'attendance_constraint_violations.type',
                \DB::raw('COUNT(*) as count')
            ])
            ->join('attendances', 'attendance_constraint_violations.attendance_id', '=', 'attendances.id')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->where('attendances.company_id', $companyId)
            ->whereBetween('attendance_constraint_violations.detected_at', [$startDate, $endDate])
            ->groupBy('attendance_constraint_violations.type');
            
        if ($departmentId) {
            $query->where('users.department_id', $departmentId);
        }
        
        if ($branchId) {
            $query->where('users.branch_id', $branchId);
        }
        
        $results = $query->get();
        
        $byType = [];
        foreach ($results as $result) {
            $byType[] = [
                'type' => $result->type,
                'count' => $result->count,
            ];
        }
        
        return $byType;
    }
    
    /**
     * Get violations grouped by severity
     *
     * @param string $companyId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string|null $departmentId
     * @param string|null $branchId
     * @return array
     */
    protected function getViolationsBySeverity(
        string $companyId, 
        Carbon $startDate, 
        Carbon $endDate, 
        ?string $departmentId = null,
        ?string $branchId = null
    ): array {
        $query = \DB::table('attendance_constraint_violations')
            ->select([
                'attendance_constraint_violations.severity',
                \DB::raw('COUNT(*) as count')
            ])
            ->join('attendances', 'attendance_constraint_violations.attendance_id', '=', 'attendances.id')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->where('attendances.company_id', $companyId)
            ->whereBetween('attendance_constraint_violations.detected_at', [$startDate, $endDate])
            ->groupBy('attendance_constraint_violations.severity');
            
        if ($departmentId) {
            $query->where('users.department_id', $departmentId);
        }
        
        if ($branchId) {
            $query->where('users.branch_id', $branchId);
        }
        
        $results = $query->get();
        
        $bySeverity = [];
        foreach ($results as $result) {
            $bySeverity[] = [
                'severity' => $result->severity,
                'count' => $result->count,
            ];
        }
        
        return $bySeverity;
    }
    
    /**
     * Get violations trend over time
     *
     * @param string $companyId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string|null $departmentId
     * @param string|null $branchId
     * @return array
     */
    protected function getViolationsTrend(
        string $companyId, 
        Carbon $startDate, 
        Carbon $endDate, 
        ?string $departmentId = null,
        ?string $branchId = null
    ): array {
        // Create weekly periods
        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1W'),
            $endDate
        );
        
        $trend = [];
        foreach ($period as $weekStart) {
            $weekEnd = (clone $weekStart)->addDays(6);
            
            $query = \DB::table('attendance_constraint_violations')
                ->join('attendances', 'attendance_constraint_violations.attendance_id', '=', 'attendances.id')
                ->join('users', 'attendances.user_id', '=', 'users.id')
                ->where('attendances.company_id', $companyId)
                ->whereBetween('attendance_constraint_violations.detected_at', [$weekStart, $weekEnd]);
                
            if ($departmentId) {
                $query->where('users.department_id', $departmentId);
            }
            
            if ($branchId) {
                $query->where('users.branch_id', $branchId);
            }
            
            $count = $query->count();
            
            $trend[] = [
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'count' => $count,
            ];
        }
        
        return $trend;
    }
    
    /**
     * Get top users with violations
     *
     * @param string $companyId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string|null $departmentId
     * @param string|null $branchId
     * @return array
     */
    protected function getTopViolationUsers(
        string $companyId, 
        Carbon $startDate, 
        Carbon $endDate, 
        ?string $departmentId = null,
        ?string $branchId = null
    ): array {
        $query = \DB::table('attendance_constraint_violations')
            ->select([
                'users.id as user_id',
                'users.name as user_name',
                \DB::raw('COUNT(*) as violation_count')
            ])
            ->join('attendances', 'attendance_constraint_violations.attendance_id', '=', 'attendances.id')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->where('attendances.company_id', $companyId)
            ->whereBetween('attendance_constraint_violations.detected_at', [$startDate, $endDate])
            ->groupBy('users.id', 'users.name')
            ->orderBy('violation_count', 'desc')
            ->limit(10);
            
        if ($departmentId) {
            $query->where('users.department_id', $departmentId);
        }
        
        if ($branchId) {
            $query->where('users.branch_id', $branchId);
        }
        
        $results = $query->get();
        
        $users = [];
        foreach ($results as $result) {
            $users[] = [
                'user_id' => $result->user_id,
                'name' => $result->user_name,
                'violation_count' => $result->violation_count,
            ];
        }
        
        return $users;
    }
}
