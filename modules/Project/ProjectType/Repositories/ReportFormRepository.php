<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ReportForm;

/**
 * @property ReportForm $model
 * @method ReportForm findOneOrFail($id)
 */
class ReportFormRepository extends BaseRepository
{
    public function __construct(ReportForm $model)
    {
        parent::__construct($model);
    }

    public function listByProjectTypeId(int $projectTypeId): Collection
    {
        return $this->model->where('project_type_id', $projectTypeId)->orderBy('id')->get();
    }

    public function listByWorkOrderId(int $workOrderId): Collection
    {
        return $this->model->where('project_sharing_work_order_id', $workOrderId)->orderBy('id')->get();
    }
}
