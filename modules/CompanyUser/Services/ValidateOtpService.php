<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Ichtrojan\Otp\Otp;
use Modules\Auth\DTO\ValidateOtpDTO;

class ValidateOtpService
{
    public function validateOtp(ValidateOtpDTO $validateOtpDTO)
    {
        if ((new Otp)->validate($validateOtpDTO->getIdentifier(), $validateOtpDTO->getOtp())->status == true) {
            return true;
        }
        throw new \ErrorException(__("validation.invalid-otp"), 401);
    }
}
