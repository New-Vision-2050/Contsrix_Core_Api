<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectManagement\Models\Contractor;

class ContractorRepository extends BaseRepository
{
    public function __construct(Contractor $model)
    {
        parent::__construct($model);
    }
}
