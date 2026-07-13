<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectManagement\Models\ProjectContractor;

class ProjectContractorRepository extends BaseRepository
{
    public function __construct(ProjectContractor $model)
    {
        parent::__construct($model);
    }
}
