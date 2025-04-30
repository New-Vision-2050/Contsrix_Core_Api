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
use PhpParser\Node\Stmt\Return_;

class IdentityDataService
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private CompanyUserRepository $repository,

    )
    {

    }
    public function uploadFile($request, $globalId)//: array
    {
        $visibility = 'public';
        $companyUser = $this->repository->getCompanyUserGlobalId($globalId);
        $path = Company::find(auth()->user()->company_id)->name . '/' . $companyUser->name;

        $uploadedFiles = [];

        $fields = [
            'file_passport',
            'file_identity',
            'file_border_number',
            'file_entry_number',
            'file_work_permit',
        ];

        foreach ($fields as $field) {
        $fieldIds = collect($request->input($field))
            // ->pluck('id')
            ->filter()
            ->toArray();

        // Get the existing media associated with the field
        $existingMedia = $companyUser->getMedia($field);
        foreach ($existingMedia as $media) {
            // Delete media that are no longer in the new input
            if (!in_array($media->id, $fieldIds)) {
                $media->delete();
            }
        }

            // ✅ Upload new files if any
            if ($request->hasFile($field)) {
                foreach ($request->file($field) as $file) {
                    $uploadedFiles[$field][] = $this->fileUploadService->uploadFile(
                        $companyUser, $file, $path, $field, $visibility
                    );
                }
            }
        }

        return $uploadedFiles;
    }
}
