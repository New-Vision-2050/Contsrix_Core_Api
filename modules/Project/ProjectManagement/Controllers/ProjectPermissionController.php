<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectManagement\Services\ProjectPermissionService;

class ProjectPermissionController extends Controller
{
    public function __construct(
        private ProjectPermissionService $service
    ) {
    }

    public function index(): JsonResponse
    {
        try {
            $permissions = $this->service->getAllPermissions();

            $data = $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'submodule' => $permission->submodule,
                    'action' => $permission->action,
                    'title' => $permission->title,
                    'title_ar' => $permission->getTranslation('title', 'ar'),
                    'title_en' => $permission->getTranslation('title', 'en'),
                    'description' => $permission->description,
                    'is_active' => $permission->is_active,
                ];
            });

            return Json::items($data->toArray());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function getBySubmodule(Request $request): JsonResponse
    {
        try {
            $submodule = $request->route('submodule');

            if (!$submodule) {
                return Json::error('Submodule is required', 400);
            }

            $permissions = $this->service->getPermissionsBySubmodule($submodule);

            $data = $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'submodule' => $permission->submodule,
                    'action' => $permission->action,
                    'title' => $permission->title,
                    'title_ar' => $permission->getTranslation('title', 'ar'),
                    'title_en' => $permission->getTranslation('title', 'en'),
                    'description' => $permission->description,
                ];
            });

            return Json::items($data->toArray());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $id = $request->route('id');

            $validated = $request->validate([
                'title_ar' => 'sometimes|string',
                'title_en' => 'sometimes|string',
                'description' => 'sometimes|string|nullable',
            ]);

            $updateData = [];
            
            if (isset($validated['title_ar']) || isset($validated['title_en'])) {
                $updateData['title'] = [];
                if (isset($validated['title_ar'])) {
                    $updateData['title']['ar'] = $validated['title_ar'];
                }
                if (isset($validated['title_en'])) {
                    $updateData['title']['en'] = $validated['title_en'];
                }
            }

            if (isset($validated['description'])) {
                $updateData['description'] = $validated['description'];
            }

            $permission = $this->service->updatePermission($id, $updateData);

            return Json::item($permission);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }
}
