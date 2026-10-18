<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicyCategory\DTO;

class CreateLeavePolicyCategoryDTO
{
    public function __construct(
        public readonly string $name,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'company_id' => tenant('id'),
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }
}
