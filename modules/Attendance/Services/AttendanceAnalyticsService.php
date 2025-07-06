<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraintViolation;

/**
 * Service for generating attendance analytics and reports
 */
class AttendanceAnalyticsService
{
    /**
     * Get attendance summary statistics for a company
     *
     * @param string $companyId
     * @param array $filters
     * @return array
     */
    public function getCompanyAttendanceSummary(string $companyId, array $filters = []): array
    {
        $startDate = isset($filters['start_date']) 
            ? Carbon::parse($filters['start_date']) 
            : Carbon::now()->startOfMonth();
            
        $endDate = isset($filters['end_date']) 
            ? Carbon::parse($filters['end_date']) 
            : Carbon::now();
            
        $departmentId = $filters['department_id'] ?? null;
        $branchId = $filters['branch_id'] ?? null;
        
        // Base query
        $query = DB::table('attendances')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->where('attendances.company_id', $companyId)
            ->whereBetween('attendances.clock_in_time', [$startDate, $endDate]);
            
        // Apply filters
        if ($departmentId) {
            $query->where('users.department_id', $departmentId);
        }
        
        if ($branchId) {
            $query->where('users.branch_id', $branchId);
        }
        
        // Get summary stats
        $summary = [
            'total_attendance_records' => (clone $query)->count(),
            'total_work_hours' => (clone $query)->sum('attendances.work_hours'),
            'average_work_hours' => (clone $query)->avg('attendances.work_hours'),
            'on_time_percentage' => $this->calculateOnTimePercentage($companyId, $startDate, $endDate, $filters),
            'attendance_by_day' => $this->getAttendanceByDay($companyId, $startDate, $endDate, $filters),
            'violation_summary' => $this->getViolationSummary($companyId, $startDate, $endDate, $filters),
            'top_performers' => $this->getTopPerformers($companyId, $startDate, $endDate, $filters),
        ];
        
        // Add trends
        $summary['trends'] = $this->calculateTrends($companyId, $startDate, $endDate, $filters);
        
        return $summary;
    }
    
    /**
     * Get attendance statistics for a specific user
     *
     * @param string $userId
     * @param array $filters
     * @return array
     */
    public function getUserAttendanceStats(string $userId, array $filters = []): array
    {
        $startDate = isset($filters['start_date']) 
            ? Carbon::parse($filters['start_date']) 
            : Carbon::now()->startOfMonth();
            
        $endDate = isset($filters['end_date']) 
            ? Carbon::parse($filters['end_date']) 
            : Carbon::now();
            
        // Base query for this user's attendance
        $query = DB::table('attendances')
            ->where('user_id', $userId)
            ->whereBetween('clock_in_time', [$startDate, $endDate]);
            
        // Calculate stats
        $stats = [
            'total_attendance_days' => (clone $query)->distinct('DATE(clock_in_time)')->count(),
            'total_work_hours' => (clone $query)->sum('work_hours'),
            'average_daily_hours' => (clone $query)->avg('work_hours'),
            'on_time_percentage' => $this->calculateUserOnTimePercentage($userId, $startDate, $endDate),
            'violations_count' => $this->getUserViolationsCount($userId, $startDate, $endDate),
            'attendance_trend' => $this->getUserAttendanceTrend($userId, $startDate, $endDate),
        ];
        
        return $stats;
    }
    
    /**
     * Calculate on-time percentage for a company
     *
     * @param string $companyId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters
     * @return float
     */
    protected function calculateOnTimePercentage(
        string $companyId, 
        Carbon $startDate, 
        Carbon $endDate, 
        array $filters = []
    ): float {
        $departmentId = $filters['department_id'] ?? null;
        $branchId = $filters['branch_id'] ?? null;
        
        $baseQuery = DB::table('attendances')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->where('attendances.company_id', $companyId)
            ->whereBetween('attendances.clock_in_time', [$startDate, $endDate]);
            
        if ($departmentId) {
            $baseQuery->where('users.department_id', $departmentId);
        }
        
        if ($branchId) {
            $baseQuery->where('users.branch_id', $branchId);
        }
        
        $totalRecords = (clone $baseQuery)->count();
        
        if ($totalRecords === 0) {
            return 0;
        }
        
        // Get violations related to late attendance
        $lateCount = (clone $baseQuery)
            ->join('attendance_constraint_violations', function ($join) {
                $join->on('attendances.id', '=', 'attendance_constraint_violations.attendance_id')
                    ->where('attendance_constraint_violations.type', 'time')
                    ->where('attendance_constraint_violations.details->violation_type', 'late_arrival');
            })
            ->count();
            
        return $totalRecords > 0 ? 
            (($totalRecords - $lateCount) / $totalRecords) * 100 : 0;
    }
    
