<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;

class CompletionStatusPresenter extends AbstractPresenter
{
    public function __construct(private readonly object $model)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->model->id,
            'name' => $this->model->name,
            'order_permit_department_id' => $this->model->order_permit_department_id,
            'department_name' => $this->model->department?->name,
            'statuses' => $this->model->statuses->map(fn ($status) => [
                'id' => $status->id,
                'name' => $status->name,
            ])->toArray(),
            'created_at' => $this->model->created_at?->toDateTimeString(),
            'updated_at' => $this->model->updated_at?->toDateTimeString(),
        ];
    }
}
