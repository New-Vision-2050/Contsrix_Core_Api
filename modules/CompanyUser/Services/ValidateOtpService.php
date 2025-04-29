<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Ichtrojan\Otp\Otp;
use Modules\Auth\DTO\ValidateOtpDTO;
use Modules\CompanyUser\Repositories\CompanyUserRepository;

class ValidateOtpService
{
    public function __construct(
        private CompanyUserRepository  $companyUserRepository,
    )
    {
    }
    public function validateOtp(ValidateOtpDTO $validateOtpDTO, $userId, string $type)
    {
        if ((new Otp)->validate($validateOtpDTO->getIdentifier(), $validateOtpDTO->getOtp())->status === true) {

            $identifier = $validateOtpDTO->getIdentifier();

            $field = $type === 'email' ? 'email' : 'phone';

          return  $this->companyUserRepository->updateUserData($userId, [
                $field => $identifier,
            ]);

            return true;
        }
        throw new \ErrorException(__("validation.invalid-otp"), 401);
    }
}
