<?php

declare(strict_types=1);

namespace Modules\Company\CompanyType\Services;

use Illuminate\Support\Collection;
use Modules\Company\CompanyType\DTO\CreateCompanyTypeDTO;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Company\CompanyType\Repositories\CompanyTypeRepository;
use Ramsey\Uuid\UuidInterface;

class CompanyTypeCRUDService
{
    public function __construct(
        private CompanyTypeRepository $repository,
    ) {
    }

    public function create(CreateCompanyTypeDTO $createCompanyTypeDTO): CompanyType
    {
         return $this->repository->createCompanyType($createCompanyTypeDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): CompanyType
    {
        return $this->repository->getCompanyType(
            id: $id,
        );
    }

    public function all()
    {
        return $this->repository->all();
    }
}
