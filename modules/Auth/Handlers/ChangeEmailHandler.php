<?php

namespace Modules\Auth\Handlers;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Commands\ChangeEmailCommand;
use Modules\Auth\Repositories\VerficationDataRepository;
use Modules\Auth\Services\OtpServices\SendOtpEmail;
use Modules\User\Repositories\UserRepository;

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
            $verificationData = $this->verficationDataRepository->validateToken($changeEmailCommand->getToken());
            if (!isset($verificationData->data["change_email"]) || $verificationData->data["change_email"] != 1) {
                throw new \ErrorException(__("validation.invalid-token"), 403);
            }

            $this->userRepository->updateWhere(['email' => $changeEmailCommand->getEmail()], ["email" => $changeEmailCommand->getNewEmail()]);
            $this->sendOtpEmail->sendOtpForEmailChange($verificationData->user_id);

            //TODO: fire event to update user authentication in auth project

            DB::commit();
        }
        catch (\Exception $e) {
            DB::rollBack();
            throw new \ErrorException($e->getMessage(), 400);
        }
    }
}
