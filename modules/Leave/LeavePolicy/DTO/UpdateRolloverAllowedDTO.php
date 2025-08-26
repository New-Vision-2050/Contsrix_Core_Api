<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\DTO;

use Ramsey\Uuid\UuidInterface;

readonly class UpdateRolloverAllowedDTO
{
    public function __construct(
        private UuidInterface $leavePolicyId,
        private bool $isRolloverAllowed,
    ) {
    }

    public function getLeavePolicyId(): UuidInterface
    {
        return $this->leavePolicyId;
    }

    public function getIsRolloverAllowed(): bool
    {
        return $this->isRolloverAllowed;
    }
}
