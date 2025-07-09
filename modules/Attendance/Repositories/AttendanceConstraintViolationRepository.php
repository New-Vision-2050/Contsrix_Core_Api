<?php

declare(strict_types=1);

namespace Modules\Attendance\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Attendance\Models\AttendanceConstraintViolation;
use Ramsey\Uuid\UuidInterface;

/**
 * @property AttendanceConstraintViolation $model
 * @method AttendanceConstraintViolation findOneOrFail($id)
 * @method AttendanceConstraintViolation findOneByOrFail(array $data)
 */
class AttendanceConstraintViolationRepository extends BaseRepository
{
    public function __construct(AttendanceConstraintViolation $model)
    {
        parent::__construct($model);
    }

    /**
     * Get violation list with filters and pagination
     */
    public function getViolationList(array $filters = [], ?int $page = null, ?int $perPage = 10): array
    {
        $query = $this->model->newQuery()->with(['user', 'company', 'attendanceRecord', 'constraint']);

        // Apply filters using the filter method
        if (!empty($filters)) {
            $query->filter($filters);
        }

        $query->orderBy('detected_at', 'desc');

        if ($page) {
            return $this->getPaginationData($query, $page, $perPage);
        }

        return [
            'data' => $query->get(),
            'pagination' => null
        ];
    }

    /**
     * Get violation by ID
     */
    public function getViolation(UuidInterface $id): AttendanceConstraintViolation
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    /**
     * Create new violation
     */
    public function createViolation(array $data): AttendanceConstraintViolation
    {
        return $this->create($data);
    }

    /**
     * Update violation
     */
    public function updateViolation(UuidInterface $id, array $data): AttendanceConstraintViolation
    {
        $violation = $this->getViolation($id);
        $violation->update($data);
        return $violation->fresh();
    }

    /**
     * Delete violation
     */
    public function deleteViolation(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Resolve violation
     */
    public function resolveViolation(UuidInterface $id, string $resolvedBy, ?string $resolutionNotes = null): AttendanceConstraintViolation
    {
        $violation = $this->getViolation($id);
        $violation->update([
            'status' => 'resolved',
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
            'resolution_notes' => $resolutionNotes,
        ]);

        return $violation->fresh();
    }

    /**
     * Dismiss violation
     */
    public function dismissViolation(UuidInterface $id, string $dismissedBy, ?string $dismissalReason = null): AttendanceConstraintViolation
    {
        $violation = $this->getViolation($id);
        $violation->update([
            'status' => 'dismissed',
            'resolved_by' => $dismissedBy,
            'resolved_at' => now(),
            'resolution_notes' => $dismissalReason,
        ]);

        return $violation->fresh();
    }

    /**
     * Get pending violations
     */
    public function getPendingViolations(array $filters = []): Collection
    {
        $filters['status'] = 'pending';

        $query = $this->model->newQuery()->with(['user', 'company', 'attendanceRecord', 'constraint']);
        $query->filter($filters);
        $query->orderBy('detected_at', 'desc');

        return $query->get();
    }

    /**
     * Get violations by severity
     */
    public function getViolationsBySeverity(string $severity, array $filters = []): Collection
    {
        $filters['severity'] = $severity;

        $query = $this->model->newQuery()->with(['user', 'company', 'attendanceRecord', 'constraint']);
        $query->filter($filters);
        $query->orderBy('detected_at', 'desc');

        return $query->get();
    }

    /**
     * Get violations by user
     */
    public function getViolationsByUser(string $userId, array $filters = []): Collection
    {
        $filters['userId'] = $userId;

        $query = $this->model->newQuery()->with(['user', 'company', 'attendanceRecord', 'constraint']);
        $query->filter($filters);
        $query->orderBy('detected_at', 'desc');

        return $query->get();
    }

    /**
     * Get violations by constraint
     */
    public function getViolationsByConstraint(string $constraintId, array $filters = []): Collection
    {
        $filters['constraintId'] = $constraintId;

        $query = $this->model->newQuery()->with(['user', 'company', 'attendanceRecord', 'constraint']);
        $query->filter($filters);
        $query->orderBy('detected_at', 'desc');

        return $query->get();
    }

    /**
     * Get violation statistics
     */
    public function getViolationStatistics(array $filters = []): array
    {
        $query = $this->model->newQuery();

        if (!empty($filters)) {
            $query->filter($filters);
        }

        $stats = [
            'total_violations' => $query->count(),
            'pending_violations' => $query->where('status', 'pending')->count(),
            'resolved_violations' => $query->where('status', 'resolved')->count(),
            'dismissed_violations' => $query->where('status', 'dismissed')->count(),
            'by_severity' => $query->selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray(),
            'by_type' => $query->selectRaw('violation_type, COUNT(*) as count')
                ->groupBy('violation_type')
                ->pluck('count', 'violation_type')
                ->toArray(),
            'by_status' => $query->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'recent_violations' => $query->where('detected_at', '>=', now()->subDays(7))->count(),
            'today_violations' => $query->whereDate('detected_at', today())->count(),
        ];

        return $stats;
    }

    /**
     * Get violations summary
     */
    public function getViolationsSummary(array $filters = []): array
    {
        $query = $this->model->newQuery();

        if (!empty($filters)) {
            $query->filter($filters);
        }

        // Get violations grouped by constraint type
        $byConstraintType = $query->join('attendance_constraints', 'attendance_constraint_violations.constraint_id', '=', 'attendance_constraints.id')
            ->selectRaw('attendance_constraints.constraint_type, COUNT(*) as count')
            ->groupBy('attendance_constraints.constraint_type')
            ->pluck('count', 'constraint_type')
            ->toArray();

        // Get violations grouped by constraint name
        $byConstraintName = $query->join('attendance_constraints', 'attendance_constraint_violations.constraint_id', '=', 'attendance_constraints.id')
            ->selectRaw('attendance_constraints.constraint_name, COUNT(*) as count')
            ->groupBy('attendance_constraints.constraint_name')
            ->pluck('count', 'constraint_name')
            ->toArray();

        // Get top violating users
        $topViolatingUsers = $query->join('users', 'attendance_constraint_violations.user_id', '=', 'users.id')
            ->selectRaw('users.name, users.email, COUNT(*) as violation_count')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('violation_count')
            ->limit(10)
            ->get()
            ->toArray();

        return [
            'by_constraint_type' => $byConstraintType,
            'by_constraint_name' => $byConstraintName,
            'top_violating_users' => $topViolatingUsers,
            'statistics' => $this->getViolationStatistics($filters),
        ];
    }

    /**
     * Bulk resolve violations
     */
    public function bulkResolve(array $violationIds, string $resolvedBy, ?string $resolutionNotes = null): int
    {
        return $this->model->whereIn('id', $violationIds)->update([
            'status' => 'resolved',
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
            'resolution_notes' => $resolutionNotes,
            'updated_at' => now()
        ]);
    }

    /**
     * Bulk dismiss violations
     */
    public function bulkDismiss(array $violationIds, string $dismissedBy, ?string $dismissalReason = null): int
    {
        return $this->model->whereIn('id', $violationIds)->update([
            'status' => 'dismissed',
            'resolved_by' => $dismissedBy,
            'resolved_at' => now(),
            'resolution_notes' => $dismissalReason,
            'updated_at' => now()
        ]);
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
