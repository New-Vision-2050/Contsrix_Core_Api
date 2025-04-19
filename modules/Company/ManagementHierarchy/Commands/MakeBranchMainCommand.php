<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Commands;

use Ramsey\Uuid\UuidInterface;

class MakeBranchMainCommand
{
    public function __construct(
        private UuidInterface $id,
        private UuidInterface $branchId,
    )
    {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getBranchAlternativeId(): UuidInterface
    {
        return $this->branchId;
    }
}
