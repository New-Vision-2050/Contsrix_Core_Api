<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Shared\Media\Services\FileUploadService;
class CompanyUserIUploadmageService
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private CompanyUserRepository $repository,
    )
    {

    }

    public function uploadFile($request)
    {
        $file = $request->image;

        $visibility = 'public';


        $path = Company::find(auth()->user()->company_id)->name . '/' . auth()->user()->name;

        $companyUser  =CompanyUser::find(auth()->user()->global_company_user_id);

        $media = $this->fileUploadService->uploadFile($companyUser, $file, $path, 'upload_user', $visibility );
        return $media->getFullUrl();
    }

}
