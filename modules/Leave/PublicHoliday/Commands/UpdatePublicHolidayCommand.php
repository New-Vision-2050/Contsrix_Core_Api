<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Commands;

use Ramsey\Uuid\UuidInterface;
use DateTime;

class UpdatePublicHolidayCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private string $country_id,
        private DateTime $date_start,
        private DateTime $date_end,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCountryId(): string
    {
        return $this->country_id;
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
        return array_filter([
            'name' => $this->name,
            'country_id' => $this->country_id,
            'date_start' => $this->date_start->format('Y-m-d'),
            'date_end' => $this->date_end->format('Y-m-d'),
        ]);
    }
}
