<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\Services;

use Illuminate\Support\Collection;
use Modules\SubscriptionSystem\ProgramSystem\DTO\CreateProgramSystemDTO;
use Modules\SubscriptionSystem\ProgramSystem\Models\ProgramSystem;
use Modules\SubscriptionSystem\ProgramSystem\Repositories\ProgramSystemRepository;
use PhpParser\Node\Stmt\Return_;
use Ramsey\Uuid\UuidInterface;

class ProgramSystemCRUDService
{
    public function __construct(
        private ProgramSystemRepository $repository,
    ) {
    }

    public function create(CreateProgramSystemDTO $createProgramSystemDTO): ProgramSystem
    {
        $programSystem = $this->repository->createProgramSystem($createProgramSystemDTO->toArray());

         foreach ($createProgramSystemDTO->features as $item) {
            $programSystem->features()->attach($item['feature_id'], [
                'module_id' => $item['module_id'],
            ]);
        }

        foreach ($createProgramSystemDTO->companyFields as $id) {
            $programSystem->companyFields()->attach($id);
        }

        foreach ($createProgramSystemDTO->businessTypes as $id) {
            $programSystem->businessTypes()->attach($id);
        }

        return $programSystem;
    }
    public function toggleIsActive(UuidInterface $id): ProgramSystem
    {
      return  $this->repository->toggleIsActive($id);

    }
    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): ProgramSystem
    {
        return $this->repository->getProgramSystem(
            id: $id,
        );
    }
}
