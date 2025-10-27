<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateBannerCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?string $url = null,
        private ?string $type = null,
        private ?bool $isActive = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function toArray(): array
    {
        $data = [];
        
        if ($this->url !== null) {
            $data['url'] = $this->url;
        }
        
        if ($this->type !== null) {
            $data['type'] = $this->type;
        }
        
        if ($this->isActive !== null) {
            $data['is_active'] = $this->isActive;
        }
        
        return $data;
    }
}
