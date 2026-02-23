<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateProjectManagementCommand
{
    public function __construct(
        private UuidInterface $id,
        private int $projectTypeId,
        private int $subProjectTypeId,
        private int $subSubProjectTypeId,
        private ?string $name = null,
        private ?string $managerId = null,
        private ?int $branchId = null,
        private ?string $projectOwnerType = null,
        private ?string $projectOwnerId = null,
        private ?string $contractId = null,
        private ?string $clientId = null,
        private ?string $projectClassificationId = null,
        private ?string $costCenterBranchId = null,
        private ?string $managementId = null,
        private ?string $currencyId = null,
        private ?float $projectValue = null,
        private ?int $status = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getProjectTypeId(): int
    {
        return $this->projectTypeId;
    }

    public function getSubProjectTypeId(): int
    {
        return $this->subProjectTypeId;
    }

    public function getSubSubProjectTypeId(): int
    {
        return $this->subSubProjectTypeId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getManagerId(): ?string
    {
        return $this->managerId;
    }

    public function getBranchId(): ?string
    {
        return $this->branchId;
    }

    public function getProjectOwnerType(): ?string
    {
        return $this->projectOwnerType;
    }

    public function getProjectOwnerId(): ?string
    {
        return $this->projectOwnerId;
    }

    public function getContractId(): ?string
    {
        return $this->contractId;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function getProjectClassificationId(): ?string
    {
        return $this->projectClassificationId;
    }

    public function getCostCenterBranchId(): ?string
    {
        return $this->costCenterBranchId;
    }

    public function getManagementId(): ?string
    {
        return $this->managementId;
    }

    public function getCurrencyId(): ?string
    {
        return $this->currencyId;
    }

    public function getProjectValue(): ?float
    {
        return $this->projectValue;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return $this->status == null? [
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

        ]: [
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
            'status' => $this->status,

        ];
    }
}
