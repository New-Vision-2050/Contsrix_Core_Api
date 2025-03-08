<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Handlers\ChangeEmailHandler;
use Modules\Auth\Handlers\MakeOtpHandler;
use Modules\Auth\Requests\ChangeEmailRequest;
use Modules\Auth\Requests\CheckVerificationQuestionRequest;
use Modules\Auth\Requests\ForgetPasswordRequest;
use Modules\Auth\Requests\GetLoginWaysRequest;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\LoginStepAlternativeRequest;
use Modules\Auth\Requests\LoginStepsRequest;
use Modules\Auth\Requests\LoginWithOtpRequest;
use Modules\Auth\Requests\LogoutRequest;
use Modules\Auth\Requests\ResendOtpRequest;
use Modules\Auth\Requests\ResetPasswordRequest;
use Modules\Auth\Services\AuthService;
use Modules\Setting\Presenters\LoginWayWithSpecificStepPresenter;
use Modules\User\Presenters\UserPresenter;
use Modules\User\Services\UserCRUDService;
use Ramsey\Uuid\Uuid;

class AuthController extends Controller
{

    public function __construct(
        private AuthService $authService,
        private MakeOtpHandler $makeOtpHandler,
        private UserCRUDService $userCRUDService,
        private ChangeEmailHandler $changeEmailHandler,
    ) {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $loginDTO = $request->createLoginDTO();
        try {
            [$token, $user] = $this->authService->login($loginDTO);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), httpStatus: 400);
        }

        if (empty($token)) {
            return Json::item(["continue_with_otp" => 1]);
        }
        $userPresenter = (new UserPresenter($user))->getData();

        return Json::item(["token" => $token, "user" => $userPresenter], message: "Logged in");
    }

    public function loginWithOtp(LoginWithOtpRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->loginWithOtp($request->createLoginDTO());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), httpStatus: $e->getCode());
        }
        $request->session()->regenerate();
        $userPresenter = (new UserPresenter($user))->getData();

        return Json::item(["user" => $userPresenter]);
    }


    public function logout(LogoutRequest $request): JsonResponse
    {
        $this->authService->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Json::success("logged out successfully");
    }


    public function forgetPassword(ForgetPasswordRequest $request): JsonResponse
    {
        $command = $request->createForgetPasswordCommand();
        try {
            $this->makeOtpHandler->handle($command);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), httpStatus: $e->getCode());
        }

        return Json::success("success");
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->ResetPassword($request->createResetPasswordCommand());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), httpStatus: $e->getCode());
        }

        return Json::success("success");
    }

    public function resendOtp(ResendOtpRequest $resendOtpRequest): JsonResponse
    {
        $command = $resendOtpRequest->createResendOtpCommand();
        try {
            $this->authService->resendOtp($command);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), httpStatus: $e->getCode());
        }

        return Json::success("success");
    }

    public function getLoginWays(GetLoginWaysRequest $request): JsonResponse
    {
        try {
            [$loginWayId, $token, $step] = $this->authService->getLoginWays($request->createGetLoginWaysDTO());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), httpStatus: $e->getCode());
        }

        return Json::item(
            [
                "login_way" => (new LoginWayWithSpecificStepPresenter(Uuid::fromString($loginWayId), $step))->getData(),
                "token" => $token
            ]
        );
    }

    public function loginBySteps(LoginStepsRequest $request): JsonResponse
    {
        $loginDTO = $request->createLoginStepDTO();

        try {
            [$loginWayId, $token, $nextStep] = $this->authService->loginBySteps($loginDTO);
            $user = $this->userCRUDService->getUserByIdentifier($loginDTO->getIdentifier());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), httpStatus: 500);
        }

        $userPresenter = (new UserPresenter($user))->getData();

        if ($nextStep == null) {
            return Json::item(
                [
                    "login_way" => (new LoginWayWithSpecificStepPresenter(
                        Uuid::fromString($loginWayId), $nextStep
                    ))->getData(),
                    "token" => $token,
                    "user" => $userPresenter
                ]
            );
        }

        return Json::item(
            [
                "login_way" => (new LoginWayWithSpecificStepPresenter(
                    Uuid::fromString($loginWayId), $nextStep
                ))->getData(),
                "token" => $token
            ]
        );
    }

    public function checkAnswers(CheckVerificationQuestionRequest $request): JsonResponse
    {
        try {
            [$res, $token] = $this->authService->checkQuestionAnswer($request->createLoginDTO());
        } catch (\Exception $e) {
            return Json::error($e->getMessage());
        }
        if (!$res) {
            return Json::error(__("validation.invalid-answers"), httpStatus: 401);
        }

        return Json::item(["token" => $token]);
    }

    public function loginStepAlternative(LoginStepAlternativeRequest $request): JsonResponse
    {
        try {
            [$loginWayId, $token, $step] = $this->authService->loginStepAlternative(
                $request->createLoginStepAlternativeDTO()
            );
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), httpStatus: $e->getCode());
        }

        return Json::item(
            [
                "login_way" => (new LoginWayWithSpecificStepPresenter(Uuid::fromString($loginWayId), $step))->getData(),
                "token" => $token
            ]
        );
    }

    public function changeEmail(ChangeEmailRequest $changeEmailRequest): JsonResponse
    {
        try {
            $command = $changeEmailRequest->createChangeEmailCommand();
            $this->changeEmailHandler->handle($command);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), httpStatus: $e->getCode());
        }

        return Json::success("success");
    }

}
