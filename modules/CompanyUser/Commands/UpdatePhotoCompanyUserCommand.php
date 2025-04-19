<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdatePhotoCompanyUserCommand
{
    public function __construct(
        private UuidInterface $id,
        public string $image,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'image' => $this->image,
        ];
    }
}
