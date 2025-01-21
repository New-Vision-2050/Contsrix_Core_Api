<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Collection;
use Modules\Auth\DTO\CreateAuthDTO;
use Modules\Auth\Models\Auth;
use Modules\Auth\Repositories\AuthRepository;
use Ramsey\Uuid\UuidInterface;

class AuthCRUDService
{
    public function __construct(
        private AuthRepository $repository,
    ) {
    }

    public function create(CreateAuthDTO $createAuthDTO): Auth
    {
         return $this->repository->createAuth($createAuthDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Auth
    {
        return $this->repository->getAuth(
            id: $id,
        );
    }
}
