<?php

declare(strict_types=1);

namespace Modules\Setting\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateLoginWayCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private array $loginOptions,
        private UuidInterface $companyId

    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }



    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'login_options' => $this->loginOptions,
            'company_id' => $this->companyId
        ];
    }
}
