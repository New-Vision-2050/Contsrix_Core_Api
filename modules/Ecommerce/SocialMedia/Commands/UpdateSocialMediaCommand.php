<?php

declare(strict_types=1);

namespace Modules\Ecommerce\SocialMedia\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateSocialMediaCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?string $socialIconsId = null,
        private ?string $url = null,
        private ?bool $isActive = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getSocialIconsId(): ?string
    {
        return $this->socialIconsId;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function toArray(): array
    {
        $data = [];
        
        if ($this->socialIconsId !== null) {
            $data['social_icons_id'] = $this->socialIconsId;
        }
        
        if ($this->url !== null) {
            $data['url'] = $this->url;
        }
        
        if ($this->isActive !== null) {
            $data['is_active'] = $this->isActive;
        }
        
        return $data;
    }
}
