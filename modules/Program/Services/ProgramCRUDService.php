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
        return $this->repository->createProgram($createProgramDTO->toArray());
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

    public function selectList(): Collection
    {
        return $this->repository->selectList();
    }
}
