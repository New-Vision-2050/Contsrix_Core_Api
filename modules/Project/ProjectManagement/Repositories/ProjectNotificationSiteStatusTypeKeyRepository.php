<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatusTypeKey;

/**
 * @property ProjectNotificationSiteStatusTypeKey $model
 * @method ProjectNotificationSiteStatusTypeKey findOneOrFail($id)
 * @method ProjectNotificationSiteStatusTypeKey findOneByOrFail(array $data)
 */
class ProjectNotificationSiteStatusTypeKeyRepository extends BaseRepository
{
    public function __construct(ProjectNotificationSiteStatusTypeKey $model)
    {
        parent::__construct($model);
    }

    public function findBySiteStatusTypeId(string $siteStatusTypeId): Collection
    {
        return $this->model
            ->where('site_status_type_id', $siteStatusTypeId)
            ->orderBy('sort_order')
            ->get();
    }

    public function findActiveBySiteStatusTypeId(string $siteStatusTypeId): Collection
    {
        return $this->model
            ->where('site_status_type_id', $siteStatusTypeId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function findVisibleInSiteStatusUpdates(string $siteStatusTypeId): Collection
    {
        return $this->model
            ->where('site_status_type_id', $siteStatusTypeId)
            ->where('is_active', true)
            ->where('show_in_site_status_updates', true)
            ->orderBy('sort_order')
            ->get();
    }
}
