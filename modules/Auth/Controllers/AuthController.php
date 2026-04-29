<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Modules\Auth\Handlers\ChangeEmailHandler;
use Modules\Auth\Handlers\MakeOtpHandler;
use Modules\Auth\Requests\ChangeEmailRequest;
use Modules\Auth\Requests\CheckVerificationQuestionRequest;
use Modules\Auth\Requests\ForgetPasswordRequest;
use Modules\Auth\Requests\getDataForLoginAsAdminRequest;
use Modules\Auth\Requests\GetLoginWaysRequest;
use Modules\Auth\Requests\LoginAsAdminRequest;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\LoginStepAlternativeRequest;
use Modules\Auth\Requests\LoginStepsRequest;
use Modules\Auth\Requests\LoginWithOtpRequest;
use Modules\Auth\Requests\LogoutRequest;
use Modules\Auth\Requests\ResendOtpRequest;
use Modules\Auth\Requests\ResetPasswordRequest;
use Modules\Auth\Requests\ValidateOtpRequest;
use Modules\Auth\Services\AuthService;
use Modules\Setting\Presenters\LoginWayWithSpecificStepPresenter;
use Modules\User\Presenters\UserPresenter;
use Modules\User\Services\UserCRUDService;
use Ramsey\Uuid\Uuid;

class AuthController extends Controller
{
    public function __construct(
        private AuthService        $authService,
        private MakeOtpHandler     $makeOtpHandler,
        private UserCRUDService    $userCRUDService,
        private ChangeEmailHandler $changeEmailHandler,
    )
    {
    }

    public function login(LoginRequest $request)
    {
        $loginDTO = $request->createLoginDTO();

        [$token, $user] = $this->authService->login($loginDTO);


        if (empty($token)) {
            return Json::item(["continue_with_otp" => 1]);
        }
        $userPresenter = (new UserPresenter($user))->getData();

        return Json::item(["token" => $token, "user" => $userPresenter], message: "Logged in");
    }

    public function loginWithOtp(LoginWithOtpRequest $request)
    {

            [$token, $user] = $this->authService->loginWithOtp($request->createLoginDTO());


        $userPresenter = (new UserPresenter($user))->getData();

        return Json::item(["token" => $token, "user" => $userPresenter]);
    }


    public function logout(LogoutRequest $request)
    {
        $this->authService->logout();

        return Json::success("logged out successfully");

    }


    public function forgetPassword(ForgetPasswordRequest $request)
    {
        $command = $request->createForgetPasswordCommand();

            $this->makeOtpHandler->handle($command);


        return Json::success("success");
    }

    public function resetPassword(ResetPasswordRequest $request)
    {

        $this->authService->ResetPassword($request->createResetPasswordCommand());


        return Json::success("success");
    }

    public function validateOtp(ValidateOtpRequest $request)
    {
        return Json::item(["token" => $this->authService->validateOtp($request->createValidateOtpDTO())]);
    }

    public function resendOtp(ResendOtpRequest $resendOtpRequest)
    {
        $command = $resendOtpRequest->createResendOtpCommand();

        $this->authService->resendOtp($command);

        return Json::success("success");
    }

    public function getLoginWays(GetLoginWaysRequest $request)
    {
        $user = $this->userCRUDService->getUserByIdentifier($request->createGetLoginWaysDTO()->getIdentifier());
//        $companyUserCompany = $user->companyUserCompanies->where("company_id", tenant("id"))->first();
//        if ($companyUserCompany->status == 0)
//            return Json::error("user is not active");

        [$loginWayId, $token, $step, $canSetPass,$firstLogin] = $this->authService->getLoginWays($request->createGetLoginWaysDTO());

        return Json::item([
            "login_way" => (new LoginWayWithSpecificStepPresenter(Uuid::fromString($loginWayId), $step, $user))->getData(),
            "token" => $token,
            "can_set_pass" => $canSetPass,
            "first_login"=> $firstLogin
        ]);
    }

    public function loginBySteps(LoginStepsRequest $request)
    {
        $loginDTO = $request->createLoginStepDTO();

            [$loginWayId, $token, $nextStep] = $this->authService->loginBySteps($loginDTO);
            $user = $this->userCRUDService->getUserByIdentifier($loginDTO->getIdentifier());

            $this->userCRUDService->updateFcmToken($user->id);

        $userPresenter = (new UserPresenter($user))->getData();
        if ($nextStep == null) {
            return Json::item(["login_way" => (new LoginWayWithSpecificStepPresenter(Uuid::fromString($loginWayId), $nextStep, $user))->getData(), "token" => $token, "user" => $userPresenter]);
        }
        return Json::item(["login_way" => (new LoginWayWithSpecificStepPresenter(Uuid::fromString($loginWayId), $nextStep, $user))->getData(), "token" => $token]);
    }

    public function checkAnswers(CheckVerificationQuestionRequest $request)
    {

        [$res, $token] = $this->authService->checkQuestionAnswer($request->createLoginDTO());
        if (!$res) {
            return Json::error(__("validation.invalid-answers"), httpStatus: 401);
        }

        return Json::item(["token" => $token]);
    }

    public function loginStepAlternative(LoginStepAlternativeRequest $request)
    {
        [$loginWayId, $token, $step] = $this->authService->loginStepAlternative($request->createLoginStepAlternativeDTO());

        $user = $this->userCRUDService->getUserByIdentifier($request->createLoginStepAlternativeDTO()->getIdentifier());


        return Json::item(["login_way" => (new LoginWayWithSpecificStepPresenter(Uuid::fromString($loginWayId), $step, $user))->getData(), "token" => $token]);
    }

    public function changeEmail(ChangeEmailRequest $changeEmailRequest)
    {

        $command = $changeEmailRequest->createChangeEmailCommand();
        $this->changeEmailHandler->handle($command);

        return Json::success("success");
    }


    public function getDataForLoginAsAdmin(getDataForLoginAsAdminRequest $request)
    {
       $data= $this->authService->getDataForLoginAsAdmin($request->company_id);

        return Json::item($data);
    }

    public function loginAsAdmin(LoginAsAdminRequest $request)
    {
       [$token,$user]= $this->authService->loginAsAdmin($request->token);


        return Json::item(["token" => $token,"user" => (new UserPresenter($user))->getData()]);
    }

}
