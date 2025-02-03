<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Services;

use Illuminate\Support\Collection;
use Modules\Company\CompanyCore\CompanyRegistrationForm\Models\CompanyRegistrationForm;
use Modules\Company\CompanyCore\DTO\CreateCompanyDTO;
use Modules\Company\CompanyCore\Jobs\CheckCompanyActivity;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Ramsey\Uuid\UuidInterface;

class CompanyCRUDService
{
    public function __construct(
        private CompanyRepository $repository,
    ) {
    }

    public function create(CreateCompanyDTO $createCompanyDTO): Company
    {
        $requestCompanyDTO = $createCompanyDTO->toArray();

        $company = $this->repository->createCompany($requestCompanyDTO);

        $companyRegistrationForm = CompanyRegistrationForm::create([
            'company_id' => $company->id,
            'registration_no' => $requestCompanyDTO['registration_no'],
            'classification_no' => $requestCompanyDTO['classification_no'],
        ]);

        CheckCompanyActivity::dispatch($company->id)->delay(now()->addHours(24));

        return $company;
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Company
    {
        return $this->repository->getCompany(
            id: $id,
        );
    }
}
