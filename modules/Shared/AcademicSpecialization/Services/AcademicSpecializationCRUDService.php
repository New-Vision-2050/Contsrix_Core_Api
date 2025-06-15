<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Services;

use Illuminate\Support\Collection;
use Modules\Shared\AcademicSpecialization\DTO\CreateAcademicSpecializationDTO;
use Modules\Shared\AcademicSpecialization\Models\AcademicSpecialization;
use Modules\Shared\AcademicSpecialization\Repositories\AcademicSpecializationRepository;
use Ramsey\Uuid\UuidInterface;

class AcademicSpecializationCRUDService
{
    public function __construct(
        private AcademicSpecializationRepository $repository,
    ) {
    }

    public function create(CreateAcademicSpecializationDTO $createAcademicSpecializationDTO): AcademicSpecialization
    {
         return $this->repository->createAcademicSpecialization($createAcademicSpecializationDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): AcademicSpecialization
    {
        return $this->repository->getAcademicSpecialization(
            id: $id,
        );
    }
}
