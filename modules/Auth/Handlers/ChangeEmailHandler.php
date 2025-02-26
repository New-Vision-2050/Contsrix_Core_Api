<?php

namespace Modules\Auth\Handlers;

use Illuminate\Support\Facades\Auth;
use Modules\Auth\Commands\ChangeEmailCommand;
use Modules\Auth\Repositories\VerficationDataRepository;
use Modules\User\Repositories\UserRepository;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChangeEmailHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private VerficationDataRepository $verficationDataRepository
    ) {
    }

    public function handle( ChangeEmailCommand $changeEmailCommand)
    {
        $verficationData = $this->verficationDataRepository->findOneByOrFail(["token" => $changeEmailCommand->getToken()]);
        if(!isset($verficationData->data["change_email"] )|| $verficationData->data["change_email"] != 1)
        {
            throw new \ErrorException(__("validation.invalid-token"), 403);
        }

      $this->userRepository->updateWhere(['email' => $changeEmailCommand->getEmail()],["email" => $changeEmailCommand->getNewEmail()]);
    }
}
