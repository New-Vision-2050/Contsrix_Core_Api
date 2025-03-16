<?php

declare(strict_types=1);

namespace Modules\Company\CompanyField\Services;

use Illuminate\Support\Collection;
use Modules\Company\CompanyField\DTO\CreateCompanyFieldDTO;
use Modules\Company\CompanyField\Models\CompanyField;
use Modules\Company\CompanyField\Repositories\CompanyFieldRepository;
use Ramsey\Uuid\UuidInterface;

class CompanyFieldCRUDService
{
    public function __construct(
        private CompanyFieldRepository $repository,
    ) {
    }

    public function create(CreateCompanyFieldDTO $createCompanyFieldDTO): CompanyField
    {
        return $this->repository->createCompanyField($createCompanyFieldDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): CompanyField
    {
        return $this->repository->getCompanyField(
            id: $id,
        );
    }

    public function all()
    {
        return $this->repository->all();
    }
}
