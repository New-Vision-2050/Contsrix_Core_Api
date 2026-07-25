<?php

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\Violation;

class ViolationRepository extends BaseRepository
{
    public function __construct(Violation $model)
    {
        parent::__construct($model);
    }

    public function listOrderedByCode(): Collection
    {
        return $this->model->newQuery()->orderBy('code')->get();
    }
}
