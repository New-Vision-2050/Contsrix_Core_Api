<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Services;

use Illuminate\Support\Collection;
use Modules\Leave\LeavePolicy\DTO\CreateLeavePolicyDTO;
use Modules\Leave\LeavePolicy\DTO\UpdateRolloverAllowedDTO;
use Modules\Leave\LeavePolicy\DTO\UpdateHalfDayAllowedDTO;
use Modules\Leave\LeavePolicy\Models\LeavePolicy;
use Modules\Leave\LeavePolicy\Repositories\LeavePolicyRepository;
use Ramsey\Uuid\UuidInterface;

class LeavePolicyCRUDService
{
    public function __construct(
        private LeavePolicyRepository $repository,
    ) {
    }

    public function create(CreateLeavePolicyDTO $createLeavePolicyDTO): LeavePolicy
    {
         return $this->repository->createLeavePolicy($createLeavePolicyDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): LeavePolicy
    {
        return $this->repository->getLeavePolicy(
            id: $id,
        );
    }

    public function updateRolloverAllowed(UpdateRolloverAllowedDTO $updateRolloverAllowedDTO): bool
    {
        return $this->repository->updateRolloverAllowed(
            id: $updateRolloverAllowedDTO->getLeavePolicyId(),
            isRolloverAllowed: $updateRolloverAllowedDTO->getIsRolloverAllowed()
        );
    }

    public function updateHalfDayAllowed(UpdateHalfDayAllowedDTO $updateHalfDayAllowedDTO): bool
    {
        return $this->repository->updateHalfDayAllowed(
            id: $updateHalfDayAllowedDTO->getLeavePolicyId(),
            isAllowHalfDay: $updateHalfDayAllowedDTO->getIsAllowHalfDay()
        );
    }

    public function getForExport(array $filters = []): Collection
    {
        return $this->repository->getForExport($filters);
    }
}
