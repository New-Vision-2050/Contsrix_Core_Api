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
}
