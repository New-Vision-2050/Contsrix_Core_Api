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
use Modules\Auth\Notifications\SendOtpForNewEmailChange;
use App\Mail\OtpMail;
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
    // Get the user from the database
    $user = $this->userRepository->find($user_id);

    // Generate OTP
    $otpData = $command->toArray();
    $otp = (new Otp)->generate($command->email, 'numeric', 5, 20)->token;
    $otpData['otp'] = $otp;

    // Set email configuration dynamically
    $mailClass = new \App\Http\Controllers\HelperClass\MailClass();
    $mailClass->setConfig(); // Ensure the configuration is set from DB

    // Send the OTP to the new email address using Mail facade
    Mail::to($command->email)->send(new OtpMail($otpData));

    return $otpData;
}
}
