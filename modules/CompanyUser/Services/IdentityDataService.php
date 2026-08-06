<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Ichtrojan\Otp\Otp;
use Modules\Auth\DTO\ValidateOtpDTO;
use Modules\ArchiveLibrary\File\Services\EmployeeArchiveFileService;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Commands\UpdateIdentityDataCommand;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\CompanyUser\Requests\IdentityDataRequest;
use Modules\Shared\Media\Services\FileUploadService;
use PhpParser\Node\Stmt\Return_;

class IdentityDataService
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private EmployeeArchiveFileService $employeeArchiveFileService,
        private CompanyUserRepository $repository,

    )
    {

    }
    public function uploadFile($request, $globalId)
    {
        $visibility = 'public';
        $companyUser = $this->repository->getCompanyUserGlobalId($globalId);
        $companyId = (string) auth()->user()->company_id;
        $path = Company::find($companyId)->name . '/' . $companyUser->name;

        $uploadedFiles = [];

        $fields = [
            'file_passport',
            'file_identity',
            'file_border_number',
            'file_entry_number',
            'file_work_permit',
            'file_industrial_safety',
        ];

        foreach ($fields as $field) {
            $metaKey = str_replace('file_', '', $field);

            $hasNewFiles = $request->hasFile($field);

            $oldFiles = collect($request->input($field, []))
                ->pluck('id')
                ->filter()
                ->toArray();

            $hasOldFiles = !empty($oldFiles);

            if (!$hasNewFiles && !$hasOldFiles) {
                if (
                    $request->has($metaKey) ||
                    $request->has($metaKey . '_start_date') ||
                    $request->has($metaKey . '_end_date')
                ) {
                    $companyUser->clearMediaCollection($field);
                }
            }
            if ($request->has($field)) {
                $existingMedia = $companyUser->getMedia($field);
                foreach ($existingMedia as $media) {
                    if (!in_array($media->id, $oldFiles)) {
                        $media->delete();
                    }
                }
            }

            if ($hasNewFiles) {
                foreach ($request->file($field) as $file) {
                    $media = $this->fileUploadService->uploadFile(
                        $companyUser, $file, $path, $field, $visibility
                    );
                    $uploadedFiles[$field][] = $media;
                    $this->employeeArchiveFileService->archiveUploadedFiles(
                        companyId: $companyId,
                        employeeGlobalId: (string) $companyUser->global_id,
                        employeeName: (string) $companyUser->name,
                        files: $file,
                        mainSection: EmployeeArchiveFileService::SECTION_PERSONAL,
                        subSection: EmployeeArchiveFileService::SUB_RESIDENCE_INFO,
                        sourceModel: $companyUser,
                        sourceMedia: $media,
                    );
                }
            }




        }

        return $uploadedFiles;
    }


}
