<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\DTO;

/**
 * Data Transfer Object for approving extension requests.
 * 
 * Encapsulates all data needed to process an extension approval
 * in a clean, type-safe manner.
 */
final class ApproveExtensionRequestDTO
{
    public function __construct(
        public readonly string $extensionId,
        public readonly string $adminId,
        public readonly ?string $approvalNotes = null,
    ) {}

    public function toArray(): array
    {
        return [
            'extension_id'   => $this->extensionId,
            'admin_id'       => $this->adminId,
            'approval_notes' => $this->approvalNotes,
        ];
    }
}
