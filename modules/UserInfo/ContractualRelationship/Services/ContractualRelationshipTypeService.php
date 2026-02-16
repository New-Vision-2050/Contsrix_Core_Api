<?php

declare(strict_types=1);

namespace Modules\UserInfo\ContractualRelationship\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\UserInfo\ContractualRelationship\Repositories\ContractualRelationshipTypeRepository;

class ContractualRelationshipTypeService
{
    public function __construct(
        private ContractualRelationshipTypeRepository $repository,
    ) {
    }

    public function getAllActiveTypes(): Collection
    {
        return $this->repository->getAllActiveTypes();
    }

    public function getAllTypes(): Collection
    {
        return $this->repository->getAllTypes();
    }
}
