<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectManagement\Services\ProjectEmployeeService;
use Modules\Project\ProjectManagement\Requests\AssignEmployeesRequest;
use Modules\Project\ProjectManagement\Presenters\ProjectEmployeePresenter;
use Modules\User\Presenters\EmployeePresenter;

class ProjectEmployeeController extends Controller
{
    public function __construct(
        private ProjectEmployeeService $service
    ) {
    }

    /**
     * Assign employees to a project
     */
    public function assignEmployees(AssignEmployeesRequest $request): JsonResponse
    {
        try {
            $employees = $this->service->appendEmployeesToProject(
                $request->project_id,
                $request->user_ids,
                $request->project_role_id
            );

            $data = $employees->map(function ($employee) {
                return (new ProjectEmployeePresenter($employee))->getData();
            });

            return Json::items($data->toArray());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Get all employees assigned to a project
     */
    public function getProjectEmployees(Request $request): JsonResponse
    {
        try {
            $projectId = $request->route('project_id');

            if (!$projectId) {
                return Json::error('Project ID is required', 400);
            }

            $employees = $this->service->getProjectEmployees($projectId);

            $data = $employees->map(function ($employee) {
                return (new ProjectEmployeePresenter($employee))->getData();
            });

            return Json::items($data->toArray());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    /**
     * Remove an employee from a project
     */
    public function removeEmployee(Request $request): JsonResponse
    {
        try {
            $projectEmployeeId = $request->route('id');

            if (!$projectEmployeeId) {
                return Json::error('Project Employee ID is required', 400);
            }

            $result = $this->service->removeEmployeeFromProject($projectEmployeeId);

            if ($result) {
                return Json::deleted();
            }

            return Json::error('Failed to remove employee', 400);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Get employees not assigned to a project (for dropdown)
     */
    public function getEmployeesNotInProject(Request $request): JsonResponse
    {
        try {
            $projectId = $request->route('project_id');

            if (!$projectId) {
                return Json::error('Project ID is required', 400);
            }

            $companyId = $request->query('company_id');

            $employees = $this->service->getEmployeesNotInProject($projectId, $companyId);

            $data = EmployeePresenter::collection($employees, \Modules\CompanyUser\Enum\CompanyUserRole::EMPLOYEE->value);

            return Json::items($data);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }

    /**
     * Assign or update project role for an employee
     */
    public function assignRole(Request $request): JsonResponse
    {
        try {
            $projectEmployeeId = $request->route('id');

            $validated = $request->validate([
                'project_role_id' => 'required|string|exists:project_roles,id',
            ]);

            $employee = $this->service->assignRoleToEmployee(
                $projectEmployeeId,
                $validated['project_role_id']
            );

            $data = (new ProjectEmployeePresenter($employee))->getData();

            return Json::item($data);
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 400);
        }
    }
}