    /**
     * Get attendance by day of the week
     *
     * @param string $companyId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters
     * @return array
     */
    protected function getAttendanceByDay(
        string $companyId, 
        Carbon $startDate, 
        Carbon $endDate, 
        array $filters = []
    ): array {
        $departmentId = $filters['department_id'] ?? null;
        $branchId = $filters['branch_id'] ?? null;
        
        $query = DB::table('attendances')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->select(DB::raw('DAYOFWEEK(clock_in_time) as day_num'), DB::raw('COUNT(*) as count'))
            ->where('attendances.company_id', $companyId)
            ->whereBetween('attendances.clock_in_time', [$startDate, $endDate])
            ->groupBy(DB::raw('DAYOFWEEK(clock_in_time)'));
            
        if ($departmentId) {
            $query->where('users.department_id', $departmentId);
        }
        
        if ($branchId) {
            $query->where('users.branch_id', $branchId);
        }
        
        $results = $query->get();
        
        // Format into day names
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        $byDay = [];
        foreach ($dayNames as $index => $name) {
            $dayNum = $index + 1;
            $count = 0;
            
            foreach ($results as $result) {
                if ($result->day_num == $dayNum) {
                    $count = $result->count;
                    break;
                }
            }
            
            $byDay[] = [
                'day' => $name,
                'count' => $count,
            ];
        }
        
        return $byDay;
    }
    
    /**
     * Get violation summary
     *
     * @param string $companyId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters
     * @return array
     */
    protected function getViolationSummary(
        string $companyId, 
        Carbon $startDate, 
        Carbon $endDate, 
        array $filters = []
    ): array {
        $departmentId = $filters['department_id'] ?? null;
        $branchId = $filters['branch_id'] ?? null;
        
        $query = DB::table('attendance_constraint_violations')
            ->join('attendances', 'attendance_constraint_violations.attendance_id', '=', 'attendances.id')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->select('attendance_constraint_violations.type', DB::raw('COUNT(*) as count'))
            ->where('attendances.company_id', $companyId)
            ->whereBetween('attendances.clock_in_time', [$startDate, $endDate])
            ->groupBy('attendance_constraint_violations.type');
            
        if ($departmentId) {
            $query->where('users.department_id', $departmentId);
        }
        
        if ($branchId) {
            $query->where('users.branch_id', $branchId);
        }
        
        $results = $query->get();
        
        $violationSummary = [];
        foreach ($results as $result) {
            $violationSummary[] = [
                'type' => $result->type,
                'count' => $result->count,
            ];
        }
        
        return $violationSummary;
    }
    
    /**
     * Get top performers based on attendance metrics
     *
     * @param string $companyId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters
     * @return array
     */
    protected function getTopPerformers(
        string $companyId, 
        Carbon $startDate, 
        Carbon $endDate, 
        array $filters = []
    ): array {
        $departmentId = $filters['department_id'] ?? null;
        $branchId = $filters['branch_id'] ?? null;
        $limit = $filters['limit'] ?? 5;
        
        $query = DB::table('attendances')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->select(
                'users.id', 
                'users.name', 
                DB::raw('SUM(attendances.work_hours) as total_hours'),
                DB::raw('COUNT(DISTINCT DATE(attendances.clock_in_time)) as days_present'),
                DB::raw('COUNT(DISTINCT attendances.id) as attendance_count')
            )
            ->where('attendances.company_id', $companyId)
            ->whereBetween('attendances.clock_in_time', [$startDate, $endDate])
            ->groupBy('users.id', 'users.name')
            ->orderBy('total_hours', 'desc')
            ->limit($limit);
            
        if ($departmentId) {
            $query->where('users.department_id', $departmentId);
        }
        
        if ($branchId) {
            $query->where('users.branch_id', $branchId);
        }
        
        $performers = $query->get();
        
        $result = [];
        foreach ($performers as $performer) {
            $result[] = [
                'user_id' => $performer->id,
                'name' => $performer->name,
                'total_hours' => $performer->total_hours,
                'days_present' => $performer->days_present,
                'attendance_count' => $performer->attendance_count,
            ];
        }
        
        return $result;
    }
    
    /**
     * Calculate attendance trends
     *
     * @param string $companyId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters
     * @return array
     */
    protected function calculateTrends(
        string $companyId, 
        Carbon $startDate, 
        Carbon $endDate, 
        array $filters = []
    ): array {
        $departmentId = $filters['department_id'] ?? null;
        $branchId = $filters['branch_id'] ?? null;
        
        // Get weekly data
        $period = CarbonPeriod::create($startDate, '1 week', $endDate);
        
        $weeklyData = [];
        foreach ($period as $date) {
            $weekStart = (clone $date)->startOfWeek();
            $weekEnd = (clone $date)->endOfWeek();
            
            $query = DB::table('attendances')
                ->join('users', 'attendances.user_id', '=', 'users.id')
                ->where('attendances.company_id', $companyId)
                ->whereBetween('attendances.clock_in_time', [$weekStart, $weekEnd]);
                
            if ($departmentId) {
                $query->where('users.department_id', $departmentId);
            }
            
            if ($branchId) {
                $query->where('users.branch_id', $branchId);
            }
            
            $weeklyData[] = [
                'week' => $weekStart->format('Y-m-d'),
                'average_hours' => (clone $query)->avg('attendances.work_hours') ?? 0,
                'total_hours' => (clone $query)->sum('attendances.work_hours') ?? 0,
                'attendance_count' => (clone $query)->count(),
                'violation_count' => $this->getViolationCountForPeriod(
                    $companyId, 
                    $weekStart, 
                    $weekEnd, 
                    $filters
                ),
            ];
        }
        
        return [
            'weekly' => $weeklyData,
            'trend_direction' => $this->analyzeTrendDirection($weeklyData),
        ];
    }
    
