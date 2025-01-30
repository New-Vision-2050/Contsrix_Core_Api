<?php

namespace Modules\Auth\Services\OtpServices;

use App\Mail\ResetPasswordMail;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\DataClasses\AuthMailData;
use Modules\Auth\Notifications\ResetPassword;
use Modules\Auth\Notifications\SendOtpForLogin;
use Modules\Auth\Services\Interfaces\SendOtp;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\UuidInterface;

class SendOtpEmail
{
    private  $user;
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }


    private function  createAuthMailData(UuidInterface $userId)
    {
        $user = $this->userRepository->find($userId);
        $this->user = $user;

        return new AuthMailData(
            $user->email,
            (new Otp)->generate($user->email, 'numeric', 5, 20)->token,
            $user->name,20,
            ""
        );
    }

    public function resetPassword(UuidInterface $userId){
        $user = $this->userRepository->find($userId);
        $user->notify(new ResetPassword($this->createAuthMailData($userId)->toArray()));

    }

    public function loginWithOtp(UuidInterface $userId)
    {
        $data =$this->createAuthMailData($userId)->toArray();
        $user = $this->userRepository->find($userId);
        $user->notify(new SendOtpForLogin($data));
    }

}
