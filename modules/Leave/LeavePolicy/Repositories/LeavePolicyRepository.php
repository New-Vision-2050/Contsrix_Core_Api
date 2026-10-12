<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Leave\LeavePolicy\Models\LeavePolicy;

/**
 * @property LeavePolicy $model
 * @method LeavePolicy findOneOrFail($id)
 * @method LeavePolicy findOneByOrFail(array $data)
 */
class LeavePolicyRepository extends BaseRepository
{
    public function __construct(LeavePolicy $model)
    {
        parent::__construct($model);
    }

    public function getLeavePolicyList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getLeavePolicy(UuidInterface $id): LeavePolicy
    {
        return $this->model->with('leaveType')
            ->where('id', $id->toString())
            ->firstOrFail();
    }

    public function createLeavePolicy(array $data): LeavePolicy
    {
        return $this->create($data);
    }

    public function updateLeavePolicy(UuidInterface $id, array $data)
    {
        return $this->update($id, $data);
    }

    public function deleteLeavePolicy(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function updateRolloverAllowed(UuidInterface $id, bool $isRolloverAllowed): bool
    {
        return $this->update($id, ['is_rollover_allowed' => $isRolloverAllowed]);
    }

    public function getForExport(array $filters = []): SupportCollection
    {
        $query = $this->model->newQuery()
            ->where('company_id', tenant('id'));

        // Apply name filter if provided
        if (!empty($filters['name'])) {
            $query->where('name', 'LIKE', '%' . $filters['name'] . '%');
        }

        // Apply total_days filter if provided
        if (isset($filters['total_days'])) {
            $query->where('total_days', $filters['total_days']);
        }

        // Apply day_calculation_type filter if provided
        if (!empty($filters['day_calculation_type'])) {
            $query->where('day_calculation_type', $filters['day_calculation_type']);
        }

        // Apply leave_type_id filter if provided
        if (!empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }

        // Apply specific IDs filter if provided
        if (!empty($filters['ids']) && is_array($filters['ids'])) {
            $query->whereIn('id', $filters['ids']);
        }

        return $query->get();
    }
}
