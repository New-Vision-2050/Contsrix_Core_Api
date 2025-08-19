<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Leave\LeaveType\Models\LeaveType;

/**
 * @property LeaveType $model
 * @method LeaveType findOneOrFail($id)
 * @method LeaveType findOneByOrFail(array $data)
 */
class LeaveTypeRepository extends BaseRepository
{
    public function __construct(LeaveType $model)
    {
        parent::__construct($model);
    }

    public function getLeaveTypeList(?int $page, ?int $perPage = 10): Collection
    {
        // For listing, we might want to include branches for filtering/display
        // but typically listing doesn't need the relationship data
        return $this->paginatedList([], $page, $perPage);
    }

    public function getLeaveType(UuidInterface $id): LeaveType
    {
        return $this->model->with('branches')
            ->where('id', $id->toString())
            ->firstOrFail();
    }

    public function createLeaveType(array $data): LeaveType
    {
        $leaveType = $this->create($data);
        
        // Sync branches if provided
        if (isset($data['branch_ids']) && is_array($data['branch_ids'])) {
            $leaveType->branches()->sync($data['branch_ids']);
        }
        
        return $leaveType;
    }

    public function updateLeaveType(UuidInterface $id, array $data): bool
    {
        $leaveType = $this->findOneOrFail($id);
        
        // Update the basic data
        $updated = $this->update($id, $data);
        
        // Sync branches if provided
        if (isset($data['branch_ids']) && is_array($data['branch_ids'])) {
            $leaveType->branches()->sync($data['branch_ids']);
        }
        
        return $updated;
    }

    public function deleteLeaveType(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Get leave types for export with optional filtering
     *
     * @param array $filters
     * @return Collection
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        // Apply name filter if provided
        if (!empty($filters['name'])) {
            $query->where('name', 'LIKE', '%' . $filters['name'] . '%');
        }

        // Apply specific IDs filter if provided
        if (!empty($filters['ids'])) {
            $query->whereIn('id', $filters['ids']);
        }

        // Order by name for consistent export
        $query->orderBy('name');

        return $query->get();
    }
}
