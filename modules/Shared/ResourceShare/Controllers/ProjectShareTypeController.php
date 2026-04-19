<?php

declare(strict_types=1);

namespace Modules\Shared\ResourceShare\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Shared\ResourceShare\Models\ProjectShareType;

class ProjectShareTypeController extends Controller
{
    /**
     * GET /api/v1/project-share-types
     * Get all types (النوع)
     */
    public function getTypes(): JsonResponse
    {
        $types = ProjectShareType::types()
            ->active()
            ->orderBy('name->ar')
            ->get(['id', 'name', 'level']);

        return Json::items($types);
    }

    /**
     * GET /api/v1/project-share-types/relations
     * Get all relations (العلاقة) - independent list
     */
    public function getRelations(): JsonResponse
    {
        $relations = ProjectShareType::relations()
            ->active()
            ->orderBy('name->ar')
            ->get(['id', 'name', 'level']);

        return Json::items($relations);
    }

    /**
     * GET /api/v1/project-share-types/roles
     * Get all roles (الدور) - independent list
     */
    public function getRoles(): JsonResponse
    {
        $roles = ProjectShareType::roles()
            ->active()
            ->orderBy('name->ar')
            ->get(['id', 'name', 'level']);

        return Json::items($roles);
    }

    /**
     * GET /api/v1/project-share-types/all
     * Get all types, relations, and roles grouped by level
     */
    public function getAll(): JsonResponse
    {
        $data = [
            'types' => ProjectShareType::types()->active()->orderBy('name->ar')->get(['id', 'name', 'level']),
            'relations' => ProjectShareType::relations()->active()->orderBy('name->ar')->get(['id', 'name', 'level']),
            'roles' => ProjectShareType::roles()->active()->orderBy('name->ar')->get(['id', 'name', 'level']),
        ];

        return Json::item($data);
    }
}
