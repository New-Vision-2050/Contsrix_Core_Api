<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Carbon\CarbonInterface;
use Modules\Auth\Repositories\OtpRepository;

class LastOtpSentAtService
{
    public function __construct(private OtpRepository $otpRepository)
    {
    }

    /**
     * @param array<int, string|null> $identifiers
     */
    public function getForIdentifiers(array $identifiers): ?CarbonInterface
    {
        return $this->otpRepository->getLastSentAtForIdentifiers($identifiers);
    }
}
