<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\DTO;

use Ramsey\Uuid\UuidInterface;

class UpsertSettingPageDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $type,
        public ?string $titleHeader = null,
        public ?string $descriptionHeader = null,
        public ?string $titleFooter = null,
        public ?string $descriptionFooter = null,
        public bool $isActive = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'type' => $this->type,
            'title_header' => $this->titleHeader,
            'description_header' => $this->descriptionHeader,
            'title_footer' => $this->titleFooter,
            'description_footer' => $this->descriptionFooter,
            'is_active' => $this->isActive,
        ];
    }
}
