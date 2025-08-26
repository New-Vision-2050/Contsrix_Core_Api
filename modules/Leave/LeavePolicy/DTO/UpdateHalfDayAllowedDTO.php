<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\DTO;

use Ramsey\Uuid\UuidInterface;

readonly class UpdateHalfDayAllowedDTO
{
    public function __construct(
        private UuidInterface $leavePolicyId,
        private bool $isAllowHalfDay,
    ) {
    }

    public function getLeavePolicyId(): UuidInterface
    {
        return $this->leavePolicyId;
    }

    public function getIsAllowHalfDay(): bool
    {
        return $this->isAllowHalfDay;
    }
}
