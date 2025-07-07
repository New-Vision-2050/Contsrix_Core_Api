<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class BulkConstraintIdsDTO
{
    /**
     * @param array<string> $constraintIds Array of constraint UUIDs
     */
    public function __construct(
        public array $constraintIds
    ) {
    }

    /**
     * Get the constraint IDs.
     *
     * @return array<string>
     */
    public function getConstraintIds(): array
    {
        return $this->constraintIds;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'constraint_ids' => $this->constraintIds,
        ];
    }
}
