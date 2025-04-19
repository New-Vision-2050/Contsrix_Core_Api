<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Ichtrojan\Otp\Otp;
use Modules\Auth\DTO\ValidateOtpDTO;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Commands\UpdateIdentityDataCommand;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\CompanyUser\Requests\IdentityDataRequest;
use Modules\Shared\Media\Services\FileUploadService;

class IdentityDataService
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private CompanyUserRepository $repository,

    )
    {

    }
    public function uploadFile($request,$globalId): array
    {
        $visibility = 'public';
        $companyUser = $this->repository->getCompanyUserGlobalId($globalId);
        $path = Company::find(auth()->user()->company_id)->name . '/' . $companyUser->name;

        $uploadedFiles = [];

        if ($request->hasFile('file_passport')) {
            $uploadedFiles['file_passport'] = $this->fileUploadService->uploadFile(
                $companyUser, $request->file('file_passport'), $path, 'file_passport', $visibility
            );
        }

        if ($request->hasFile('file_identity')) {
            $uploadedFiles['file_identity'] = $this->fileUploadService->uploadFile(
                $companyUser, $request->file('file_identity'), $path, 'file_identity', $visibility
            );
        }

        if ($request->hasFile('file_border_number')) {
            $uploadedFiles['file_border_number'] = $this->fileUploadService->uploadFile(
                $companyUser, $request->file('file_border_number'), $path, 'file_border_number', $visibility
            );
        }

        if ($request->hasFile('file_entry_number')) {
            $uploadedFiles['file_entry_number'] = $this->fileUploadService->uploadFile(
                $companyUser, $request->file('file_entry_number'), $path, 'file_entry_number', $visibility
            );
        }
        if ($request->hasFile('file_work_permit')) {
            $uploadedFiles['file_work_permit'] = $this->fileUploadService->uploadFile(
                $companyUser, $request->file('file_work_permit'), $path, 'file_work_permit', $visibility
            );
        }

        return $uploadedFiles;
    }

}
