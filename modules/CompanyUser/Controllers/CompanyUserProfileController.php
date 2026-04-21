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
use Modules\CompanyUser\Presenters\WidgetCompanyUserPresenter;
use Modules\CompanyUser\Presenters\WidgetCompanyUserProfilePresenter;
use Modules\CompanyUser\Requests\GetCompanyUserRequest;
use Modules\CompanyUser\Requests\IdentityDataRequest;
use Modules\CompanyUser\Requests\SendEmailOtpRequest;
use Modules\CompanyUser\Requests\UpdateCompanyContactInfoUserRequest;
use Modules\CompanyUser\Requests\UpdateCompanyDataInfoUserRequest;
use Modules\CompanyUser\Requests\UploadPhotoCompanyUserRequest;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Modules\CompanyUser\Services\CompanyUserDatatatusService;
use Modules\CompanyUser\Services\CompanyUserImageValidationService;
use Modules\CompanyUser\Services\CompanyUserIUploadmageService;
use Modules\CompanyUser\Services\CompanyUserWidgetService;
use Modules\CompanyUser\Services\IdentityDataService;
use Modules\CompanyUser\Services\SendOtpService;
use Modules\CompanyUser\Services\ValidateOtpService;
use Modules\CompanyUser\Services\VerifyCompanyUserContactInfoService;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\Uuid;

class CompanyUserProfileController extends Controller
{
    public function __construct(
        private CompanyUserCRUDService               $companyUserService,
        private CompanyUserImageValidationService    $companyUserImageValidationService,
        private CompanyUserIUploadmageService        $companyUserIUploadImageService,
        private UpdateCompanyUserDataInfoHandler     $updateCompanyUserDataInfoHandler,
        private UpdateCompanyUserContactInfoHandler  $updateCompanyUserContactInfoHandler,
        private UpdateCompanyUserIdentityDataHandler $updateCompanyUserIdentityDataHandler,
        private VerifyCompanyUserContactInfoService  $verifyCompanyUserContactInfoService,
        private SendOtpService                       $sendOtpService,
        private ValidateOtpService                   $validateOtpService,
        private IdentityDataService                  $identityDataService,
        private CompanyUserWidgetService             $companyUserWidgetService,
        private CompanyUserDatatatusService          $companyUserDatatatusService,
        private UserRepository                       $userRepository
    )
    {
    }

    public function profile(GetCompanyUserRequest $request): JsonResponse
    {
        $userId = $request->route('id') ? Uuid::fromString($request->route('id')) : auth()->user()->id;

        $userData = $this->userRepository->getUser($userId);

        $user = $this->companyUserService->get(
            Uuid::fromString($userData->global_company_user_id),
        );

        $presenter = new CompanyUserPresenter($user, (string)$userId);

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
            $userId = $request->route('user_id') ? Uuid::fromString($request->route('user_id')) : auth()->user()->id;

            $companyUser = $this->companyUserIUploadImageService->uploadFile($request, $userId);

            $presenter = new CompanyUserImagePresenter($companyUser);
            return Json::item($presenter->getData(), [], "Photo uploaded successfully");

        } catch (\Exception $e) {
            return Json::error('Something went wrong, please try again later.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function showDataInfo(GetCompanyUserRequest $request)
    {
        $userId = $request->route('id') ? Uuid::fromString($request->route('id')) : auth()->user()->id;

        $userData = $this->userRepository->getUser($userId);

        $presenter = new CompanyUserDataInfoPresenter($userData);

        return Json::item($presenter->getData());
    }

    public function updateDataInfo(UpdateCompanyDataInfoUserRequest $request)
    {
        $userId = $request->route('id') ? Uuid::fromString($request->route('id')) : auth()->user()->id;

        $user = $this->userRepository->getUser($userId);

        $command = $request->createUpdateCompanyUserCommand();
        $command->global_id = Uuid::fromString($user->global_company_user_id);

        $this->updateCompanyUserDataInfoHandler->handle($command);

        $item = $this->companyUserService->getGlobalId($command->global_id);

        $presenter = new CompanyUserPresenter($item);

        return Json::item($presenter->getData());
    }

    public function sendOtp(SendEmailOtpRequest $request)
    {
        $userId = $request->route('id') ? Uuid::fromString($request->route('id')) : auth()->user()->id;

        $user = $this->userRepository->getUser($userId);

        $command = $request->updateEmailOtpCommand();
        $command->name = $user->name;
        $otpData = $this->sendOtpService->sendOtp($command, $userId);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'data' => $otpData,
        ]);
    }

