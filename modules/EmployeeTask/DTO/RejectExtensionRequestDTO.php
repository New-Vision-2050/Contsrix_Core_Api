<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\DTO;

/**
 * Data Transfer Object for rejecting extension requests.
 * 
 * Encapsulates all data needed to process an extension rejection
 * in a clean, type-safe manner.
 */
final class RejectExtensionRequestDTO
{
    public function __construct(
        public readonly string $extensionId,
        public readonly string $adminId,
        public readonly ?string $rejectionReason = null,
    ) {}

    public function toArray(): array
    {
        return [
            'extension_id'     => $this->extensionId,
            'admin_id'         => $this->adminId,
            'rejection_reason' => $this->rejectionReason,
        ];
    }
}
