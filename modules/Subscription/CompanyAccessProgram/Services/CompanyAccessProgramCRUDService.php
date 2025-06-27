<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Services;

use Illuminate\Support\Collection;
use Modules\Subscription\CompanyAccessProgram\DTO\CreateCompanyAccessProgramDTO;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;
use Modules\Subscription\CompanyAccessProgram\Repositories\CompanyAccessProgramRepository;
use Ramsey\Uuid\UuidInterface;

class CompanyAccessProgramCRUDService
{
    public function __construct(
        private CompanyAccessProgramRepository $repository,
    ) {
    }

    public function create(CreateCompanyAccessProgramDTO $createCompanyAccessProgramDTO): CompanyAccessProgram
    {
         return $this->repository->createCompanyAccessProgram($createCompanyAccessProgramDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): CompanyAccessProgram
    {
        return $this->repository->getCompanyAccessProgram(
            id: $id,
        );
    }
}
