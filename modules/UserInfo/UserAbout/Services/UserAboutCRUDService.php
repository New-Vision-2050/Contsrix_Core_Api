<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserAbout\Services;

use Illuminate\Support\Collection;
use Modules\UserInfo\UserAbout\DTO\CreateUserAboutDTO;
use Modules\UserInfo\UserAbout\Models\UserAbout;
use Modules\UserInfo\UserAbout\Repositories\UserAboutRepository;
use Ramsey\Uuid\UuidInterface;

class UserAboutCRUDService
{
    public function __construct(
        private UserAboutRepository $repository,
    ) {
    }

    public function create(CreateUserAboutDTO $createUserAboutDTO): UserAbout
    {
         return $this->repository->createOrUpdateUserAbout($createUserAboutDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $companyId,UuidInterface $globalId)
    {
        return $this->repository->getUserAbout($companyId, $globalId);
    }
}
