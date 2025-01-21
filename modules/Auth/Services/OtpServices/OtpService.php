<?php

namespace Modules\Auth\Services\OtpServices;

use Carbon\Carbon;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

class OtpService
{
    public $user;

    public function __construct(
       User $user
    )
    {
$this->user = $user;
$this->makeOtp();
    }

    public function makeOtp($length=5)
    {
        $max=9;
        for ($i=1; $i<=$length; $i++)
        {
            $max+=9*(10^$i);
        }
        $code = rand(10^($length-1), $max) ;
        $minutes = 15;
       (new userRepository($this->user))->updateUser($this->user->id,["otp",$code, "otp_expire" =>Carbon::now()->addMinutes($minutes)]);

        return $this;
    }
}
