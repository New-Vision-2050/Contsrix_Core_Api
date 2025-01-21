<?php

namespace Modules\Auth\Services\OtpServices;

use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Services\Interfaces\SendOtp;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

class SendOtpEmail extends OtpService implements SendOtp
{

    public function __construct($email)
    {
        Parent::__construct((new UserRepository(new User()))->getUserByEmail($email));


    }


    public function send()
    {
        $data = array();
        $data['email'] = $this->user->email;
        $data['otp'] = $this->user->otp;
        $data['name'] = $this->user->name;
        $data['minutes'] = 20;
        $data['url'] = "";
        Mail::to($data['email'])->send(new ResetPasswordMail($data));


    }
}
