<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Handlers;

use Modules\Project\ProjectManagement\Commands\UpdateProjectManagementCommand;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectRole;
use Modules\Project\ProjectManagement\Repositories\ProjectManagementRepository;
use Illuminate\Support\Facades\Auth;

class UpdateProjectManagementHandler
{
    public function __construct(
        private ProjectManagementRepository $repository,
    ) {
    }

    public function handle(UpdateProjectManagementCommand $updateProjectManagementCommand)
    {
        $this->repository->updateProjectManagement($updateProjectManagementCommand->getId(), $updateProjectManagementCommand->toArray());

        $this->ensureManagerIsProjectEmployee($updateProjectManagementCommand);
    }

    private function ensureManagerIsProjectEmployee(UpdateProjectManagementCommand $command): void
    {
        $managerId = $command->getManagerId();

        if (! $managerId) {
            return;
        }

        $project = ProjectManagement::withoutGlobalScopes()
            ->findOrFail($command->getId()->toString());

        $adminRole = ProjectRole::query()
            ->where('project_id', $project->id)
            ->where('is_default', true)
            ->first();

        ProjectEmployee::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'user_id' => $managerId,
            ],
            [
                'company_id' => $project->company_id,
                'project_role_id' => $adminRole?->id,
                'assigned_by_user_id' => Auth::id() ? (string) Auth::id() : $project->created_by_user_id,
                'assigned_at' => now(),
            ]
        );
    }
}
