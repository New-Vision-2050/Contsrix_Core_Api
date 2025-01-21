<?php

namespace Modules\Auth\Services;

use BasePackage\Shared\Facade\Json;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Commands\ResetPasswordCommand;
use Modules\Auth\DTO\LoginDTO;
use Modules\Auth\Handlers\LogoutHandler;
use Modules\Auth\Handlers\MakeOtpHandler;
use Modules\Auth\Repositories\AuthRepository;
use Modules\User\Presenters\UserPresenter;
use Modules\User\Repositories\UserRepository;

class AuthService
{
    public function __construct(
//        private AuthRepository $repository,
        private                $token,
        private LogoutHandler  $logoutHandler,
        private UserRepository   $userRepository,


    )
    {
    }

    public function login(LoginDTO $authDTO)
    {

        $this->token = Auth::guard('api')->attempt($authDTO->toArray());
        return $this;
    }

    public function logout()
    {
        $this->logoutHandler->handle();
        return $this;
    }

    public function ResetPassword(ResetPasswordCommand $resetPasswordCommand)
    {
        $user  = $this->userRepository->searchOtp($resetPasswordCommand->getOtp());

        if($user&& Carbon::parse($user->otp_expire)->format("Y-m-d H:i:s")>=Carbon::now()->format("Y-m-d H:i:s"))
        {
            $this->userRepository->updateUser($user->id  , ["password"=>$resetPasswordCommand->getPassword(),"otp"=>null,"otp_expire"=>null]);
            return 1;
        }
        return 0;


    }






    public function loginResponse()
    {
        if (!$this->token) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            'status' => true,
            'token' => $this->token,

        ]);
    }
}
