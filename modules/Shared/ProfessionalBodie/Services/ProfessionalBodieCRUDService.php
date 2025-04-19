<?php

declare(strict_types=1);

namespace Modules\Shared\ProfessionalBodie\Services;

use Illuminate\Support\Collection;
use Modules\Shared\ProfessionalBodie\DTO\CreateProfessionalBodieDTO;
use Modules\Shared\ProfessionalBodie\Models\ProfessionalBodie;
use Modules\Shared\ProfessionalBodie\Repositories\ProfessionalBodieRepository;
use Ramsey\Uuid\UuidInterface;

class ProfessionalBodieCRUDService
{
    public function __construct(
        private ProfessionalBodieRepository $repository,
    ) {
    }

    public function create(CreateProfessionalBodieDTO $createProfessionalBodieDTO): ProfessionalBodie
    {
         return $this->repository->createProfessionalBodie($createProfessionalBodieDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): ProfessionalBodie
    {
        return $this->repository->getProfessionalBodie(
            id: $id,
        );
    }
}
