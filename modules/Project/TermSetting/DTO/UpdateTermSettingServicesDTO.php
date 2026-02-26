<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\DTO;

class UpdateTermSettingServicesDTO
{
    public function __construct(
        public int $id,
        public array $termServiceIds = [],
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTermServiceIds(): array
    {
        return $this->termServiceIds;
    }

    public function __toString(): string
    {
        return json_encode([
            'id' => $this->id,
            'term_service_ids' => $this->termServiceIds,
        ]);
    }
}
