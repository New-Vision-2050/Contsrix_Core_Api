<?php

declare(strict_types=1);

namespace Modules\UserInfo\ContractualRelationship\Presenters;

use Modules\UserInfo\ContractualRelationship\Models\ContractualRelationship;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ContractualRelationshipPresenter extends AbstractPresenter
{
    private ContractualRelationship $contractualRelationship;

    public function __construct(ContractualRelationship $contractualRelationship)
    {
        $this->contractualRelationship = $contractualRelationship;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->contractualRelationship->id,
            'contractual_relationship_type_id' => $this->contractualRelationship->contractual_relationship_type_id,
            'contractual_relationship_type' => $this->contractualRelationship->contractualRelationshipType ? [
                'id' => $this->contractualRelationship->contractualRelationshipType->id,
                'name' => $this->contractualRelationship->contractualRelationshipType->name,
            ] : null,
            'employment_name' => $this->contractualRelationship->employment_name,
            'registration_number' => $this->contractualRelationship->registration_number,
            'stakeholder_id' => $this->contractualRelationship->stakeholder_id,
            'stakeholder' => $this->contractualRelationship->stakeholder ? [
                'id' => $this->contractualRelationship->stakeholder->id,
                'name' => $this->contractualRelationship->stakeholder->name,
            ] : null,
        ];
    }
}
