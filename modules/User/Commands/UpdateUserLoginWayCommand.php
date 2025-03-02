<?php

declare(strict_types=1);

namespace Modules\User\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateUserLoginWayCommand
{
    public function __construct(
        private UuidInterface $id,
        private UuidInterface $loginWayId,
    )
    {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }


    public function toArray(): array
    {
        return [
            "login_way_id" => $this->loginWayId,
        ];

    }
}
