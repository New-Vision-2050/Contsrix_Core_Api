<?php

namespace Modules\Auth\Services\OtpServices;

use Carbon\Carbon;

class OtpService
{
    public function makeOtp($length = 5)
    {
        $max = 9;
        for ($i = 1 ; $i <= $length ; $i++) {
            $max += 9 * (10 ^ $i);
        }
        $code = rand(10 ^ ($length - 1), $max);
        $minutes = 15;

        return ["otp", $code, "otp_expire" => Carbon::now()->addMinutes($minutes)];

    }
}