    public function validateOtp(ValidateOtpRequest $request)
    {
        try {
            $userId = $request->route('id') ? Uuid::fromString($request->route('id')) : auth()->user()->id;

            $createValidateOtpDTO = $request->createValidateOtpDTO();

            $status = $this->validateOtpService->validateOtp($createValidateOtpDTO, $userId, $request->get('type'));

            return Json::item(["status" => $status]);

        } catch (\Throwable $e) {
            return Json::error(__("validation.invalid-otp"), 421, httpStatus: 421);
        }
    }

    public function showContactInformation(GetCompanyUserRequest $request)
    {
        $userId = $request->route('id') ? Uuid::fromString($request->route('id')) : auth()->user()->id;

        $userData = $this->userRepository->getUser($userId);

        $user = $this->companyUserService->get(
            Uuid::fromString($userData->global_company_user_id),
        );

        $presenter = new CompanyContactInfoPresenter($userData);

        return Json::item($presenter->getData());
    }

    public function updateContactInformation(UpdateCompanyContactInfoUserRequest $request)
    {
        $userId = $request->route('id') ? Uuid::fromString($request->route('id')) : auth()->user()->id;

        $user = $this->userRepository->getUser($userId);

        $command = $request->createUpdateCompanyUserCommand();
        $command->global_id = Uuid::fromString($user->global_company_user_id);

        $this->updateCompanyUserContactInfoHandler->handle($command);

        $item = $this->companyUserService->getGlobalId($command->global_id);

        $presenter = new CompanyUserPresenter($item);

        return Json::item($presenter->getData());
    }

    public function identityData(IdentityDataRequest $request)
    {
        $userId = $request->route('id') ? Uuid::fromString($request->route('id')) : auth()->user()->id;

        $user = $this->userRepository->getUser($userId);

        $command = $request->updateIdentityDataCommand();
        $command->global_id = Uuid::fromString($user->global_company_user_id);

        $this->updateCompanyUserIdentityDataHandler->handle($command);

        $item = $this->companyUserService->getGlobalId($command->global_id);

        $this->identityDataService->uploadFile($request, $command->global_id);

        $presenter = new CompanyIdentityDataPresenter($item);

        return Json::item($presenter->getData());
    }

    public function showidentityData(GetCompanyUserRequest $request)
    {
        $userId = $request->route('id') ? Uuid::fromString($request->route('id')) : auth()->user()->id;

        $userData = $this->userRepository->getUser($userId);

        $user = $this->companyUserService->get(
            Uuid::fromString($userData->global_company_user_id),
        );

        $presenter = new CompanyIdentityDataPresenter($user);

        return Json::item($presenter->getData());
    }

    public function widget(GetCompanyUserRequest $request): JsonResponse
    {
        $userId = Uuid::fromString($request->route('id'));

        $user = $this->userRepository->getUser($userId);

        $presenter = $this->companyUserWidgetService->getCompanyStatistics(
            Uuid::fromString($user->company_id),
            Uuid::fromString($user->global_company_user_id),
        );

        return Json::item($presenter->getData());
    }

    public function dataStatus(GetCompanyUserRequest $request): JsonResponse
    {
        $userId = Uuid::fromString($request->route('id'));

        $user = $this->userRepository->getUser($userId);

        $getCompanyStatistics = $this->companyUserDatatatusService->getDatatatus(
            Uuid::fromString($user->company_id),
            Uuid::fromString($user->global_company_user_id),
        );

        return Json::item($getCompanyStatistics);
    }

}
