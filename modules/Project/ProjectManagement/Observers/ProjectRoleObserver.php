<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Observers;

use Modules\Project\ProjectManagement\Models\ProjectRole;
use Illuminate\Support\Facades\Log;

class ProjectRoleObserver
{
    /**
     * Handle the ProjectRole "updating" event.
     * Prevent updates to default roles (Project Admin).
     */
    public function updating(ProjectRole $role): bool
    {
        if ($role->is_default) {
            Log::warning('Attempt to update default Project Admin role blocked', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'project_id' => $role->project_id,
            ]);

            throw new \Exception(
                "Default Project Admin role '{$role->name}' cannot be updated. " .
                "This role is system-managed and protected from modifications."
            );
        }

        return true;
    }

    /**
     * Handle the ProjectRole "deleting" event.
     * Prevent deletion of default roles (Project Admin).
     */
    public function deleting(ProjectRole $role): bool
    {
        if ($role->is_default) {
            Log::warning('Attempt to delete default Project Admin role blocked', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'project_id' => $role->project_id,
            ]);

            throw new \Exception(
                "Default Project Admin role '{$role->name}' cannot be deleted. " .
                "This role is system-managed and protected from deletion."
            );
        }

        return true;
    }
}
