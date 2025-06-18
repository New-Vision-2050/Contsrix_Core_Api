<?php

declare(strict_types=1);

namespace Modules\Attendance\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Attendance\Models\Attendance;

class AttendanceRepository
{
    /**
     * Create new attendance record
     */
    public function create(array $data): Attendance
    {
        return Attendance::create($data);
    }

    /**
     * Find attendance by ID
     */
    public function findById(string $id): ?Attendance
    {
        return Attendance::find($id);
    }

    /**
     * Update attendance record
     */
    public function update(string $id, array $data): Attendance
    {
        $attendance = $this->findById($id);
        $attendance->update($data);
        
        // Recalculate work hours if clock times are updated
        if (isset($data['clock_out_time']) || isset($data['break_end_time'])) {
            $attendance->calculateWorkHours();
            $attendance->save();
        }
        
        return $attendance->fresh();
    }

    /**
     * Delete attendance record
     */
    public function delete(string $id): bool
    {
        $attendance = $this->findById($id);
        return $attendance ? $attendance->delete() : false;
    }

    /**
     * Get current active attendance for user
     */
    public function getCurrentAttendance(string $userId): ?Attendance
    {
        return Attendance::where('user_id', $userId)
            ->whereDate('clock_in_time', today())
            ->whereNull('clock_out_time')
            ->first();
    }

    /**
     * Get attendance history with filters
     */
    public function getAttendanceHistory(array $filters): Collection
    {
        $query = Attendance::query()->with(['user', 'company']);

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('clock_in_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('clock_in_time', '<=', $filters['end_date']);
        }

        if (isset($filters['is_late'])) {
            $query->where('is_late', $filters['is_late']);
        }

        if (isset($filters['is_early_departure'])) {
            $query->where('is_early_departure', $filters['is_early_departure']);
        }

        // Default ordering
        $query->orderBy('clock_in_time', 'desc');

        // Apply pagination if specified
        if (isset($filters['per_page'])) {
            return $query->paginate($filters['per_page']);
        }

        return $query->get();
    }

    /**
     * Get attendance by date range
     */
    public function getAttendanceByDateRange(string $userId, Carbon $startDate, Carbon $endDate): Collection
    {
        return Attendance::where('user_id', $userId)
            ->whereBetween('clock_in_time', [$startDate, $endDate])
            ->orderBy('clock_in_time', 'desc')
            ->get();
    }

    /**
     * Get attendance for specific date
     */
    public function getAttendanceByDate(string $userId, Carbon $date): ?Attendance
    {
        return Attendance::where('user_id', $userId)
            ->whereDate('clock_in_time', $date)
            ->first();
    }

    /**
     * Get late arrivals
     */
    public function getLateArrivals(array $filters = []): Collection
    {
        $query = Attendance::where('is_late', true)->with(['user']);

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('clock_in_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('clock_in_time', '<=', $filters['end_date']);
        }

        return $query->orderBy('clock_in_time', 'desc')->get();
    }

    /**
     * Get early departures
     */
    public function getEarlyDepartures(array $filters = []): Collection
    {
        $query = Attendance::where('is_early_departure', true)->with(['user']);

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('clock_in_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('clock_in_time', '<=', $filters['end_date']);
        }

        return $query->orderBy('clock_in_time', 'desc')->get();
    }

    /**
     * Get overtime records
     */
    public function getOvertimeRecords(array $filters = []): Collection
    {
        $query = Attendance::where('overtime_hours', '>', 0)->with(['user']);

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('clock_in_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('clock_in_time', '<=', $filters['end_date']);
        }

        return $query->orderBy('overtime_hours', 'desc')->get();
    }

    /**
     * Get attendance statistics
     */
    public function getAttendanceStats(array $filters = []): array
    {
        $query = Attendance::query();

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('clock_in_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('clock_in_time', '<=', $filters['end_date']);
        }

        $stats = [
            'total_records' => $query->count(),
            'total_work_hours' => $query->sum('total_work_hours'),
            'total_overtime_hours' => $query->sum('overtime_hours'),
            'total_break_hours' => $query->sum('total_break_hours'),
            'late_arrivals' => $query->where('is_late', true)->count(),
            'early_departures' => $query->where('is_early_departure', true)->count(),
            'approved_records' => $query->where('status', 'approved')->count(),
            'pending_records' => $query->where('status', 'pending_approval')->count(),
        ];

        return $stats;
    }

    /**
     * Get users with no attendance for a specific date
     */
    public function getUsersWithoutAttendance(Carbon $date, ?string $companyId = null): Collection
    {
        // This would require joining with users table
        // Implementation depends on your user management structure
        return collect([]);
    }

    /**
     * Bulk update attendance records
     */
    public function bulkUpdate(array $attendanceIds, array $data): int
    {
        return Attendance::whereIn('id', $attendanceIds)->update($data);
    }
}
