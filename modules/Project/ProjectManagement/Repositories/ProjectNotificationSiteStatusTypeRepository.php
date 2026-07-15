<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatusType;

/**
 * @property ProjectNotificationSiteStatusType $model
 * @method ProjectNotificationSiteStatusType findOneOrFail($id)
 * @method ProjectNotificationSiteStatusType findOneByOrFail(array $data)
 */
class ProjectNotificationSiteStatusTypeRepository extends BaseRepository
{
    public function __construct(ProjectNotificationSiteStatusType $model)
    {
        parent::__construct($model);
    }

    public function listActive(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function listWithActiveKeys(): Collection
    {
        return $this->model
            ->with(['activeKeys' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
