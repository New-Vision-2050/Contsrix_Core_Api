<?php

namespace Modules\Auth\Services\OtpServices;

use App\Mail\ResetPasswordMail;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Services\Interfaces\SendOtp;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\UuidInterface;

class SendOtpEmail implements SendOtp
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
        $data['otp'] = (new Otp)->generate($user->email, 'numeric', 5, 15)->token;
        $data['name'] = $user->name;
        $data['minutes'] = 20;
        $data['url'] = "";
        Mail::to($data['email'])->send(new ResetPasswordMail($data));
    }
}
