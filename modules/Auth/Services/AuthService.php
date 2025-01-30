<?php

namespace Modules\Auth\Services;

use Carbon\Carbon;
use Ichtrojan\Otp\Otp;
use Modules\Auth\Commands\ResendOtpCommand;
use Modules\Auth\Commands\ResetPasswordCommand;
use Modules\Auth\DTO\LoginDTO;
use Modules\Auth\DTO\LoginWithOtpDTO;
use Modules\Auth\Handlers\LogoutHandler;
use Modules\Auth\Repositories\OtpRepository;
use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Modules\Setting\Services\SettingCRUDService;
use Modules\User\Repositories\UserRepository;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        private LogoutHandler      $logoutHandler,
        private UserRepository     $userRepository,
        private OtpRepository      $otpRepository,
        private SendOtpEmail       $sendOtpEmail,
        private SettingCRUDService $settingCRUDService
    )
    {
    }

    public function login(LoginDTO $authDTO)
    {
        $isContinueWithOTP = $this->settingCRUDService->getValue('continue_with_otp');
        if ($isContinueWithOTP) {
            $user = $this->userRepository->getUserByEmail($authDTO->getEmail());
            $this->sendOtpEmail->loginWithOtp($user->id);
            return [null, $user];
        }

        $token = JWTAuth::attempt($authDTO->toArray());
        if (!$token) {
            throw new \ErrorException(__("validation.invalid-credential"), 403);
        }
        $user = auth()->user();
        return [$token, $user];
    }


    public function loginWithOtp(LoginWithOtpDTO $loginWithOtpDTO)
    {
        $isContinueWithOTP = $this->settingCRUDService->getValue('continue_with_otp');
        if (!$isContinueWithOTP) {
            throw new \ErrorException(__("validation.invalid-to-login-with-otp"), 403);
        }
        if ((new Otp)->validate($loginWithOtpDTO->getEmail(), $loginWithOtpDTO->getOtp())->status == false) {
            throw new \ErrorException(__("validation.invalid-otp"), 401);
        }


        $user = $this->userRepository->getUserByEmail($loginWithOtpDTO->getEmail());

        $token = JWTAuth::fromUser($user);

        return [$token, $user];

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

    public function resendOtp(ResendOtpCommand $resendOtpCommand)
    {
        $isContinueWithOTP = $this->settingCRUDService->getValue('continue_with_otp');
        if (!$isContinueWithOTP) {
            throw new \ErrorException(__("validation.invalid-to-login-with-otp"), 403);
        }
        $otp = $this->otpRepository->getOtpDataByIdentifier($resendOtpCommand->getEmail());

        if (Carbon::parse($otp->created_at)->diffInMinutes(Carbon::now()) < 3) {
            throw new \ErrorException(__("validation.can-not-resend-before", ["minute" => 3]), 400);

        }

        $user = $this->userRepository->getUserByEmail($resendOtpCommand->getEmail());
        $this->sendOtpEmail->loginWithOtp($user->id);

    }


}
