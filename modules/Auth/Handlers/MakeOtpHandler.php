<?php

namespace Modules\Auth\Handlers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Commands\ForgetPasswordCommand;
use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\Uuid;

class MakeOtpHandler
{
    public function __construct(
        private UserRepository $userRepository,

    ) {
    }

    public function handle( ForgetPasswordCommand $command )
    {
        $user = $this->userRepository->getUserByEmail($command->getEmail());

        /** @var SendOtpEmail $sendOtpEmail */

        $sendOtpEmail = app()->make(SendOtpEmail::class);
        $sendOtpEmail->send(Uuid::fromString($user->id));
    }
}
