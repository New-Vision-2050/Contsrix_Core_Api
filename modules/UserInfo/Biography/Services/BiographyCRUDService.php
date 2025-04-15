<?php

declare(strict_types=1);

namespace Modules\UserInfo\Biography\Services;

use Illuminate\Support\Collection;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\UserInfo\Biography\DTO\CreateBiographyDTO;
use Modules\UserInfo\Biography\Models\Biography;
use Modules\UserInfo\Biography\Repositories\BiographyRepository;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;

class BiographyCRUDService
{
    public function __construct(
        private BiographyRepository $repository,
        private CompanyUserRepository $companyUserRepository,
        private FileUploadService  $fileUploadService,
    ) {
    }

    public function create(CreateBiographyDTO $createBiographyDTO)
    {
        $file = $createBiographyDTO->file;
        $company_id = $createBiographyDTO->company_id;
        $global_id = $createBiographyDTO->global_id;

        $visibility = 'public';

        $user = $this->companyUserRepository->getCompanyUserGlobalId(Uuid::fromString($global_id));
        if ($file) {
            $companyName = Company::find($company_id)?->name ?? 'UnknownCompany';
            $path = $companyName . '/' . $user->name;

            $this->fileUploadService->uploadFile(
                $user,
                $file,
                $path,
                'upload_biography',
                $visibility
            );
        }

        return $user->fresh()->load('media');
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id)
    {
        return $this->repository->getBiography(
            id: $id,
        );
    }
}
