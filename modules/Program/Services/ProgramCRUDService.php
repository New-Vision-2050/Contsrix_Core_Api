<?php

declare(strict_types=1);

namespace Modules\Program\Services;

use Illuminate\Support\Collection;
use Modules\Program\DTO\CreateProgramDTO;
use Modules\Program\Models\Program;
use Modules\Program\Repositories\ProgramRepository;
use Ramsey\Uuid\UuidInterface;

class ProgramCRUDService
{
    public function __construct(
        private ProgramRepository $repository,
    ) {
    }

    public function create(CreateProgramDTO $createProgramDTO): Program
    {
<<<<<<< HEAD
        return $this->repository->createProgram($createProgramDTO->toArray());
=======
         return $this->repository->createProgram($createProgramDTO->toArray());
>>>>>>> 7be6c72c (merge with stage (first version ))
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Program
    {
        return $this->repository->getProgram(
            id: $id,
        );
    }
<<<<<<< HEAD

    public function selectList(): Collection
    {
        return $this->repository->selectList();
    }
=======
>>>>>>> 7be6c72c (merge with stage (first version ))
}
