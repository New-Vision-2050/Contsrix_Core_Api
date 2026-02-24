<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateProjectManagementDTO
{
    public function __construct(
        public int $projectTypeId,
        public int $subProjectTypeId,
        public int $subSubProjectTypeId,
        public ?string $name = null,
        public ?string $managerId = null,
        public ?string $branchId = null,
        public ?string $projectOwnerType = null,
        public ?string $projectOwnerId = null,
        public ?string $contractId = null,
        public ?string $clientId = null,
        public ?string $projectClassificationId = null,
        public ?string $costCenterBranchId = null,
        public ?string $managementId = null,
        public ?string $currencyId = null,
        public ?float $projectValue = null,
        public int $status = 1,
    ) {
    }

    public function toArray(): array
    {
        return [
            'project_type_id' => $this->projectTypeId,
            'sub_project_type_id' => $this->subProjectTypeId,
            'sub_sub_project_type_id' => $this->subSubProjectTypeId,
            'name' => $this->name,
            'manager_id' => $this->managerId,
            'branch_id' => $this->branchId,
            'project_owner_type' => $this->projectOwnerType,
            'project_owner_id' => $this->projectOwnerId,
            'contract_id' => $this->contractId,
            'client_id' => $this->clientId,
            'project_classification_id' => $this->projectClassificationId,
            'cost_center_branch_id' => $this->costCenterBranchId,
            'management_id' => $this->managementId,
            'currency_id' => $this->currencyId,
            'project_value' => $this->projectValue,
            'company_id' => tenant('id'),
            'status' => $this->status,
        ];
    }
}
