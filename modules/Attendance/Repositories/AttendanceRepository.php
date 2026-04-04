<?php

declare(strict_types=1);

namespace Modules\Attendance\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

    public function paginatedAttendance(Collection $collection, int $page = 1, int $perPage = 10)
    {
        $count = $collection->count();
        $results = $collection->forPage($page, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $results,
            $count,
            $perPage,
            $page,
        );
    }
    /**
     * Get attendance list with filters and pagination
     */
    public function getAttendanceList(array $filters = [], ?int $page = null, ?int $perPage = 10): array
    {
        $query = $this->newAttendanceListQuery();
        if (!empty($filters)) {
            $query->filter($filters);
        }

        $query->orderBy('clock_in_time', 'desc');

        return $this->paginateQueryResult($query, $page, $perPage);
    }
    public function getQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->model->newQuery();
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
     * Get current active attendance for user.
     *
     * @param  bool  $withUser  When false, skips eager-loading user (avoids an extra join/query when caller already has the user).
     */
    public function getCurrentAttendance(UuidInterface $userId, bool $withUser = true): ?Attendance
    {
        $query = Attendance::query()
            ->where('user_id', $userId->toString())
            ->whereNull('clock_out_time')
            ->whereNotNull('clock_in_time');

        if ($withUser) {
            $query->with('user');
        }

        // Avoid refresh(): it ran a second identical SELECT for every call.
        return $query->first();
    }

    /**
     * Get attendance history with filters and pagination
     */
    public function getAttendanceHistory(array $filters = [], ?int $page = null, ?int $perPage = 10): array
    {
        $query = $this->newAttendanceListQuery();
        if (!empty($filters)) {
            $query->filter($filters);
        }

        $query->orderBy('start_time', 'desc');

        $results = $this->paginateQueryResult($query, $page, $perPage);

        if (!empty($results['data'])) {
            $results['data'] = collect($results['data'])->groupBy(function ($item) {
                /** @var Attendance $item */
                return $this->historyGroupKey($item);
            });
        }

        return $results;
    }
    /**
     * Get attendance by date range
     */
    public function getAttendanceByDateRange(UuidInterface $userId, Carbon $startDate, Carbon $endDate): Collection
    {
        return Attendance::whereBetween('start_time', [$startDate->startOfDay(), $endDate->endOfDay()])
        // where('user_id', $userId)
            ->orderBy('start_time', 'desc')
            ->get();
    }

    /**
     * Get attendance for specific date
     */
    public function getAttendanceByDate(string $userId, Carbon $date): ?Attendance
    {
        // Convert date range to UTC for database query (database stores times in UTC)
        $timezone = getTimeZoneBranchByRequest() ?? config('app.timezone');
        $dateInTz = $date->copy()->setTimezone($timezone);
        $dayStartUtc = $dateInTz->copy()->startOfDay()->setTimezone('UTC');
        $dayEndUtc = $dateInTz->copy()->endOfDay()->setTimezone('UTC');
        
        return Attendance::where('user_id', $userId)
            ->whereBetween('clock_in_time', [$dayStartUtc, $dayEndUtc])
            ->first();
    }

    /**
     * Get late arrivals with filters and pagination
     */
    public function getLateArrivals(array $filters = [], ?int $page = null, ?int $perPage = 10): array
    {
        $filters['isLate'] = true;

        $query = $this->newAttendanceListQuery();
        $query->filter($filters);
        $query->orderBy('clock_in_time', 'desc');

        return $this->paginateQueryResult($query, $page, $perPage);
    }

    /**
     * Get early departures with filters and pagination
     */
    public function getEarlyDepartures(array $filters = [], ?int $page = null, ?int $perPage = 10): array
    {
        $filters['isEarlyLeave'] = true;

        $query = $this->newAttendanceListQuery();
        $query->filter($filters);
        $query->orderBy('clock_in_time', 'desc');

        return $this->paginateQueryResult($query, $page, $perPage);
    }

    /**
     * Get overtime records with filters and pagination
     */
    public function getOvertimeRecords(array $filters = [], ?int $page = null, ?int $perPage = 10): array
    {
        $filters['overtimeHoursFrom'] = 0.01; // Greater than 0

        $query = $this->newAttendanceListQuery();
        $query->filter($filters);
        $query->orderBy('overtime_hours', 'desc');

        return $this->paginateQueryResult($query, $page, $perPage);
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

    private function newAttendanceListQuery(): Builder
    {
        return $this->model->newQuery()->with(['user', 'company']);
    }

    /**
     * @return array{data: \Illuminate\Support\Collection|array, pagination: array|null}
     */
    private function paginateQueryResult(Builder $query, ?int $page, int $perPage): array
    {
        if ($page) {
            return $this->getPaginationData($query, $page, $perPage);
        }

        return [
            'data' => $query->get(),
            'pagination' => null,
        ];
    }

    private function historyGroupKey(Attendance $item): string
    {
        $start = $item->start_time ?? $item->clock_in_time;
        $end = $item->end_time ?? $item->clock_out_time;

        $startFormatted = $start
            ? ($start instanceof Carbon ? $start : Carbon::parse($start))->format('Y-m-d H:i')
            : null;

        $endFormatted = $end
            ? ($end instanceof Carbon ? $end : Carbon::parse($end))->format('Y-m-d H:i')
            : 'Present';

        return $startFormatted.' - '.$endFormatted;
    }
}
