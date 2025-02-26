<?php

namespace Modules\Auth\Services\OtpServices;

use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\DataClasses\AuthMailData;
use Modules\Auth\Notifications\ResetPassword;
use Modules\Auth\Notifications\SendOtpForEmailChange;
use Modules\Auth\Notifications\SendOtpForLogin;
use Modules\Auth\Services\Interfaces\SendOtp;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\UuidInterface;

class SendOtpEmail
{
    public function __construct(private UserRepository $userRepository)
    {
    }


    private function  createAuthMailData(UuidInterface $userId)
    {
        $user = $this->userRepository->find($userId);

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

    public function sendOtpForEmailChange(UuidInterface $userId){
        $user = $this->userRepository->find($userId);
        $user->notify(new SendOtpForEmailChange($this->createAuthMailData($userId)->toArray()));

    }

    public function loginWithOtp(UuidInterface $userId , array $types = ["mail"])
    {
        $data =$this->createAuthMailData($userId)->toArray();
        $user = $this->userRepository->find($userId);
        $user->notify(new SendOtpForLogin($data,$types));
    }

}
