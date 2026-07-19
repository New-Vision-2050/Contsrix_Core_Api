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

    public function listActive(?int $projectTypeId = null, ?string $notificationTypeId = null): Collection
    {
        return $this->model
            ->with('notificationTypes')
            ->where('is_active', true)
            ->when($projectTypeId, fn ($q) => $q->where('project_type_id', $projectTypeId))
            ->when($notificationTypeId, fn ($q) => $q->whereHas('notificationTypes', fn ($q2) => $q2->where('project_notification_types.id', $notificationTypeId)))
            ->orderBy('sort_order')
            ->get();
    }

    public function listWithActiveKeys(?int $projectTypeId = null, ?string $notificationTypeId = null): Collection
    {
        return $this->model
            ->with(['activeKeys' => fn ($q) => $q->where('is_active', true), 'notificationTypes'])
            ->where('is_active', true)
            ->when($projectTypeId, fn ($q) => $q->where('project_type_id', $projectTypeId))
            ->when($notificationTypeId, fn ($q) => $q->whereHas('notificationTypes', fn ($q2) => $q2->where('project_notification_types.id', $notificationTypeId)))
            ->orderBy('sort_order')
            ->get();
    }
}
