<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\Uuid;
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

        $media = $this->fileUploadService->uploadFile($companyUser, $file, $path, 'upload', $visibility );
        return $media->getFullUrl();
    }

}
