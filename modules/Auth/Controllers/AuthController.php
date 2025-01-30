<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Modules\Auth\Handlers\MakeOtpHandler;
use Modules\Auth\Requests\ForgetPasswordRequest;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\LoginWithOtpRequest;
use Modules\Auth\Requests\LogoutRequest;
use Modules\Auth\Requests\ResetPasswordRequest;
use Modules\Auth\Services\AuthService;
use Modules\User\Presenters\UserPresenter;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private MakeOtpHandler $makeOtpHandler,
    ) {
    }

    public function login(LoginRequest $request)
    {
        $loginDTO = $request->createLoginDTO();
        try {
            [$token, $user] = $this->authService->login($loginDTO);
        } catch (\Exception $e) {
            return Json::buildItems(data: ["message" => $e->getMessage()], httpStatus: 403);
        }

        if (empty($token)) {
            return Json::buildItems(data: ["message" => "success", "continue_with_otp" => 1]);
        }
        $userPresenter = (new UserPresenter($user))->getData();

        return Json::buildItems(data: ["message" => "success", "token" => $token, "user" => $userPresenter]);
    }

    public function loginWithOtp(LoginWithOtpRequest $request)
    {
        try {
            [$token, $user] = $this->authService->loginWithOtp($request->createLoginDTO());
        } catch (\Exception $e) {
            return Json::buildItems(data:["message" => $e->getMessage()], httpStatus: 403);
        }

        $userPresenter = (new UserPresenter($user))->getData();

        return Json::buildItems(data:["message" => "success", "token" => $token, "user" => $userPresenter]);
    }


    public function logout(LogoutRequest $request)
    {
        $this->authService->logout();

        return Json::buildItems(data:["message" => "success"]);
    }

    public function forgetPassword(ForgetPasswordRequest $request)
    {
        $command = $request->createForgetPasswordCommand();
        $this->makeOtpHandler->handle($command);


        return Json::buildItems(null, ["message" => "success"], "", 200);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $this->authService->ResetPassword($request->createResetPasswordCommand());
        } catch (\Exception $e) {
            return Json::buildItems(data: ["message" => $e->getMessage()], httpStatus:  401);
        }

        return Json::buildItems(data: ["message" => "success"]);
    }

}
