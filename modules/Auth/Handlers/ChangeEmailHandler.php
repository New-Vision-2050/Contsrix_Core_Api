<?php

namespace Modules\Auth\Handlers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Commands\ChangeEmailCommand;
use Modules\Auth\Repositories\VerficationDataRepository;
use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Modules\User\Repositories\UserRepository;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChangeEmailHandler
{
    public function __construct(
        private UserRepository            $userRepository,
        private VerficationDataRepository $verficationDataRepository,
        private SendOtpEmail              $sendOtpEmail
    )
    {
    }

    public function handle(ChangeEmailCommand $changeEmailCommand)
    {
        try {
            DB::BeginTransaction();
            $verficationData = $this->verficationDataRepository->validateToken($changeEmailCommand->getToken());
            if (!isset($verficationData->data["change_email"]) || $verficationData->data["change_email"] != 1) {
                throw new \ErrorException(__("validation.invalid-token"), 403);
            }

            $this->userRepository->updateWhere(['email' => $changeEmailCommand->getEmail()], ["email" => $changeEmailCommand->getNewEmail()]);
            $this->sendOtpEmail->sendOtpForEmailChange($verficationData->user_id);

            //TODO: fire event to update user authentication in auth project

            DB::commit();
        }
        catch (\Exception $e) {
            DB::rollBack();
            throw new \ErrorException($e->getMessage(), 400);
        }


    }
}
