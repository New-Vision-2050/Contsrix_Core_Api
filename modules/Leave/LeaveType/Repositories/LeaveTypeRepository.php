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
        return $this->paginatedList([], $page, $perPage);
    }

    public function getLeaveType(UuidInterface $id): LeaveType
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createLeaveType(array $data): LeaveType
    {
        return $this->create($data);
    }

    public function updateLeaveType(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteLeaveType(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
