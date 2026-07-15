<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatusTypeKey;

class ProjectNotificationSiteStatusTypeKeyPresenter
{
    public static function single(ProjectNotificationSiteStatusTypeKey $key): array
    {
        return [
            'id' => $key->id,
            'site_status_type_id' => $key->site_status_type_id,
            'name_ar' => $key->name_ar,
            'name_en' => $key->name_en,
            'key' => $key->key,
            'field_type' => $key->field_type,
            'options' => $key->options,
            'show_in_site_status_updates' => $key->show_in_site_status_updates,
            'sort_order' => $key->sort_order,
            'is_active' => $key->is_active,
            'created_at' => $key->created_at,
            'updated_at' => $key->updated_at,
        ];
    }

    public static function collection(Collection $keys): array
    {
        return $keys->map(static fn ($key) => self::single($key))->all();
    }
}
