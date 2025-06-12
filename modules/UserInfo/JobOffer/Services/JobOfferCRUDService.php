<?php

declare(strict_types=1);

namespace Modules\UserInfo\JobOffer\Services;

use Illuminate\Support\Collection;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\Shared\Media\Services\FileDeletedService;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\UserInfo\JobOffer\DTO\CreateJobOfferDTO;
use Modules\UserInfo\JobOffer\Models\JobOffer;
use Modules\UserInfo\JobOffer\Repositories\JobOfferRepository;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;

class JobOfferCRUDService
{
    public function __construct(
        private JobOfferRepository $repository,
        private CompanyUserRepository $companyUserRepository,
        private FileUploadService  $fileUploadService,
        private FileDeletedService $fileDeletedService
    ) {
    }

    public function create(CreateJobOfferDTO $createJobOfferDTO,$request)//: JobOffer
    {
        $jobOffer = $this->repository->createOrUpdateJobOffer($createJobOfferDTO->toArray());

        $inputFile = $request->input('file');
        $file = $request->file('file');
        $company_id = $createJobOfferDTO->company_id;
        $global_id = $createJobOfferDTO->global_id;

        $visibility = 'public';

        $user = $this->companyUserRepository->getCompanyUserGlobalId(Uuid::fromString($global_id));

        if (empty($inputFile)) {
            $jobOffer->clearMediaCollection('upload_offerjob');
        }

        $this->fileDeletedService->deleteFile(
            $jobOffer,
            $inputFile,
            'upload_offerjob'
        );

        if ($file) {
            $companyName = Company::find($company_id)?->name ?? 'UnknownCompany';
            $path = $companyName . '/' . $user->name;

            $this->fileUploadService->uploadFile(
                $jobOffer,
                $file,
                $path,
                'upload_offerjob',
                $visibility
            );
        }


        return $jobOffer;
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
        return $this->repository->getJobOffer($companyId, $globalId);
    }
}
