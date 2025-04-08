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
        private SendOtpEmail   $sendOtpEmail
    )
    {

    }

    public function authMailData($command)
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
    public function sendOtpForEmailChange($command)
    {
        $mailClass = new \App\Http\Controllers\HelperClass\MailClass();
        $mailClass->setConfig();

        Mail::to($command->email)->send(new OtpMail($this->authMailData($command)->toArray()));

    }
}
