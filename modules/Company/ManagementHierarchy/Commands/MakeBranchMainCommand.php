<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Commands;

use Ramsey\Uuid\UuidInterface;

class MakeBranchMainCommand
{
    public function __construct(
        private int $id,
        private int $branchId,
    )
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBranchAlternativeId(): int
    {
        return $this->branchId;
    }
}
