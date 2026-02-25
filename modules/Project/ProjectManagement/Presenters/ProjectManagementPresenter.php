<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use Modules\Project\ProjectManagement\Models\ProjectManagement;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ProjectManagementPresenter extends AbstractPresenter
{
    private ProjectManagement $projectManagement;

    public function __construct(ProjectManagement $projectManagement)
    {
        $this->projectManagement = $projectManagement;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->projectManagement->id,
            'name' => $this->projectManagement->name,
            'project_type_id' => $this->projectManagement->project_type_id,
            'sub_project_type_id' => $this->projectManagement->sub_project_type_id,
            'sub_sub_project_type_id' => $this->projectManagement->sub_sub_project_type_id,
            'manager_id' => $this->projectManagement->manager_id,
            'branch_id' => $this->projectManagement->branch_id,
            'project_owner_type' => $this->projectManagement->project_owner_type,
            'project_owner_id' => $this->projectManagement->project_owner_id,
            'contract_id' => $this->projectManagement->contract_id,
            'client_id' => $this->projectManagement->client_id,
            'project_classification_id' => $this->projectManagement->project_classification_id,
            'cost_center_branch_id' => $this->projectManagement->cost_center_branch_id,
            'management_id' => $this->projectManagement->management_id,
            'currency_id' => $this->projectManagement->currency_id,
            'project_value' => $this->projectManagement->project_value,
            'status' => $this->projectManagement->status,
            'company_id' => $this->projectManagement->company_id,
            'created_at' => $this->projectManagement->created_at?->toDateTimeString(),
            'updated_at' => $this->projectManagement->updated_at?->toDateTimeString(),
        ];

        if (!$isListing) {
            $data['project_type'] = $this->projectManagement->projectType ? [
                'id' => $this->projectManagement->projectType->id,
                'name' => $this->projectManagement->projectType->name,
            ] : null;

            $data['sub_project_type'] = $this->projectManagement->subProjectType ? [
                'id' => $this->projectManagement->subProjectType->id,
                'name' => $this->projectManagement->subProjectType->name,
            ] : null;

            $data['sub_sub_project_type'] = $this->projectManagement->subSubProjectType ? [
                'id' => $this->projectManagement->subSubProjectType->id,
                'name' => $this->projectManagement->subSubProjectType->name,
            ] : null;

            $data['manager'] = $this->projectManagement->manager ? [
                'id' => $this->projectManagement->manager->id,
                'name' => $this->projectManagement->manager->name,
                'email' => $this->projectManagement->manager->email,
            ] : null;

            $data['branch'] = $this->projectManagement->branch ? [
                'id' => $this->projectManagement->branch->id,
                'name' => $this->projectManagement->branch->name,
            ] : null;

            $data['project_owner'] = $this->projectManagement->projectOwner ? [
                'id' => $this->projectManagement->projectOwner->id,
                'name' => $this->projectManagement->projectOwner->name ?? null,
                'type' => $this->projectManagement->getProjectOwnerTypeAlias(),
            ] : null;

            $data['project_classification'] = $this->projectManagement->project_classification_id ? [
                'id' => $this->projectManagement->project_classification_id,
            ] : null;

            $data['company'] = $this->projectManagement->company ? [
                'id' => $this->projectManagement->company->id,
                'name' => $this->projectManagement->company->name,
            ] : null;

            $data['client'] = $this->projectManagement->client ? [
                'id' => $this->projectManagement->client->id,
                'name' => $this->projectManagement->client->name,
            ] : null;

            $data['cost_center_branch'] = $this->projectManagement->costCenterBranch ? [
                'id' => $this->projectManagement->costCenterBranch->id,
                'name' => $this->projectManagement->costCenterBranch->name,
            ] : null;

            $data['management'] = $this->projectManagement->management ? [
                'id' => $this->projectManagement->management->id,
                'name' => $this->projectManagement->management->name,
            ] : null;

            $data['currency'] = $this->projectManagement->currency ? [
                'id' => $this->projectManagement->currency->id,
                'name' => $this->projectManagement->currency->name,
                'code' => $this->projectManagement->currency->code ?? null,
            ] : null;
        } else {
            $data['project_type_name'] = $this->projectManagement->projectType?->name;
            $data['sub_project_type_name'] = $this->projectManagement->subProjectType?->name;
            $data['sub_sub_project_type_name'] = $this->projectManagement->subSubProjectType?->name;
            $data['manager_name'] = $this->projectManagement->manager?->name;
            $data['branch_name'] = $this->projectManagement->branch?->name;
            $data['client_name'] = $this->projectManagement->client?->name;
            $data['project_owner_name'] = $this->projectManagement->projectOwner?->name;
            $data['currency_name'] = $this->projectManagement->currency?->name;
            $data['currency_code'] = $this->projectManagement->currency?->code;
            $data['cost_center_branch_name'] = $this->projectManagement->costCenterBranch?->name;
            $data['management_name'] = $this->projectManagement->management?->name;
        }

        return $data;
    }
}
