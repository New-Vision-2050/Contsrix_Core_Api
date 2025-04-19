<?php

declare(strict_types=1);

namespace Modules\UserInfo\EmploymentContract\Services;

use Illuminate\Support\Collection;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\UserInfo\EmploymentContract\DTO\CreateEmploymentContractDTO;
use Modules\UserInfo\EmploymentContract\Models\EmploymentContract;
use Modules\UserInfo\EmploymentContract\Repositories\EmploymentContractRepository;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;
class EmploymentContractCRUDService
{
    public function __construct(
        private EmploymentContractRepository $repository,
        private CompanyUserRepository $companyUserRepository,
        private FileUploadService  $fileUploadService,
    ) {
    }

    public function create(CreateEmploymentContractDTO $createEmploymentContractDTO): EmploymentContract
    {
        $employmentContract = $this->repository->createEmploymentContract($createEmploymentContractDTO->toArray());

        $file = $createEmploymentContractDTO->file;
        $company_id = $createEmploymentContractDTO->company_id;
        $global_id = $createEmploymentContractDTO->global_id;

        $visibility = 'public';

        $user = $this->companyUserRepository->getCompanyUserGlobalId(Uuid::fromString($global_id));
        if ($file) {
            $user->clearMediaCollection('upload_employment_contracts');
            $companyName = Company::find($company_id)?->name ?? 'UnknownCompany';
            $path = $companyName . '/' . $user->name;

            $this->fileUploadService->uploadFile(
                $employmentContract,
                $file,
                $path,
                'upload_employment_contracts',
                $visibility
            );
        }

        return $employmentContract;
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $companyId,UuidInterface $globalId)
    {
        return $this->repository->getEmploymentContract($companyId, $globalId);
    }
}
