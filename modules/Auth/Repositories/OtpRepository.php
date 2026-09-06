<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Ichtrojan\Otp\Models\Otp;

/**
 * @property Otp $model
 */
class OtpRepository extends BaseRepository
{
    public function __construct(Otp $model)
    {
        parent::__construct($model);
    }

    public function getOtpDataByIdentifier($identifier): ?Otp
    {
        return $this->findOneBy([
           "identifier" =>$identifier,
        ]);
    }

    /**
     * Return the most recently generated OTP for the supplied
     * delivery identifiers. OTP records are keyed by email address or phone
     * number rather than by the application's user ID.
     *
     * @param array<int, string|null> $identifiers
     */
    public function getLatestForIdentifiers(array $identifiers): ?Otp
    {
        $identifiers = array_values(array_filter($identifiers, static fn (?string $identifier): bool => filled($identifier)));

        if ($identifiers === []) {
            return null;
        }

        return $this->model
            ->newQuery()
            ->whereIn('identifier', $identifiers)
            ->latest('created_at')
            ->first();
    }
}
