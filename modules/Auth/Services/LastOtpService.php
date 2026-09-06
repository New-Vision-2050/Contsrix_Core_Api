<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Ichtrojan\Otp\Models\Otp;
use Modules\Auth\Repositories\OtpRepository;

class LastOtpService
{
    public function __construct(private OtpRepository $otpRepository)
    {
    }

    /**
     * @param array<int, string|null> $identifiers
     */
    public function getForIdentifiers(array $identifiers): ?Otp
    {
        return $this->otpRepository->getLatestForIdentifiers($identifiers);
    }
}
