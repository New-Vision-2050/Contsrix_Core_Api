<?php

declare(strict_types=1);

namespace Modules\UserInfo\ContractualRelationship\Services;

use Modules\UserInfo\ContractualRelationship\Commands\UpdateContractualRelationshipCommand;
use Modules\UserInfo\ContractualRelationship\Models\ContractualRelationship;
use Modules\UserInfo\ContractualRelationship\Repositories\ContractualRelationshipRepository;
use Ramsey\Uuid\UuidInterface;

class ContractualRelationshipCRUDService
{
    public function __construct(
        private ContractualRelationshipRepository $repository,
    ) {
    }

    public function create(UpdateContractualRelationshipCommand $command): ContractualRelationship
    {
        return $this->repository->createOrUpdateContractualRelationship($command->toArray());
    }

    public function get(UuidInterface $companyId, UuidInterface $globalId): ?ContractualRelationship
    {
        return $this->repository->getContractualRelationship($companyId, $globalId);
    }
}
