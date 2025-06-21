<?php

declare(strict_types=1);

namespace Modules\Attendance\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Attendance\Models\AttendanceConstraint;
use Ramsey\Uuid\UuidInterface;

/**
 * @property AttendanceConstraint $model
 * @method AttendanceConstraint findOneOrFail($id)
 * @method AttendanceConstraint findOneByOrFail(array $data)
 */
class AttendanceConstraintRepository extends BaseRepository
{
    public function __construct(AttendanceConstraint $model)
    {
        parent::__construct($model);
    }

    /**
     * Get constraint list with filters and pagination
     */
    public function getConstraintList(array $filters = [], ?int $page = null, ?int $perPage = 10): array
    {
        $query = $this->model->newQuery()->with(['user', 'company']);

        // Apply filters using the filter method
        if (!empty($filters)) {
            $query->filter($filters);
        }

        $query->orderBy('priority', 'desc')->orderBy('created_at', 'desc');

        if ($page) {
            return $this->getPaginationData($query, $page, $perPage);
        }

        return [
            'data' => $query->get(),
            'pagination' => null
        ];
    }

    /**
     * Get constraint by ID
     */
    public function getConstraint(UuidInterface $id): AttendanceConstraint
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    /**
     * Create new constraint
     */
    public function createConstraint(array $data): AttendanceConstraint
    {
        return $this->create($data);
    }

    /**
     * Update constraint
     */
    public function updateConstraint(UuidInterface $id, array $data): AttendanceConstraint
    {
        $constraint = $this->getConstraint($id);
        $constraint->update($data);
        return $constraint->fresh();
    }

    /**
     * Delete constraint
     */
    public function deleteConstraint(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Get active constraints for user/company
     */
    public function getActiveConstraints(string $companyId, ?string $userId = null, ?string $departmentId = null): Collection
    {
        $filters = [
            'companyId' => $companyId,
            'isCurrentlyActive' => true
        ];

        if ($userId) {
            $filters['userId'] = $userId;
        }

        if ($departmentId) {
            $filters['departmentId'] = $departmentId;
        }

        $query = $this->model->newQuery();
        $query->filter($filters);
        $query->orderBy('priority', 'desc');

        return $query->get();
    }

    /**
     * Get constraints by type
     */
    public function getConstraintsByType(string $type, array $filters = []): Collection
    {
        $filters['constraintType'] = $type;

        $query = $this->model->newQuery();
        $query->filter($filters);
        $query->orderBy('priority', 'desc');

        return $query->get();
    }

    /**
     * Get constraints by name
     */
    public function getConstraintsByName(string $name, array $filters = []): Collection
    {
        $filters['constraintName'] = $name;

        $query = $this->model->newQuery();
        $query->filter($filters);
        $query->orderBy('priority', 'desc');

        return $query->get();
    }

    /**
     * Bulk activate constraints
     */
    public function bulkActivate(array $constraintIds): int
    {
        return $this->model->whereIn('id', $constraintIds)->update([
            'is_active' => true,
            'updated_at' => now()
        ]);
    }

    /**
     * Bulk deactivate constraints
     */
    public function bulkDeactivate(array $constraintIds): int
    {
        return $this->model->whereIn('id', $constraintIds)->update([
            'is_active' => false,
            'updated_at' => now()
        ]);
    }

    /**
     * Bulk delete constraints
     */
    public function bulkDelete(array $constraintIds): int
    {
        return $this->model->whereIn('id', $constraintIds)->delete();
    }

    /**
     * Get constraint statistics
     */
    public function getConstraintStatistics(array $filters = []): array
    {
        $query = $this->model->newQuery();

        if (!empty($filters)) {
            $query->filter($filters);
        }

        $stats = [
            'total_constraints' => $query->count(),
            'active_constraints' => $query->where('is_active', true)->count(),
            'inactive_constraints' => $query->where('is_active', false)->count(),
            'by_type' => $query->selectRaw('constraint_type, COUNT(*) as count')
                ->groupBy('constraint_type')
                ->pluck('count', 'constraint_type')
                ->toArray(),
            'by_name' => $query->selectRaw('constraint_name, COUNT(*) as count')
                ->groupBy('constraint_name')
                ->pluck('count', 'constraint_name')
                ->toArray(),
            'by_priority' => $query->selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->orderBy('priority')
                ->pluck('count', 'priority')
                ->toArray(),
        ];

        return $stats;
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
