<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use Modules\Project\ProjectType\Models\ProjectType;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ProjectTypePresenter extends AbstractPresenter
{
    private ProjectType $projectType;

    public function __construct(ProjectType $projectType)
    {
        $this->projectType = $projectType;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->projectType->id,
            'name' => $this->projectType->name,
        ];
    }
}