    /**
     * Get violation count for a specific period
     *
     * @param string $companyId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters
     * @return int
     */
    protected function getViolationCountForPeriod(
        string $companyId, 
        Carbon $startDate, 
        Carbon $endDate, 
        array $filters = []
    ): int {
        $departmentId = $filters['department_id'] ?? null;
        $branchId = $filters['branch_id'] ?? null;
        
        $query = DB::table('attendance_constraint_violations')
            ->join('attendances', 'attendance_constraint_violations.attendance_id', '=', 'attendances.id')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->where('attendances.company_id', $companyId)
            ->whereBetween('attendance_constraint_violations.detected_at', [$startDate, $endDate]);
            
        if ($departmentId) {
            $query->where('users.department_id', $departmentId);
        }
        
        if ($branchId) {
            $query->where('users.branch_id', $branchId);
        }
        
        return $query->count();
    }
    
    /**
     * Calculate on-time percentage for a specific user
     *
     * @param string $userId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    protected function calculateUserOnTimePercentage(
        string $userId, 
        Carbon $startDate, 
        Carbon $endDate
    ): float {
        $totalRecords = DB::table('attendances')
            ->where('user_id', $userId)
            ->whereBetween('clock_in_time', [$startDate, $endDate])
            ->count();
            
        if ($totalRecords === 0) {
            return 0;
        }
        
        $lateCount = DB::table('attendances')
            ->join('attendance_constraint_violations', function ($join) {
                $join->on('attendances.id', '=', 'attendance_constraint_violations.attendance_id')
                    ->where('attendance_constraint_violations.type', 'time')
                    ->where('attendance_constraint_violations.details->violation_type', 'late_arrival');
            })
            ->where('attendances.user_id', $userId)
            ->whereBetween('attendances.clock_in_time', [$startDate, $endDate])
            ->count();
            
        return ($totalRecords - $lateCount) / $totalRecords * 100;
    }
    
    /**
     * Get violation count for a specific user
     *
     * @param string $userId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getUserViolationsCount(
        string $userId, 
        Carbon $startDate, 
        Carbon $endDate
    ): array {
        $violations = DB::table('attendance_constraint_violations')
            ->join('attendances', 'attendance_constraint_violations.attendance_id', '=', 'attendances.id')
            ->select('attendance_constraint_violations.type', DB::raw('COUNT(*) as count'))
            ->where('attendances.user_id', $userId)
            ->whereBetween('attendances.clock_in_time', [$startDate, $endDate])
            ->groupBy('attendance_constraint_violations.type')
            ->get();
            
        $result = [];
        foreach ($violations as $violation) {
            $result[] = [
                'type' => $violation->type,
                'count' => $violation->count,
            ];
        }
        
        return $result;
    }
    
    /**
     * Get attendance trend for a specific user
     *
     * @param string $userId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getUserAttendanceTrend(
        string $userId, 
        Carbon $startDate, 
        Carbon $endDate
    ): array {
        // Group data by week
        $weeklyData = DB::table('attendances')
            ->select(
                DB::raw('YEARWEEK(clock_in_time) as year_week'),
                DB::raw('MIN(DATE(clock_in_time)) as week_start'),
                DB::raw('SUM(work_hours) as total_hours'),
                DB::raw('COUNT(*) as attendance_count')
            )
            ->where('user_id', $userId)
            ->whereBetween('clock_in_time', [$startDate, $endDate])
            ->groupBy(DB::raw('YEARWEEK(clock_in_time)'))
            ->orderBy('year_week')
            ->get();
            
        $result = [];
        foreach ($weeklyData as $week) {
            $result[] = [
                'week' => $week->week_start,
                'total_hours' => $week->total_hours,
                'attendance_count' => $week->attendance_count,
            ];
        }
        
        return $result;
    }
    
    /**
     * Analyze trend direction from weekly data
     *
     * @param array $weeklyData
     * @return string
     */
    protected function analyzeTrendDirection(array $weeklyData): string
    {
        if (empty($weeklyData) || count($weeklyData) < 2) {
            return 'stable';
        }
        
        $firstWeek = $weeklyData[0]['average_hours'] ?? 0;
        $lastWeek = $weeklyData[count($weeklyData) - 1]['average_hours'] ?? 0;
        
        $difference = $lastWeek - $firstWeek;
        
        if ($difference > 0.5) {
            return 'increasing';
        } elseif ($difference < -0.5) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }
}
