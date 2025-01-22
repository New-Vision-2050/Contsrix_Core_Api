<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Facade\Json;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Handlers\MakeOtpHandler;
use Modules\Auth\Requests\ForgetPasswordRequest;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\LogoutRequest;
use Modules\Auth\Requests\ResetPasswordRequest;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\Interfaces\SendOtp;
use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Modules\User\Models\User;
use Modules\User\Presenters\UserPresenter;
use Ramsey\Uuid\Uuid;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private MakeOtpHandler $makeOtpHandler,
    ) {
    }

    public function login(LoginRequest $request)
    {
        [$token , $user] = $this->authService->login($request->createLoginDTO());
        if (!$token) {
            return Json::buildItems(null,["message"=>"unauthenticated"],"",401);
        }
        $userPresenter = (new UserPresenter($user))->getData();
        return Json::buildItems(null,["message"=>"success","token"=>$token,"user"=>$userPresenter],"",200);

    }

    public function logout(LogoutRequest $request)
    {

        $this->authService->logout();
        return Json::buildItems(null, ["message"=>"success"],"",200);
    }

    public function forgetPassword(ForgetPasswordRequest $request)
    {
        $command  = $request->createForgetPasswordCommand();
        $this->makeOtpHandler->handle($command);

        return Json::buildItems(null,["message"=>"success"],"",200);

    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $this->authService->ResetPassword($request->createResetPasswordCommand());
        }
        catch (\Exception $e){
            return Json::buildItems(null,["message"=>"otp not fount or expired"],"",401);

        }

        return Json::buildItems(null,["message"=>"success"],"",200);



    }

}
