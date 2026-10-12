<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\DTO;

class CreateLeaveRequestDTO
{
    /**
     * @param list<string>|null $attachments
     */
    public function __construct(
        public readonly string $leave_policy_id,
        public readonly string $start_date,
        public readonly string $end_date,
        public readonly string $reason,
        public readonly ?array $attachments = null,
        public readonly bool $is_emergency = false,
        public readonly ?string $notes = null,
    ) {}
}
