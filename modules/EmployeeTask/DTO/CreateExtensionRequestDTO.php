<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\DTO;

final class CreateExtensionRequestDTO
{
    public function __construct(
        public readonly string  $taskId,
        public readonly string  $requestedBy,
        public readonly float   $additionalHours,
        public readonly ?string $reason = null,
    ) {}

    public function toArray(): array
    {
        return [
            'employee_task_request_id' => $this->taskId,
            'requested_by'             => $this->requestedBy,
            'additional_hours'         => $this->additionalHours,
            'reason'                   => $this->reason,
            'status'                   => 'pending',
        ];
    }
}
