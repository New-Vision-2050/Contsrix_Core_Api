<?php

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Project\ProjectType\Models\Violation;

class ViolationPresenter extends AbstractPresenter
{
    public function __construct(private Violation $model) {}

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->model->id,
            'code' => $this->model->code,
            'description' => $this->model->description,
            'category' => $this->model->category,
            'weight' => $this->model->default_weight,
            'actions' => $this->model->actions(),
        ];
    }
}
