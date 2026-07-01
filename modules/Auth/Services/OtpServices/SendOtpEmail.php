<?php

namespace Modules\Auth\Services\OtpServices;

use Ichtrojan\Otp\Otp;
use Modules\Auth\DataClasses\AuthMailData;
use Modules\Auth\Notifications\ResetPassword;
use Modules\Auth\Notifications\SendOtpForEmailChange;
use Modules\Auth\Notifications\SendOtpForLogin;
use Modules\User\Repositories\UserRepository;
use Modules\User\Services\UserCRUDService;
use Ramsey\Uuid\UuidInterface;

class SendOtpEmail
{
    public function __construct(private UserRepository $userRepository,private UserCRUDService $userCRUDService)
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
private function  createAuthMailForLoginStepData($identifier)
    {
        $user = $this->userCRUDService->getUserByIdentifier($identifier);

        return new AuthMailData(
            $identifier,
            (new Otp)->generate($identifier, 'numeric', 5, 20)->token,
            $user->name,20,
            ""
        );
    }

    public function resetPassword($identifier, $firstLogin = 0, array $types = ["mail"]){
        $data =$this->createAuthMailForLoginStepData($identifier)->toArray();
        $user = $this->userCRUDService->getUserByIdentifier($identifier);
        $data['first_login'] = $firstLogin;

        $user->notify(new ResetPassword($data, $types));
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
    public function loginStepOtp( $identifier, array $types = ["mail"])
    {
        $data =$this->createAuthMailForLoginStepData($identifier)->toArray();
        $user = $this->userCRUDService->getUserByIdentifier($identifier);
        $user->notify(new SendOtpForLogin($data,$types));
    }

}
