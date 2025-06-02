<?php

declare(strict_types=1);

namespace Modules\JobTitle\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Illuminate\Support\Collection;
use Modules\JobTitle\Models\JobTitle;

class JobTitleSimplePresenter extends AbstractPresenter
{
    private JobTitle $model;

    public function __construct(JobTitle $model)
    {
        $this->model = $model;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->model->id,
            'name' => $this->model->name,
            'job_type_id' => $this->model->job_type_id,
            'company_id' => $this->model->company_id,
        ];
    }

}
