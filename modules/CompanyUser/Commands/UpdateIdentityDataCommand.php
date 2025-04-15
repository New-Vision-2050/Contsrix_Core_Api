<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateIdentityDataCommand
{
    public function __construct(
        public ? string $passport,
        public ? string $identity,
        public ? string $border_number,
        public ? string $entry_number,

        public ? string $passport_start_date,
        public ? string $identity_start_date,
        public ? string $border_number_start_date,
        public ? string $entry_number_start_date,

        public ? string $passport_end_date,
        public ? string $identity_end_date,
        public ? string $border_number_end_date,
        public ? string $entry_number_end_date,

        public ? string $work_permit_start_date,
        public ? string $work_permit_end_date,
        public ? string $work_permit
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'passport' => $this->passport,
            'identity' => $this->identity,
            'border_number' => $this->border_number,
            'entry_number' => $this->entry_number,
            'passport_start_date' => $this->passport_start_date,
            'identity_start_date' => $this->identity_start_date,
            'border_number_start_date' => $this->border_number_start_date,
            'entry_number_start_date' => $this->entry_number_start_date,
            'passport_end_date' => $this->passport_end_date,
            'identity_end_date' => $this->identity_end_date,
            'border_number_end_date' => $this->border_number_end_date,
            'entry_number_end_date' => $this->entry_number_end_date,
            'work_permit_start_date' => $this->work_permit_start_date,
            'work_permit_end_date' => $this->work_permit_end_date,
            'work_permit' => $this->work_permit,
        ], fn($value) => !is_null($value));
    }
}
