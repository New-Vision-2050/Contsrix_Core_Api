<?php

namespace Modules\Auth\Services;

use Carbon\Carbon;
use Ichtrojan\Otp\Otp;
use Modules\Auth\Commands\ResetPasswordCommand;
use Modules\Auth\DTO\LoginDTO;
use Modules\Auth\Handlers\LogoutHandler;
use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
//    private $token;

    public function __construct(
//        private AuthRepository $repository,
        private LogoutHandler  $logoutHandler,
        private UserRepository $userRepository,
    )
    {
    }

    public function login(LoginDTO $authDTO)

    {
        return [JWTAuth::attempt($authDTO->toArray()), auth()->user()];
    }

    public function logout()
    {
        $this->logoutHandler->handle();
        return $this;
    }



    public function ResetPassword(ResetPasswordCommand $resetPasswordCommand)
    {


        if ((new Otp)->validate($resetPasswordCommand->getEmail(), $resetPasswordCommand->getOtp())->status == true) {
            $user = $this->userRepository->getUserByEmail($resetPasswordCommand->getEmail());

            $this->userRepository->updateUser(Uuid::fromString($user->id), ["password" => $resetPasswordCommand->getPassword()]);

            return $this;
        }
        throw new \ErrorException('oto not valid or expired', 401);

    }


}
