<?php

declare(strict_types=1);

namespace Modules\Subscription\Module\Services;

use Illuminate\Support\Collection;
use Modules\Subscription\Module\DTO\CreateModuleDTO;
use Modules\Subscription\Module\Models\Module;
use Modules\Subscription\Module\Repositories\ModuleRepository;
use Ramsey\Uuid\UuidInterface;

class ModuleCRUDService
{
    public function __construct(
        private ModuleRepository $repository,
    ) {
    }

    public function create(CreateModuleDTO $createModuleDTO): Module
    {
         return $this->repository->createModule($createModuleDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginatedParents(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Module
    {
        return $this->repository->getModule(
            id: $id,
        );
    }
}
