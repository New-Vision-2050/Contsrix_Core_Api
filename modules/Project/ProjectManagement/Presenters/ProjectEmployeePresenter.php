<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ProjectEmployeePresenter extends AbstractPresenter
{
    public function __construct(private ProjectEmployee $projectEmployee)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->projectEmployee->id,
            'project_id' => $this->projectEmployee->project_id,
            'user' => $this->projectEmployee->user ? [
                'id' => $this->projectEmployee->user->id,
                'name' => $this->projectEmployee->user->name,
                'email' => $this->projectEmployee->user->email,
            ] : null,
            'assigned_at' => $this->projectEmployee->assigned_at?->toISOString(),
            'assigned_by' => $this->projectEmployee->assignedBy ? [
                'id' => $this->projectEmployee->assignedBy->id,
                'name' => $this->projectEmployee->assignedBy->name,
            ] : null,
            "company"=>$this->projectEmployee->company ? [
                'id' => $this->projectEmployee->company->id,
                'name' => $this->projectEmployee->company->name,
            ] : null,
            'created_at' => $this->projectEmployee->created_at?->toISOString(),
        ];
    }
}
