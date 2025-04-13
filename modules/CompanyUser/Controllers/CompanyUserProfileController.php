<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Requests\ValidateOtpRequest;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Handlers\UpdateCompanyUserContactInfoHandler;
use Modules\CompanyUser\Handlers\UpdateCompanyUserDataInfoHandler;
use Modules\CompanyUser\Handlers\UpdateCompanyUserIdentityDataHandler;
use Modules\CompanyUser\Presenters\CompanyContactInfoPresenter;
use Modules\CompanyUser\Presenters\CompanyIdentityDataPresenter;
use Modules\CompanyUser\Presenters\CompanyUserDataInfoPresenter;
use Modules\CompanyUser\Presenters\CompanyUserImagePresenter;
use Modules\CompanyUser\Presenters\CompanyUserPresenter;
use Modules\CompanyUser\Requests\GetCompanyUserRequest;
use Modules\CompanyUser\Requests\IdentityDataRequest;
use Modules\CompanyUser\Requests\SendEmailOtpRequest;
use Modules\CompanyUser\Requests\UpdateCompanyContactInfoUserRequest;
use Modules\CompanyUser\Requests\UpdateCompanyDataInfoUserRequest;
use Modules\CompanyUser\Requests\UploadPhotoCompanyUserRequest;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Modules\CompanyUser\Services\CompanyUserImageValidationService;
use Modules\CompanyUser\Services\CompanyUserIUploadmageService;
use Modules\CompanyUser\Services\IdentityDataService;
use Modules\CompanyUser\Services\SendOtpService;
use Modules\CompanyUser\Services\ValidateOtpService;
use Modules\CompanyUser\Services\VerifyCompanyUserContactInfoService;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\Uuid;

class CompanyUserProfileController extends Controller
{
    public function __construct(
        private CompanyUserCRUDService               $companyUserService,
        private CompanyUserImageValidationService    $companyUserImageValidationService,
        private CompanyUserIUploadmageService        $companyUserIUploadImageService,
        private UpdateCompanyUserDataInfoHandler     $updateCompanyUserDataInfoHandler ,
        private UpdateCompanyUserContactInfoHandler  $updateCompanyUserContactInfoHandler,
        private UpdateCompanyUserIdentityDataHandler $updateCompanyUserIdentityDataHandler,
        private VerifyCompanyUserContactInfoService  $verifyCompanyUserContactInfoService,
        private SendOtpService                       $sendOtpService,
        private ValidateOtpService                   $validateOtpService,
        private IdentityDataService                  $identityDataService
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

    public function uploadPhoto(UploadPhotoCompanyUserRequest $request)//: JsonResponse
    {
        try {
            $companyUser = $this->companyUserIUploadImageService->uploadFile($request);
            $presenter = new CompanyUserImagePresenter($companyUser);
            return Json::item($presenter->getData(), [], "Photo uploaded successfully");

        } catch (\Exception $e) {
            return Json::error('Something went wrong, please try again later.', [
                'error' => $e->getMessage(),
            ]);
        }
    }
    public function showDataInfo()
    {
        $user = $this->companyUserService->get(
            Uuid::fromString(auth()->user()->global_company_user_id),
        );

        $presenter = new CompanyUserDataInfoPresenter($user);

        return Json::item($presenter->getData());
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

    public function sendOtp(SendEmailOtpRequest $request)
    {
        $command = $request->updateEmailOtpCommand();
        $command->name = auth()->user()->name;
        $user_id = auth()->user()->id;
        $otpData = $this->sendOtpService->sendOtp($command,$user_id);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'data'    => $otpData,
        ]);
    }

    public function validateOtp(ValidateOtpRequest $request)
    {
        return Json::item(["status" => $this->validateOtpService->validateOtp($request->createValidateOtpDTO())]);
    }
    public function showContactInformation()
    {
        $user = $this->companyUserService->get(
            Uuid::fromString(auth()->user()->global_company_user_id),
        );

        $presenter = new CompanyContactInfoPresenter($user);

        return Json::item($presenter->getData());
    }
    public function updateContactInformation(UpdateCompanyContactInfoUserRequest $request)
    {
        $command = $request->createUpdateCompanyUserCommand();
        $command->global_id = Uuid::fromString(auth()->user()->global_company_user_id);

        $this->updateCompanyUserContactInfoHandler->handle($command);

        $item = $this->companyUserService->getGlobalId($command->global_id);

        $presenter = new CompanyUserPresenter($item);

        return Json::item($presenter->getData());
    }
    public function identityData(IdentityDataRequest $request)
    {
        $command = $request->updateIdentityDataCommand();
        $command->global_id = Uuid::fromString(auth()->user()->global_company_user_id);

        $this->updateCompanyUserIdentityDataHandler->handle($command);

        $item = $this->companyUserService->getGlobalId($command->global_id);

        return   $this->identityDataService->uploadFile($request,$command->global_id);

        $presenter = new CompanyUserPresenter($item);

        return Json::item($presenter->getData());
    }

    public function showidentityData()
    {
        $user = $this->companyUserService->get(
            Uuid::fromString(auth()->user()->global_company_user_id),
        );

        $presenter = new CompanyIdentityDataPresenter($user);

        return Json::item($presenter->getData());
    }

}
