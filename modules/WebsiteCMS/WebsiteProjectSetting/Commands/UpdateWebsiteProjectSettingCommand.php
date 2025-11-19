<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProjectSetting\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateWebsiteProjectSettingCommand
{
    public function __construct(
        private UuidInterface $id,
        private array $name,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): array
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
