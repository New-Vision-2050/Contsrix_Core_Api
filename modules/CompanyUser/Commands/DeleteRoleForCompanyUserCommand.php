<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Commands;

use Ramsey\Uuid\UuidInterface;

class DeleteRoleForCompanyUserCommand
{
    public function __construct(
        private UuidInterface $id,
        private UuidInterface $company_id,
        private int $role,

    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }
    public function getCompanyId(): UuidInterface
    {
        return $this->company_id;
    }

    public function getRole(): int
    {
        return $this->role;
    }




}
