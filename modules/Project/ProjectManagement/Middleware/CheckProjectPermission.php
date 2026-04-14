<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use BasePackage\Shared\Presenters\Json;

class CheckProjectPermission
{
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        $projectId = $request->route('project_id') ?? $request->input('project_id');
        $userId = auth()->id();

        if (!$projectId || !$userId) {
            return response()->json(Json::error('Unauthorized', 403)->getData(), 403);
        }

        $projectEmployee = ProjectEmployee::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->with('projectRole.permissions')
            ->first();

        if (!$projectEmployee || !$projectEmployee->projectRole) {
            return response()->json(
                Json::error('You are not assigned to this project', 403)->getData(),
                403
            );
        }

        $hasPermission = $projectEmployee->projectRole->permissions
            ->pluck('name')
            ->contains($permission);

        if (!$hasPermission) {
            return response()->json(
                Json::error("You don't have permission: {$permission}", 403)->getData(),
                403
            );
        }

        return $next($request);
    }
}
