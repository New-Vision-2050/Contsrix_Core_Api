<?php

declare(strict_types=1);

namespace Modules\UserInfo\ContractualRelationship\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\UserInfo\ContractualRelationship\Models\ContractualRelationship;
use Ramsey\Uuid\UuidInterface;

class ContractualRelationshipRepository extends BaseRepository
{
    public function __construct(ContractualRelationship $model)
    {
        parent::__construct($model);
    }

    public function getContractualRelationship(UuidInterface $companyId, UuidInterface $globalId): ?ContractualRelationship
    {
        return $this->model->where([
            'global_id' => $globalId,
            'company_id' => $companyId,
        ])->with('contractualRelationshipType')->first();
    }

    public function createOrUpdateContractualRelationship(array $data): ContractualRelationship
    {
        $contractualRelationship = $this->model->where([
            'global_id' => $data['global_id'],
            'company_id' => $data['company_id'],
        ])->first();

        if ($contractualRelationship) {
            $contractualRelationship->update($data);
            return $contractualRelationship->load('contractualRelationshipType');
        }

        return $this->model->create($data);
    }
}
