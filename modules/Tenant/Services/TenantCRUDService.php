<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Illuminate\Support\Collection;
use Modules\Tenant\DTO\CreateTenantDTO;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Repositories\TenantRepository;
use Ramsey\Uuid\UuidInterface;

class TenantCRUDService
{
    public function __construct(
        private TenantRepository $repository,
    ) {
    }

    public function create(CreateTenantDTO $createTenantDTO): Tenant
    {
         return $this->repository->createTenant($createTenantDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Tenant
    {
        return $this->repository->getTenant(
            id: $id,
        );
    }
}
