<?php

namespace Modules\Auth\Services;

use Ichtrojan\Otp\Otp;
use Modules\Auth\Commands\ResetPasswordCommand;
use Modules\Auth\DTO\LoginDTO;
use Modules\Auth\DTO\LoginWithOtpDTO;
use Modules\Auth\Handlers\LogoutHandler;
use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Modules\User\Repositories\UserRepository;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
//    private $token;

    public function __construct(
//        private AuthRepository $repository,
        private LogoutHandler  $logoutHandler,
        private UserRepository $userRepository,
        private SendOtpEmail   $sendOtpEmail,
    )
    {
    }

    public function login(LoginDTO $authDTO)

    {
        $token = JWTAuth::attempt($authDTO->toArray());
        if (!$token) {
            throw new \ErrorException(__("validation.invalid-credential"), 403);
        }
        $user = auth()->user();
        if ($authDTO->getContinueWithOtp() == 1) {
            $user = $this->userRepository->getUserByEmail($authDTO->getEmail());
            $this->sendOtpEmail->loginWithOtp($user->id);
            $token = null;//will make token null after login by otp
        }

        return [$token, $user];
    }


    public function loginWithOtp(LoginWithOtpDTO $loginWithOtpDTO)
    {

        if ((new Otp)->validate($loginWithOtpDTO->getEmail(), $loginWithOtpDTO->getOtp())->status == false)

            throw new \ErrorException(__("validation.invalid-otp"), 401);


        $user = $this->userRepository->getUserByEmail($loginWithOtpDTO->getEmail());

        $token = JWTAuth::fromUser($user);


        return [$token , $user];

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

            $this->userRepository->updateUser($user->id, ["password" => $resetPasswordCommand->getPassword()]);

            return $this;
        }
        throw new \ErrorException(__("validation.invalid-otp"), 401);

    }


}
