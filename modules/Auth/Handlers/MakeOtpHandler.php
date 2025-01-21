<?php

namespace Modules\Auth\Handlers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\User\Repositories\UserRepository;

class MakeOtpHandler
{
    public function __construct(
        private UserRepository $userRepository,

    ) {
    }

    public function handle( $email ,$length =5 )
    {
        $max=9;
        for ($i=1; $i<=$length; $i++)
        {
            $max+=9*(10^$i);
        }
        $code = rand(10^($length-1), $max) ;
        $minutes = 15;
        $user= $this->userRepository->getUserByEmail($this->email);
        $user->otp = $code;
        $user->otp_expire = Carbon::now()->addMinutes($minutes);
        $user->save();
    }
}
