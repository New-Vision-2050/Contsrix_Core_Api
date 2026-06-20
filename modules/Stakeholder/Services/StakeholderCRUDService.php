<?php

declare(strict_types=1);

namespace Modules\Stakeholder\Services;

use Modules\Stakeholder\DTO\CreateStakeholderDTO;
use Modules\Stakeholder\Models\Stakeholder;
use Modules\Stakeholder\Repositories\StakeholderRepository;
use Ramsey\Uuid\UuidInterface;

class StakeholderCRUDService
{
    public function __construct(
        private StakeholderRepository $repository,
    ) {
    }

    public function create(CreateStakeholderDTO $dto): Stakeholder
    {
        return $this->repository->createStakeholder($dto->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->getStakeholderList($page, $perPage);
    }

    public function get(UuidInterface $id): Stakeholder
    {
        return $this->repository->getStakeholder($id);
    }

    public function update(UuidInterface $id, array $data): bool
    {
        return $this->repository->updateStakeholder($id, $data);
    }

    public function delete(UuidInterface $id): bool
    {
        return $this->repository->deleteStakeholder($id);
    }
}
