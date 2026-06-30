<?php

namespace Modules\Auth\Handlers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Commands\ForgetPasswordCommand;
use Modules\Auth\Notifications\ResetPassword;
use Modules\Auth\Repositories\OtpRepository;
use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Modules\User\Repositories\UserRepository;
use Modules\User\Services\UserCRUDService;
use Ramsey\Uuid\Uuid;

class MakeOtpHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private UserCRUDService $userCRUDService,

        private SendOtpEmail $sendOtpEmail,
        private OtpRepository $otpRepository

    ) {
    }

    public function handle( ForgetPasswordCommand $command )
    {
        $otp = $this->otpRepository->getOtpDataByIdentifier( $command->getIdentifier());
        if (!empty($otp) && Carbon::parse($otp->created_at)->diffInMinutes(Carbon::now())< 3)
        {
            throw new \ErrorException(__("validation.can-not-resend-before",["minute"=>3]), 400);

        }

        $this->sendOtpEmail->resetPassword($command->getIdentifier(), 0, ["sms"]);

    }
}
