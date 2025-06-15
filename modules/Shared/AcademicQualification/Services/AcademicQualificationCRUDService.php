<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicQualification\Services;

use Illuminate\Support\Collection;
use Modules\Shared\AcademicQualification\DTO\CreateAcademicQualificationDTO;
use Modules\Shared\AcademicQualification\Models\AcademicQualification;
use Modules\Shared\AcademicQualification\Repositories\AcademicQualificationRepository;
use Ramsey\Uuid\UuidInterface;

class AcademicQualificationCRUDService
{
    public function __construct(
        private AcademicQualificationRepository $repository,
    ) {
    }

    public function create(CreateAcademicQualificationDTO $createAcademicQualificationDTO): AcademicQualification
    {
         return $this->repository->createAcademicQualification($createAcademicQualificationDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): AcademicQualification
    {
        return $this->repository->getAcademicQualification(
            id: $id,
        );
    }
}
