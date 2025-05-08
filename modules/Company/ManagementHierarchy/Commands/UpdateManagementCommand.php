<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateManagementCommand
{
    public function __construct(
        private int $id,
        private string $name,
        private int $branchId,
        private UuidInterface $companyId,
        private string $description,
        private int $isActive,
        private array $deputyManagerIds,
        private UuidInterface $referenceUserId,
        private UuidInterface $managerId,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBranchId(): int
    {
        return $this->branchId;
    }

    public function getCompanyId(): UuidInterface
    {
        return $this->companyId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getIsActive(): int
    {
        return $this->isActive;
    }

    public function getDeputyManagerIds(): array
    {
        return $this->deputyManagerIds;
    }

    public function getReferenceUserId(): UuidInterface
    {
        return $this->referenceUserId;
    }

    public function getManagerId(): UuidInterface
    {
        return $this->managerId;
    }
}
