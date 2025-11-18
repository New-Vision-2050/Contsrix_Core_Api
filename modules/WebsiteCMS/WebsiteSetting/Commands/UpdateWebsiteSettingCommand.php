<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteSetting\Commands;

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\UuidInterface;

class UpdateWebsiteSettingCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?string $main_color = null,
        private ?string $second_color = null,
        private ?string $background_color = null,
        private ?UploadedFile $logo = null,
        private ?string $website_address = null,
        private string $name,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getMainColor(): ?string
    {
        return $this->main_color;
    }

    public function getSecondColor(): ?string
    {
        return $this->second_color;
    }

    public function getBackgroundColor(): ?string
    {
        return $this->background_color;
    }

    public function getLogo(): ?UploadedFile
    {
        return $this->logo;
    }

    public function getWebsiteAddress(): ?string
    {
        return $this->website_address;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'main_color' => $this->main_color,
            'second_color' => $this->second_color,
            'background_color' => $this->background_color,
            'website_address' => $this->website_address,
        ];
    }
}
