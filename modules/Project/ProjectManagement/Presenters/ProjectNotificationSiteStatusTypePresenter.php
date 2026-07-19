<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatusType;

class ProjectNotificationSiteStatusTypePresenter
{
    public static function single(ProjectNotificationSiteStatusType $type): array
    {
        return [
            'id' => $type->id,
            'project_type_id' => $type->project_type_id,
            'name_ar' => $type->name_ar,
            'name_en' => $type->name_en,
            'sort_order' => $type->sort_order,
            'is_active' => $type->is_active,
            'notification_types' => $type->relationLoaded('notificationTypes')
                ? $type->notificationTypes->map(fn ($nt) => [
                    'id' => $nt->id,
                    'name_ar' => $nt->name_ar,
                    'name_en' => $nt->name_en,
                ])->all()
                : [],
            'created_at' => $type->created_at,
            'updated_at' => $type->updated_at,
        ];
    }

    public static function collection(Collection $types): array
    {
        return $types->map(static fn ($type) => self::single($type))->all();
    }

    public static function withKeys(ProjectNotificationSiteStatusType $type): array
    {
        return [
            ...self::single($type),
            'keys' => ProjectNotificationSiteStatusTypeKeyPresenter::collection($type->activeKeys),
        ];
    }

    public static function collectionWithKeys(Collection $types): array
    {
        return $types->map(static fn ($type) => self::withKeys($type))->all();
    }
}
