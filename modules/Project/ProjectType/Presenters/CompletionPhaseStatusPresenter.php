<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;

class CompletionPhaseStatusPresenter extends AbstractPresenter
{
    public function __construct(private readonly object $model)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->model->id,
            'name' => $this->model->name,
            'phase' => [
                'id' => $this->model->phase?->id,
                'name' => $this->model->phase?->name,
            ],
            'created_at' => $this->model->created_at?->toDateTimeString(),
            'updated_at' => $this->model->updated_at?->toDateTimeString(),
        ];
    }
}
