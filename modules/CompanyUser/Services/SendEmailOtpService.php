<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\Auth\DataClasses\AuthMailData;
use Ichtrojan\Otp\Otp;
use Modules\Auth\Notifications\SendOtpForEmailChange;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Support\Facades\Mail;

class SendEmailOtpService
{
    public function __construct(
        private UserRepository $userRepository,
        private SendOtpEmail          $sendOtpEmail
    )
    {

    }

    public function send($command)
    {
        $otp = (new Otp)->generate($command->email, 'numeric', 5, 20)->token;

        return new AuthMailData(
            $command->email,
            $otp,
            $command->name,
            20,
            ""
        );
    }
    public function sendOtpForEmailChange($command, UuidInterface $user_id)
    {
        $user = $this->userRepository->find($user_id);
        $otpData = $command->toArray();

        // Ensure 'otp' key is in the data
        $otp = (new Otp)->generate($command->email, 'numeric', 5, 20)->token;
        $otpData['otp'] = $otp;

        // Now, send the OTP via the notification
        $user->notifyNow(new SendOtpForEmailChange($otpData));

        return $otpData;
    }
}
