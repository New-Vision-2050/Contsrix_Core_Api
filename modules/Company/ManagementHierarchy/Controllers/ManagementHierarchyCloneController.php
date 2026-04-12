<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Company\ManagementHierarchy\DTO\CloneDepartmentDTO;
use Modules\Company\ManagementHierarchy\Requests\CloneManagementRequest;
use Modules\Company\ManagementHierarchy\Requests\UpdateCloneManagementRequest;
use Modules\Company\ManagementHierarchy\Services\ManagementHierarchyCloneService;

class ManagementHierarchyCloneController extends Controller
{
    public function __construct(
        private ManagementHierarchyCloneService $cloneService
    )
    {
    }

    /**
     * Clone a department from one branch to another
     *
     * @return JsonResponse
     */
    public function cloneManagement(CloneManagementRequest $request)
    {
//        try {
            $clonedDepartment = $this->cloneService->cloneManagement($request->createCloneManagementDTO());



            return response()->json([
                'success' => true,
                'message' => 'Department cloned successfully',
                'data' => [
                    'department' => $clonedDepartment->load('detail'),
                ],
            ]);
//        } catch (\Exception $e) {
//            return response()->json([
//                'success' => false,
//                'message' => 'Failed to clone department: ' . $e->getMessage(),
//            ], 500);
//        }
    }


    public function updateCloneManagement(UpdateCloneManagementRequest $request)
    {
        try {
            $clonedDepartment = $this->cloneService->updateCloneManagement($request->createCloneManagementDTO());



            return response()->json([
                'success' => true,
                'message' => 'Department cloned successfully',

            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clone department: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Get all departments that have been cloned from a source department
     *
     * @param Request $request
     * @param string $departmentId
     * @return JsonResponse
     */
    public function getLinkedDepartments(Request $request, string $departmentId): JsonResponse
    {
        try {
            $linkedDepartments = $this->cloneService->getLinkedDepartments($departmentId);

            return response()->json([
                'success' => true,
                'data' => [
                    'departments' => $linkedDepartments,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get linked departments: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync changes between linked departments
     *
     * @param Request $request
     * @param string $departmentId
     * @return JsonResponse
     */
    public function syncLinkedDepartments(Request $request, string $departmentId): JsonResponse
    {
        $request->validate([
            'fields_to_sync' => 'array',
            'sync_managers' => 'boolean',
        ]);

        try {
            $fieldsToSync = $request->input('fields_to_sync', ['name']);
            $syncManagers = $request->input('sync_managers', false);

            $this->cloneService->syncLinkedDepartments($departmentId, $fieldsToSync, $syncManagers);

            return response()->json([
                'success' => true,
                'message' => 'Departments synchronized successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync departments: ' . $e->getMessage(),
            ], 500);
        }
    }

}
