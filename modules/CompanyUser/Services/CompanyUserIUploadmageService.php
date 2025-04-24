<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\User\Repositories\UserRepository;

class CompanyUserIUploadmageService
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private CompanyUserRepository $repository,
        private UserRepository $userRepository
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
        return $companyUser->fresh()->load('media');
    }

}
