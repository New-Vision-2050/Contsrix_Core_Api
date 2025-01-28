<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationType\Services;

use Illuminate\Support\Collection;
use Modules\Company\CompanyRegistrationType\DTO\CreateCompanyRegistrationTypeDTO;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use Modules\Company\CompanyRegistrationType\Repositories\CompanyRegistrationTypeRepository;
use Ramsey\Uuid\UuidInterface;

class CompanyRegistrationTypeCRUDService
{
    public function __construct(
        private CompanyRegistrationTypeRepository $repository,
    ) {
    }

    public function create(CreateCompanyRegistrationTypeDTO $createCompanyRegistrationTypeDTO): CompanyRegistrationType
    {
         return $this->repository->createCompanyRegistrationType($createCompanyRegistrationTypeDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): CompanyRegistrationType
    {
        return $this->repository->getCompanyRegistrationType(
            id: $id,
        );
    }
}
