<?php

declare(strict_types=1);

namespace Modules\UserInfo\ContractualRelationship\Commands;

class UpdateContractualRelationshipCommand
{
    public function __construct(
        public string $company_id,
        public string $global_id,
        public string $contractual_relationship_type_id,
        public ?string $employment_name,
        public ?string $registration_number,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,
            'contractual_relationship_type_id' => $this->contractual_relationship_type_id,
            'employment_name' => $this->employment_name,
            'registration_number' => $this->registration_number,
        ]);
    }
}
