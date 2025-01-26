<?php

namespace Modules\Auth\Handlers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Commands\ForgetPasswordCommand;
use Modules\Auth\Notifications\ResetPassword;
use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\Uuid;

class MakeOtpHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private SendOtpEmail $sendOtpEmail

    ) {
    }

    public function handle( ForgetPasswordCommand $command )
    {
        $user = $this->userRepository->getUserByEmail($command->getEmail());

        $this->sendOtpEmail->send($user->id)->resetPassword();
    }
}
