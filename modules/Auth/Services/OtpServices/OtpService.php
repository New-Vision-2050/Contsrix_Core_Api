<?php

namespace Modules\Auth\Services\OtpServices;

use Carbon\Carbon;
use Faker\Core\Uuid;
use Modules\User\Repositories\UserRepository;

class OtpService
{
    public function makeOtp($user,$length = 5)
    {

        $code = rand(10000,99999);
        $minutes = 15;
        (new UserRepository($user))->updateUser(\Ramsey\Uuid\Uuid::fromString($user->id),["otp"=> $code, "otp_expire" => Carbon::now()->addMinutes($minutes)]);

        return 1;

    }
}
