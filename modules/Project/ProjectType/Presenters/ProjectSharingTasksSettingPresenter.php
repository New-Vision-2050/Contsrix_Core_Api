<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Project\ProjectType\Models\ProjectSharingTasksSetting;

class ProjectSharingTasksSettingPresenter extends AbstractPresenter
{
    public function __construct(private readonly ProjectSharingTasksSetting $model)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id'                      => $this->model->id,
            'project_type_id'         => $this->model->project_type_id,
            'project_sharing_work_order' => $this->model->workOrder ? [
                'id'          => $this->model->workOrder->id,
                'code'        => $this->model->workOrder->code,
                'description' => $this->model->workOrder->description,
                'type'        => $this->model->workOrder->type,
            ] : null,
            'project_sharing_task'    => $this->model->task ? [
                'id'   => $this->model->task->id,
                'code' => $this->model->task->code,
                'name' => $this->model->task->name,
            ] : null,
            'created_at'              => $this->model->created_at?->toDateTimeString(),
            'updated_at'              => $this->model->updated_at?->toDateTimeString(),
        ];
    }
}
