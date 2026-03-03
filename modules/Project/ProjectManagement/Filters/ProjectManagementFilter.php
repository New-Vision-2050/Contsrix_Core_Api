<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class ProjectManagementFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->where('name', 'like', '%' . $name . '%');
    }

    public function projectTypeId($projectTypeId)
    {
        return $this->where('project_type_id', $projectTypeId);
    }

    public function subProjectTypeId($subProjectTypeId)
    {
        return $this->where('sub_project_type_id', $subProjectTypeId);
    }

    public function subSubProjectTypeId($subSubProjectTypeId)
    {
        return $this->where('sub_sub_project_type_id', $subSubProjectTypeId);
    }

    public function managerId($managerId)
    {
        return $this->where('manager_id', $managerId);
    }

    public function branchId($branchId)
    {
        return $this->where('branch_id', $branchId);
    }

    public function projectOwnerType($projectOwnerType)
    {
        return $this->where('project_owner_type', $projectOwnerType);
    }

    public function projectOwnerId($projectOwnerId)
    {
        return $this->where('project_owner_id', $projectOwnerId);
    }

    public function contractId($contractId)
    {
        return $this->where('contract_id', $contractId);
    }

    public function clientId($clientId)
    {
        return $this->where('client_id', $clientId);
    }

    public function managementId($managementId)
    {
        return $this->where('management_id', $managementId);
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }
}
