<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Services;

use Illuminate\Support\Collection;
use Modules\AdminRequest\DTO\CreateAdminRequestDTO;
use Modules\AdminRequest\Models\AdminRequest;
use Modules\AdminRequest\Repositories\AdminRequestRepository;
use Ramsey\Uuid\UuidInterface;

class AdminRequestCRUDService
{
    public function __construct(
        private AdminRequestRepository $repository,
    ) {
    }

    public function create(CreateAdminRequestDTO $createAdminRequestDTO): AdminRequest
    {
         return $this->repository->createAdminRequest($createAdminRequestDTO->toArray());
    }

    public function list()
    {
        return $this->repository->getAll();
    }

    public function get(UuidInterface $id): AdminRequest
    {
        return $this->repository->getAdminRequest(
            id: $id,
        );
    }
}
