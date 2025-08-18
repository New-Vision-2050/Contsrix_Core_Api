<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
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
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createLeavePolicy(array $data): LeavePolicy
    {
        return $this->create($data);
    }

    public function updateLeavePolicy(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteLeavePolicy(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
