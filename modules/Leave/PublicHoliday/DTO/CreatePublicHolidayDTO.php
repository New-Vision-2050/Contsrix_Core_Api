<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\DTO;

use Ramsey\Uuid\UuidInterface;
use DateTime;

class CreatePublicHolidayDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $branch_id,
        public readonly DateTime $date_start,
        public readonly DateTime $date_end,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBranchId(): int
    {
        return $this->branch_id;
    }

    public function getDateStart(): DateTime
    {
        return $this->date_start;
    }

    public function getDateEnd(): DateTime
    {
        return $this->date_end;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'branch_id' => $this->branch_id,
            'date_start' => $this->date_start->format('Y-m-d'),
            'date_end' => $this->date_end->format('Y-m-d'),
        ];
    }
}
