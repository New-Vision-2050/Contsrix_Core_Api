<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\OrderPermit;

/**
 * @property OrderPermit $model
 * @method OrderPermit findOneOrFail($id)
 */
class OrderPermitRepository extends BaseRepository
{
    public function __construct(OrderPermit $model)
    {
        parent::__construct($model);
    }

    public function listByProjectTypeId(): Collection
    {
        return $this->model->get();

    }
}
