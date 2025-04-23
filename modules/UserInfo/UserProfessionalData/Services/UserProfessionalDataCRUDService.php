<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\Services;

use Illuminate\Support\Collection;
use Modules\UserInfo\UserProfessionalData\DTO\CreateUserProfessionalDataDTO;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Modules\UserInfo\UserProfessionalData\Repositories\UserProfessionalDataRepository;
use Ramsey\Uuid\UuidInterface;

class UserProfessionalDataCRUDService
{
    public function __construct(
        private UserProfessionalDataRepository $repository,
    ) {
    }

    public function create(CreateUserProfessionalDataDTO $createUserProfessionalDataDTO): UserProfessionalData
    {
         return $this->repository->createOrUpdateUserProfessionalData($createUserProfessionalDataDTO->toArray());
    }

    public function list(UuidInterface $companyId,UuidInterface $globalId,int $page = 1, int $perPage = 10): array
    {
        return $this->repository->getUserProfessionalDataList($companyId, $globalId, $page, $perPage);
    }

    public function get(UuidInterface $companyId,UuidInterface $globalId): UserProfessionalData
    {
        return $this->repository->getUserProfessionalData($companyId, $globalId);
    }
}
