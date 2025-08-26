<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\Services;

use Illuminate\Support\Collection;
use Modules\Leave\LeaveType\DTO\CreateLeaveTypeDTO;
use Modules\Leave\LeaveType\Models\LeaveType;
use Modules\Leave\LeaveType\Repositories\LeaveTypeRepository;
use Ramsey\Uuid\UuidInterface;

class LeaveTypeCRUDService
{
    public function __construct(
        private LeaveTypeRepository $repository,
    ) {
    }

    public function create(CreateLeaveTypeDTO $createLeaveTypeDTO): LeaveType
    {
         return $this->repository->createLeaveType($createLeaveTypeDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): LeaveType
    {
        return $this->repository->getLeaveType(
            id: $id,
        );
    }

    /**
     * Get leave types for export with optional filtering
     *
     * @param array $filters
     * @return Collection
     */
    public function getForExport(array $filters = []): Collection
    {
        return $this->repository->getForExport($filters);
    }
}
