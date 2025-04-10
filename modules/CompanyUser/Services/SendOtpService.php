<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Modules\Auth\DataClasses\AuthMailData;
use Ichtrojan\Otp\Otp;
use Modules\User\Repositories\UserRepository;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Modules\Auth\DTO\ValidateOtpDTO;
use App\Notifications\Drivers\SMS\MoraSms;

class SendOtpService
{
    public function __construct(
        private UserRepository $userRepository,
        private SendOtpEmail   $sendOtpEmail,
        private MoraSms        $moraSms
    ) {}

    public function sendOtp($command, $user_id)
    {
        return $command->type == 'phone'
            ? $this->sendOtpForPhone($command, $user_id)
            : $this->sendOtpForEmail($command, $user_id);
    }

    public function authMailData($command, $user_id)
    {
        $otp = (new Otp)->generate($command->identifier, 'numeric', 5, 20)->token;

        return new AuthMailData(
            $command->identifier,
            $otp,
            $command->name,
            20,
            ""
        );
    }

    public function sendOtpForEmail($command, $user_id)
    {
        $mailClass = new \App\Http\Controllers\HelperClass\MailClass();
        $mailClass->setConfig();

        $otpData = $this->authMailData($command, $user_id);

        Mail::to($command->identifier)->send(new OtpMail($otpData->toArray()));
    }

    public function sendOtpForPhone($command, $user_id)
    {
        $otp = (new Otp)->generate($command->identifier, 'numeric', 5, 20)->token;

        $message = "Your OTP is: $otp";

        $this->moraSms->to($command->identifier)
                      ->message($message)
                      ->send();
    }

    public function sendOtpVerify(ValidateOtpDTO $validateOtpDTO)
    {
        if ((new Otp)->validate($validateOtpDTO->getIdentifier(), $validateOtpDTO->getOtp())->status == true) {
            return true;
        }
        throw new \ErrorException(__("validation.invalid-otp"), 401);
    }
}
