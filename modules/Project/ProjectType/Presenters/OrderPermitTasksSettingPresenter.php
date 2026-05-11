<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Project\ProjectType\Models\OrderPermitTasksSetting;

class OrderPermitTasksSettingPresenter extends AbstractPresenter
{
    public function __construct(private readonly OrderPermitTasksSetting $model)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id'                      => $this->model->id,
            'project_type_id'         => $this->model->project_type_id,
            'order_permit' => $this->model->orderPermit ? [
                'id'          => $this->model->orderPermit->id,
                'code'        => $this->model->orderPermit->code,
                'description' => $this->model->orderPermit->description,
                'type'        => $this->model->orderPermit->type,
            ] : null,
            'order_permit_task' => $this->model->task ? [
                'id'   => $this->model->task->id,
                'code' => $this->model->task->code,
                'name' => $this->model->task->name,
            ] : null,
            'created_at'              => $this->model->created_at?->toDateTimeString(),
            'updated_at'              => $this->model->updated_at?->toDateTimeString(),
        ];
    }
}
