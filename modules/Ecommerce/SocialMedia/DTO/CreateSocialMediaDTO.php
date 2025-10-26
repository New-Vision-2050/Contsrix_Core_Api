<?php

declare(strict_types=1);

namespace Modules\Ecommerce\SocialMedia\DTO;

use Ramsey\Uuid\UuidInterface;
class CreateSocialMediaDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $socialIconsId,
        public string $url,
        public bool $isActive = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'social_icons_id' => $this->socialIconsId,
            'url' => $this->url,
            'is_active' => $this->isActive,
        ];
    }
}
