<?php

declare(strict_types=1);

namespace Modules\Attendance\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Attendance\Models\Attendance;
use Ramsey\Uuid\UuidInterface;

/**
 * @property Attendance $model
 * @method Attendance findOneOrFail($id)
 * @method Attendance findOneByOrFail(array $data)
 */
class AttendanceRepository extends BaseRepository
{
    public function __construct(Attendance $model)
    {
        parent::__construct($model);
    }

    /**
     * Get attendance list with filters and pagination
     */
    public function getAttendanceList(array $filters = [], ?int $page = null, ?int $perPage = 10): array
    {
        $query = $this->model->newQuery()->with(['user', 'company']);

        // Apply filters using the filter method
        if (!empty($filters)) {
            $query->filter($filters);
        }

        $query->orderBy('clock_in_time', 'desc');

        if ($page) {
            return $this->getPaginationData($query, $page, $perPage);
        }

        return [
            'data' => $query->get(),
            'pagination' => null
        ];
    }

    /**
     * Get attendance by ID
     */
    public function getAttendance(UuidInterface $id): Attendance
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    /**
     * Create new attendance record
     */
    public function createAttendance(array $data): Attendance
    {
        return $this->create($data);
    }

    /**
     * Update attendance record
     */
    public function updateAttendance(UuidInterface $id, array $data): Attendance
    {
        $attendance = $this->getAttendance($id);
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
    public function deleteAttendance(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Get current active attendance for user
     */
    public function getCurrentAttendance(UuidInterface $userId): ?Attendance
    {
        return Attendance::with('user')
            ->where('user_id', $userId)
            ->whereDate('clock_in_time', today())
            ->whereNull('clock_out_time')
            ->first();
    }

    /**
     * Get attendance history with filters and pagination
     */
    public function getAttendanceHistory(array $filters = [], ?int $page = null, ?int $perPage = 10): array
    {
        $query = $this->model->newQuery()->with(['user', 'company']);

        // Apply filters using the filter method
        if (!empty($filters)) {
            $query->filter($filters);
        }

        $query->orderBy('clock_in_time', 'desc');

        if ($page) {
            return $this->getPaginationData($query, $page, $perPage);
        }
        return [
            'data' => $query->get(),
            'pagination' => null
        ];

    }
    /**
     * Get attendance by date range
     */
    public function getAttendanceByDateRange(UuidInterface $userId, Carbon $startDate, Carbon $endDate): Collection
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
     * Get late arrivals with filters and pagination
     */
    public function getLateArrivals(array $filters = [], ?int $page = null, ?int $perPage = 10): array
    {
        $filters['isLate'] = true;

        $query = $this->model->newQuery()->with(['user', 'company']);
        $query->filter($filters);
        $query->orderBy('clock_in_time', 'desc');

        if ($page) {
            return $this->getPaginationData($query, $page, $perPage);
        }

        return [
            'data' => $query->get(),
            'pagination' => null
        ];
    }

    /**
     * Get early departures with filters and pagination
     */
    public function getEarlyDepartures(array $filters = [], ?int $page = null, ?int $perPage = 10): array
    {
        $filters['isEarlyLeave'] = true;

        $query = $this->model->newQuery()->with(['user', 'company']);
        $query->filter($filters);
        $query->orderBy('clock_in_time', 'desc');

        if ($page) {
            return $this->getPaginationData($query, $page, $perPage);
        }

        return [
            'data' => $query->get(),
            'pagination' => null
        ];
    }

    /**
     * Get overtime records with filters and pagination
     */
    public function getOvertimeRecords(array $filters = [], ?int $page = null, ?int $perPage = 10): array
    {
        $filters['overtimeHoursFrom'] = 0.01; // Greater than 0

        $query = $this->model->newQuery()->with(['user', 'company']);
        $query->filter($filters);
        $query->orderBy('overtime_hours', 'desc');

        if ($page) {
            return $this->getPaginationData($query, $page, $perPage);
        }

        return [
            'data' => $query->get(),
            'pagination' => null
        ];
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

    /**
     * Helper method to get pagination data
     */
    private function getPaginationData($query, int $page, int $perPage): array
    {
        $count = $query->count();
        $data = $query->forPage($page, $perPage)->get();
        $pagination = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'data' => $data,
            'pagination' => $pagination['pagination'],
        ];
    }
}
