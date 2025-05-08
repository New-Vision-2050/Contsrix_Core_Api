<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\JobType\Models\JobType;

class JobTypeSimplePresenter extends AbstractPresenter
{
    private JobType $model;

    public function __construct(JobType $model)
    {
        $this->model = $model;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->model->id,
            'name' => $this->model->name,
        ];
    }
}
