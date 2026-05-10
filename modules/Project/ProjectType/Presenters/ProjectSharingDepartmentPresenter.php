<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Project\ProjectType\Models\ProjectSharingDepartment;

class ProjectSharingDepartmentPresenter extends AbstractPresenter
{
    public function __construct(private readonly ProjectSharingDepartment $model)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id'              => $this->model->id,
            'project_type_id' => $this->model->project_type_id,
            'code'            => $this->model->code,
            'description'     => $this->model->description,
            'created_at'      => $this->model->created_at?->toDateTimeString(),
            'updated_at'      => $this->model->updated_at?->toDateTimeString(),
        ];
    }
}
