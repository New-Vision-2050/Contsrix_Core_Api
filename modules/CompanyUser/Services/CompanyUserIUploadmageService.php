<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\ArchiveLibrary\File\Services\EmployeeArchiveFileService;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\User\Repositories\UserRepository;

class CompanyUserIUploadmageService
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private CompanyUserRepository $repository,
        private UserRepository $userRepository,
        private EmployeeArchiveFileService $employeeArchiveFileService
    )
    {

    }

    public function uploadFile($request,$userId)
    {
        $file = $request->image;

        $visibility = 'public';

        $user = $this->userRepository->getUser($userId);

        $path = Company::find($user->company_id)->name . '/' . $user->name;

        $companyUser  = CompanyUser::find($user->global_company_user_id);
        $companyUser->clearMediaCollection('upload_user');
        $media = $this->fileUploadService->uploadFile($companyUser, $file, $path, 'upload_user', $visibility );
        $this->employeeArchiveFileService->archiveUploadedFiles(
            companyId: (string) $user->company_id,
            employeeGlobalId: (string) $user->global_company_user_id,
            employeeName: (string) $user->name,
            files: $file,
            mainSection: EmployeeArchiveFileService::SECTION_PERSONAL,
            subSection: EmployeeArchiveFileService::SUB_PERSONAL_PHOTO,
            sourceModel: $companyUser,
            sourceMedia: $media,
        );

        return $companyUser->fresh()->load('media');
    }

}
