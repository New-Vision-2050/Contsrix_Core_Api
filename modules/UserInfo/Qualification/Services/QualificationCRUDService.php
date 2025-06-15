<?php

declare(strict_types=1);

namespace Modules\UserInfo\Qualification\Services;

use Illuminate\Support\Collection;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\UserInfo\Qualification\DTO\CreateQualificationDTO;
use Modules\UserInfo\Qualification\Models\Qualification;
use Modules\UserInfo\Qualification\Repositories\QualificationRepository;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;

class QualificationCRUDService
{
    public function __construct(
        private QualificationRepository $repository,
        private CompanyUserRepository $companyUserRepository,
        private FileUploadService $fileUploadService,

    ) {
    }

    public function create(CreateQualificationDTO $createQualificationDTO): Qualification
    {
         return $this->repository->createQualification($createQualificationDTO->toArray());
    }

    public function list(UuidInterface $companyId,UuidInterface $globalId,int $page = 1, int $perPage = 10)//: array
    {
        return $this->repository->getQualificationList($companyId, $globalId, $page, $perPage);
    }
    public function get(UuidInterface $id): Qualification
    {
        return $this->repository->getQualification(
            id: $id,
        );
    }

    public function uploadFile($qualification, $request)
    {
        $visibility = 'public';
        $uploadedFiles = [];

        $fieldIds = collect($request->input('file', []))
            ->pluck('id')
            ->filter()
            ->toArray();

        $hasOldFiles = !empty($fieldIds);
        $hasNewFiles = $request->hasFile('file') && is_array($request->file('file')) && count($request->file('file')) > 0;

        $existingMedia = $qualification->getMedia('upload_Qualification');
        foreach ($existingMedia as $media) {
            if (!in_array($media->id, $fieldIds)) {
                $media->delete();
            }
        }

        if ($hasNewFiles) {
            foreach ($request->file('file') as $file) {
                $user = $this->companyUserRepository->getCompanyUserGlobalId(Uuid::fromString($qualification->global_id));
                $path = Company::find($qualification->company_id)->name . '/' . $user->name;

                $media = $this->fileUploadService->uploadFile($qualification, $file, $path, 'upload_Qualification', $visibility);
                $uploadedFiles[] = $media;
            }
        }

        if (!$hasOldFiles && !$hasNewFiles) {
            $qualification->clearMediaCollection('upload_Qualification');
        }

        return $qualification->fresh()->load('media');
    }

}
