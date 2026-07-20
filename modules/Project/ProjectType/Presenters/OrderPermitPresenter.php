<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Project\ProjectType\Models\OrderPermit;

class OrderPermitPresenter extends AbstractPresenter
{
    public function __construct(private readonly OrderPermit $model)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id'                          => $this->model->id,
            'code'                        => $this->model->code,
            'description'                 => $this->model->description,
            'type'                        => $this->model->type,
            'uds_period'                  => $this->model->uds_period,
            'order_permit_department_id'  => $this->model->order_permit_department_id,
            'department'                  => $this->model->department ? [
                'id'   => $this->model->department->id,
                'name' => $this->model->department->name,
            ] : null,
            'order_permit_type_id'        => $this->model->order_permit_type_id,
            'order_permit_type'           => $this->model->orderPermitType ? [
                'id'   => $this->model->orderPermitType->id,
                'name' => $this->model->orderPermitType->name,
            ] : null,
            'created_at'                  => $this->model->created_at?->toDateTimeString(),
            'updated_at'                  => $this->model->updated_at?->toDateTimeString(),
        ];
    }
}
