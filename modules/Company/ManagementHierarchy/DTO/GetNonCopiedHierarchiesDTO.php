<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\DTO;

class GetNonCopiedHierarchiesDTO
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 10,
    ) {
    }

    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];
    }
}
