<?php

declare(strict_types=1);

namespace Modules\Company\RegistrationType\Services;

use Illuminate\Support\Collection;
use Modules\Company\RegistrationType\DTO\CreateRegistrationTypeDTO;
use Modules\Company\RegistrationType\Models\RegistrationType;
use Modules\Company\RegistrationType\Repositories\RegistrationTypeRepository;
use Ramsey\Uuid\UuidInterface;

class RegistrationTypeCRUDService
{
    public function __construct(
        private RegistrationTypeRepository $repository,
    ) {
    }

    public function create(CreateRegistrationTypeDTO $createRegistrationTypeDTO): RegistrationType
    {
         return $this->repository->createRegistrationType($createRegistrationTypeDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): RegistrationType
    {
        return $this->repository->getRegistrationType(
            id: $id,
        );
    }
}
