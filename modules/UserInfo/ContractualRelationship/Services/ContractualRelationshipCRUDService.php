<?php

declare(strict_types=1);

namespace Modules\UserInfo\ContractualRelationship\Services;

use Modules\Stakeholder\Models\Stakeholder;
use Modules\UserInfo\ContractualRelationship\Commands\UpdateContractualRelationshipCommand;
use Modules\UserInfo\ContractualRelationship\Models\ContractualRelationship;
use Modules\UserInfo\ContractualRelationship\Repositories\ContractualRelationshipRepository;
use Ramsey\Uuid\UuidInterface;

class ContractualRelationshipCRUDService
{
    private const DEFAULT_STAKEHOLDER_NAME = 'شركه ابعاد الرؤيه للاستشارات الهندسية';

    public function __construct(
        private ContractualRelationshipRepository $repository,
    ) {
    }

    public function create(UpdateContractualRelationshipCommand $command): ContractualRelationship
    {
        $data = $command->toArray();

        if (empty($data['stakeholder_id'])) {
            $defaultStakeholder = Stakeholder::where('name', self::DEFAULT_STAKEHOLDER_NAME)->first();
            if ($defaultStakeholder) {
                $data['stakeholder_id'] = $defaultStakeholder->id;
            }
        }

        return $this->repository->createOrUpdateContractualRelationship($data);
    }

    public function get(UuidInterface $companyId, UuidInterface $globalId): ?ContractualRelationship
    {
        return $this->repository->getContractualRelationship($companyId, $globalId);
    }
}
