<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Ichtrojan\Otp\Models\Otp;
use Modules\Auth\Handlers\MakeOtpHandler;
use Modules\Auth\Requests\ForgetPasswordRequest;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\LoginWithOtpRequest;
use Modules\Auth\Requests\LogoutRequest;
use Modules\Auth\Requests\ResendOtpRequest;
use Modules\Auth\Requests\ResetPasswordRequest;
use Modules\Auth\Services\AuthService;
use Modules\User\Presenters\UserPresenter;

class AuthController extends Controller
{
    public function __construct(
        private AuthService    $authService,
        private MakeOtpHandler $makeOtpHandler,
    )
    {
    }

    public function login(LoginRequest $request)
    {
        $loginDTO = $request->createLoginDTO();
        try {
            [$token, $user] = $this->authService->login($loginDTO);
        } catch (\Exception $e) {
            return Json::buildItems(data: ["msg" => $e->getMessage()], httpStatus: $e->getCode());
        }

        if (empty($token)) {
            return Json::buildItems(data: ["msg" => "success", "continue_with_otp" => 1]);
        }
        $userPresenter = (new UserPresenter($user))->getData();

        return Json::buildItems(data: ["msg" => "success", "token" => $token, "user" => $userPresenter]);
    }

    public function loginWithOtp(LoginWithOtpRequest $request)
    {
        try {
            [$token, $user] = $this->authService->loginWithOtp($request->createLoginDTO());
        } catch (\Exception $e) {
            return Json::buildItems(data: ["msg" => $e->getMessage()], httpStatus: $e->getCode());
        }

        $userPresenter = (new UserPresenter($user))->getData();

        return Json::buildItems(data: ["msg" => "success", "token" => $token, "user" => $userPresenter]);
    }


    public function logout(LogoutRequest $request)
    {
        $this->authService->logout();

        return Json::buildItems(data: ["msg" => "success"]);
    }

    public function forgetPassword(ForgetPasswordRequest $request)
    {
        $command = $request->createForgetPasswordCommand();
        try {
            $this->makeOtpHandler->handle($command);

        } catch (\Exception $e) {
            return Json::buildItems(data: ["msg" => $e->getMessage()], httpStatus: $e->getCode());
        }

        return Json::buildItems(null, ["msg" => "success"], "", 200);
    }

    public
    function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $this->authService->ResetPassword($request->createResetPasswordCommand());
        } catch (\Exception $e) {
            return Json::buildItems(data: ["msg" => $e->getMessage()], httpStatus: $e->getCode());
        }

        return Json::buildItems(data: ["msg" => "success"]);
    }

    public
    function resendOtp(ResendOtpRequest $resendOtpRequest)
    {
        $command = $resendOtpRequest->createResendOtpCommand();
        try {
            $this->authService->resendOtp($command);

        } catch (\Exception $e) {
            return Json::buildItems(data: ["msg" => $e->getMessage()], httpStatus: $e->getCode());
        }
        return Json::buildItems(data: ["msg" => "success"]);


    }

}
