<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use BasePackage\Shared\Facade\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Commands\ForgetPasswordCommand;
use Modules\Auth\Handlers\DeleteAuthHandler;
use Modules\Auth\Handlers\UpdateAuthHandler;
use Modules\Auth\Presenters\AuthPresenter;
use Modules\Auth\Requests\ForgetPasswordRequest;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\DeleteAuthRequest;
use Modules\Auth\Requests\GetAuthListRequest;
use Modules\Auth\Requests\GetAuthRequest;
use Modules\Auth\Requests\LogoutRequest;
use Modules\Auth\Requests\ResetPasswordRequest;
use Modules\Auth\Requests\UpdateAuthRequest;
use Modules\Auth\Services\AuthCRUDService;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\Interfaces\SendOtp;
use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Ramsey\Uuid\Uuid;

class AuthController extends Controller
{
    private SendOtp $sendOtp;

    public function __construct(

        private AuthService $authService,
    )
    {

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
        $this->sendOtp = new SendOtpEmail($request->createForgetPasswordCommand()->getEmail());
        $this->sendOtp->send();
        return Json::buildItems('message', "success");

    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        if ($this->authService->ResetPassword($request->createResetPasswordCommand()))
            return Json::buildItems('message', "success");
        else
            return response(["message" => "Invalid otp", 401]);

    }


}
