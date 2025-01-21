<?php

namespace Modules\Auth\Services\OtpServices;

use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\UuidInterface;

class SendOtpEmail extends OtpService
{
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function send(UuidInterface $userId)
    {
        $user = $this->userRepository->find($userId);
        $data = array();
        $data['email'] = $user->email;
        $data['otp'] = $this->makeOtp(5);
        $data['name'] = $user->name;
        $data['minutes'] = 20;
        $data['url'] = "";
        Mail::to($data['email'])->send(new ResetPasswordMail($data));
    }
}
