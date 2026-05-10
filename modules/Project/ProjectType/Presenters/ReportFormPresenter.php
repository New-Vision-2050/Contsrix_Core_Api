<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Project\ProjectType\Models\ReportForm;

class ReportFormPresenter extends AbstractPresenter
{
    public function __construct(private readonly ReportForm $model)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id'                            => $this->model->id,
            'project_type_id'               => $this->model->project_type_id,
            'project_sharing_work_order_id' => $this->model->project_sharing_work_order_id,
            'name'                          => $this->model->name,
            'question'                      => $this->model->question,
            'value'                         => $this->model->value,
            'number_of_attachments'         => $this->model->number_of_attachments,
            'notes'                         => $this->model->notes,
            'created_at'                    => $this->model->created_at?->toDateTimeString(),
            'updated_at'                    => $this->model->updated_at?->toDateTimeString(),
        ];
    }
}
