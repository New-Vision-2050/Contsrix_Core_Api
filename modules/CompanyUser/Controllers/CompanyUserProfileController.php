<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Handlers\UpdateCompanyUserDataInfoHandler;
use Modules\CompanyUser\Presenters\CompanyUserPresenter;
use Modules\CompanyUser\Requests\GetCompanyUserRequest;
use Modules\CompanyUser\Requests\UpdateCompanyDataInfoUserRequest;
use Modules\CompanyUser\Requests\UploadPhotoCompanyUserRequest;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Modules\CompanyUser\Services\CompanyUserImageValidationService;
use Modules\CompanyUser\Services\CompanyUserIUploadmageService;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\Uuid;

class CompanyUserProfileController extends Controller
{
    public function __construct(
        private CompanyUserCRUDService  $companyUserService,
        private CompanyUserImageValidationService $companyUserImageValidationService,
        private CompanyUserIUploadmageService $companyUserIUploadImageService,
        private UpdateCompanyUserDataInfoHandler         $updateCompanyUserDataInfoHandler ,
    )
    {
    }

    public function profile(): JsonResponse
    {
        $user = $this->companyUserService->get(
            Uuid::fromString(auth()->user()->global_company_user_id) ,
        );

        $presenter = new CompanyUserPresenter($user);

        return Json::item($presenter->getData());
    }

    public function validatePhoto(UploadPhotoCompanyUserRequest $request): JsonResponse
    {
        $errors = $this->companyUserImageValidationService->validateName($request);

        return Json::item($errors);
    }

    public function uploadPhoto(UploadPhotoCompanyUserRequest $request): JsonResponse
    {
        try {
            $path = $this->companyUserIUploadImageService->uploadFile($request);

            return Json::success([
                'message' => 'Photo uploaded successfully',
                'url' => $path,
            ]);
        } catch (\Exception $e) {
            return Json::error('Something went wrong, please try again later.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updateDataInfo(UpdateCompanyDataInfoUserRequest $request)
    {
        $command = $request->createUpdateCompanyUserCommand();
        $command->global_id = Uuid::fromString(auth()->user()->global_company_user_id);

        $this->updateCompanyUserDataInfoHandler->handle($command);

        $item = $this->companyUserService->getGlobalId($command->global_id);

        $presenter = new CompanyUserPresenter($item);

        return Json::item($presenter->getData());
    }
    public function updateContactInfo(UpdateCompanyDataInfoUserRequest $request)
    {
        $command = $request->createUpdateCompanyUserCommand();
        $command->global_id = Uuid::fromString(auth()->user()->global_company_user_id);

        $this->updateCompanyUserDataInfoHandler->handle($command);

        $item = $this->companyUserService->getGlobalId($command->global_id);

        $presenter = new CompanyUserPresenter($item);

        return Json::item($presenter->getData());
    }


}
