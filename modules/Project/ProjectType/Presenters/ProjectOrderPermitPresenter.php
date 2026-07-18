<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;

class ProjectOrderPermitPresenter extends AbstractPresenter
{
    public function __construct(private readonly ProjectOrderPermit $model)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->model->id,
            'project_id' => $this->model->project_id,
            'project_management_id' => $this->model->project_management_id,
            'project_management_name' => $this->model->projectManagement?->name,
            'order_permit_id' => $this->model->order_permit_id,
            'order_permit_department_id' => $this->model->order_permit_department_id,
            'contractor_id' => $this->model->contractor_id,
            'contractor_name' => $this->model->contractor?->name,
            'name' => $this->model->name,
            'type' => $this->model->type,
            'assigned_date' => $this->model->assigned_date?->toDateString(),
            'state_id' => $this->model->state_id,
            'state_name' => $this->model->state?->name,
            'lat' => $this->model->lat,
            'long' => $this->model->long,
            'price' => $this->model->price,
            'created_at' => $this->model->created_at?->toDateTimeString(),
            'updated_at' => $this->model->updated_at?->toDateTimeString(),
        ];
    }
}
