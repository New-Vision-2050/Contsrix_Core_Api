<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Modules\Auth\Requests\ForgetPasswordRequest;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\LogoutRequest;
use Modules\Auth\Requests\ResetPasswordRequest;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\Interfaces\SendOtp;
use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Ramsey\Uuid\Uuid;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
    ) {
    }

    public function login(LoginRequest $request)
    {
        return $this->authService->login($request->createLoginDTO())->loginResponse();
    }

    public function logout(LogoutRequest $request)
    {
        $this->authService->logout();

        return Json::buildItems('message', "success");
    }

    public function forgetPassword(ForgetPasswordRequest $request)
    {
        /** @var SendOtpEmail $sendOtpEmail */
        $sendOtpEmail = app()->make(SendOtpEmail::class);
        $sendOtpEmail->send(Uuid::fromString($request->user()->id));

        return Json::buildItems(key: 'message', data: "success", httpStatus: 500);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        if ($this->authService->ResetPassword($request->createResetPasswordCommand())) {
            return Json::buildItems('message', "success");
        } else {
            return response(["message" => "Invalid otp", 401]);
        }
    }
}
