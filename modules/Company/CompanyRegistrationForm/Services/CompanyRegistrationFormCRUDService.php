<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationForm\Services;

use Illuminate\Support\Collection;
use Modules\Company\CompanyRegistrationForm\DTO\CreateCompanyRegistrationFormDTO;
use Modules\Company\CompanyRegistrationForm\Models\CompanyRegistrationForm;
use Modules\Company\CompanyRegistrationForm\Repositories\CompanyRegistrationFormRepository;
use Ramsey\Uuid\UuidInterface;

class CompanyRegistrationFormCRUDService
{
    public function __construct(
        private CompanyRegistrationFormRepository $repository,
    ) {
    }

    public function create(CreateCompanyRegistrationFormDTO $createCompanyRegistrationFormDTO): CompanyRegistrationForm
    {
         return $this->repository->createCompanyRegistrationForm($createCompanyRegistrationFormDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): CompanyRegistrationForm
    {
        return $this->repository->getCompanyRegistrationForm(
            id: $id,
        );
    }
}
