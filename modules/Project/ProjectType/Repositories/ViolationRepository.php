<?php

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectType\Models\Violation;

class ViolationRepository extends BaseRepository
{
    public function __construct(Violation $model)
    {
        parent::__construct($model);
    }
}
