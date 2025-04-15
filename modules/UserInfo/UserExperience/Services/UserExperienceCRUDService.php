<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserExperience\Services;

use Illuminate\Support\Collection;
use Modules\UserInfo\UserExperience\DTO\CreateUserExperienceDTO;
use Modules\UserInfo\UserExperience\Models\UserExperience;
use Modules\UserInfo\UserExperience\Repositories\UserExperienceRepository;
use Ramsey\Uuid\UuidInterface;

class UserExperienceCRUDService
{
    public function __construct(
        private UserExperienceRepository $repository,
    ) {
    }

    public function create(CreateUserExperienceDTO $createUserExperienceDTO): UserExperience
    {
         return $this->repository->createUserExperience($createUserExperienceDTO->toArray());
    }

    public function list(UuidInterface $companyId,UuidInterface $globalId,int $page = 1, int $perPage = 10)//: array
    {
        return $this->repository->getUserExperienceList($companyId, $globalId, $page, $perPage);
    }

    public function get(UuidInterface $id): UserExperience
    {
        return $this->repository->getUserExperience(
            id: $id,
        );
    }
}
