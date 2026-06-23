<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\Attendance\Services\AttendanceStatusService;
use Modules\Project\ProjectManagement\Services\ProjectEmployeeService;
use Modules\Project\ProjectManagement\Requests\AssignEmployeesRequest;
use Modules\Project\ProjectManagement\Presenters\ProjectEmployeePresenter;
use Modules\User\Presenters\EmployeePresenter;
use Modules\Project\ProjectManagement\Mail\EmployeeAssignedMail;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\User\Models\User;
use Modules\Company\CompanyCore\Models\Company;

class ProjectEmployeeController extends Controller
{
    public function __construct(
        private ProjectEmployeeService $service,
        private AttendanceStatusService $attendanceStatusService
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
                $request->project_role_id,
                $request->company_id
            );

            // Send email to each assigned employee
            $this->sendEmployeeAssignmentEmails(
                $request->project_id,
                $request->user_ids,
                $request->project_role_id,
                $request->company_id
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

            $companyId = $request->query('company_id');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date', now()->toDateString());

            $employees = $this->service->getProjectEmployees($projectId, $companyId);
            $userIds = $employees
                ->pluck('user_id')
                ->filter()
                ->unique()
                ->values();

            $attendanceStatusByUserId = $this->attendanceStatusService->buildForUsers($userIds, array_filter([
                'start_date' => $startDate,
                'end_date' => $endDate,
            ], static fn ($value) => $value !== null && $value !== ''));

            $data = $employees->map(function ($employee) use ($attendanceStatusByUserId, $startDate) {
                $presented = (new ProjectEmployeePresenter($employee))->getData();
                $userId = $employee->user_id ? (string) $employee->user_id : null;

                $presented['attendance'] = $userId && $attendanceStatusByUserId->has($userId)
                    ? $attendanceStatusByUserId->get($userId)
                    : $this->attendanceStatusService->syntheticAbsent($employee->user, $startDate);

                return $presented;
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

    /**
     * Send email notifications to assigned employees
     */
    private function sendEmployeeAssignmentEmails(
        string $projectId,
        array $userIds,
        ?string $projectRoleId,
        ?string $companyId
    ): void {
        try {
            // Get project WITHOUT tenancy scope (could be shared project)
            $project = ProjectManagement::withoutGlobalScopes()
                ->find($projectId);

            if (!$project) {
                \Log::warning("Project not found for employee assignment email", [
                    'project_id' => $projectId,
                ]);
                return;
            }

            // Get role name if provided
            $roleName = null;
            if ($projectRoleId) {
                $role = \Modules\Project\ProjectManagement\Models\ProjectRole::find($projectRoleId);
                $roleName = $role ? $role->name : null;
            }

            // Get current user as assigner
            $currentUser = auth()->user();
            $assignedByName = $currentUser ? $currentUser->name : 'مدير النظام';

            // Get company for building action URL
            $targetCompanyId = $companyId ?? (string) tenant('id');
            $company = Company::withoutGlobalScopes()->find($targetCompanyId);

            foreach ($userIds as $userId) {
                try {
                    // Get user WITHOUT tenancy scope (could be from different company)
                    $user = User::withoutGlobalScopes()->find($userId);

                    if (!$user || !$user->email) {
                        \Log::warning("User not found for employee assignment email", [
                            'user_id' => $userId,
                            'project_id' => $projectId,
                        ]);
                        continue;
                    }

                    // Build action URL
                    $actionUrl = $this->buildActionUrlForEmployee($company, $projectId);

                    // Send the email with extra error protection
                    try {
                        Mail::to($user->email)->send(
                            new EmployeeAssignedMail(
                                project: $project,
                                employeeName: $user->name,
                                assignedByName: $assignedByName,
                                roleName: $roleName,
                                actionUrl: $actionUrl
                            )
                        );

                        \Log::info("Employee assignment email sent successfully", [
                            'employee_email' => $user->email,
                            'employee_name' => $user->name,
                            'project_id' => $projectId,
                            'project_name' => $project->name,
                        ]);
                    } catch (\Exception $mailException) {
                        \Log::error("Mail sending failed for employee assignment", [
                            'user_id' => $userId,
                            'project_id' => $projectId,
                            'employee_email' => $user->email,
                            'error' => $mailException->getMessage(),
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error("Failed to send employee assignment email to individual user", [
                        'user_id' => $userId,
                        'project_id' => $projectId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error("Failed to send employee assignment emails", [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build action URL for employee assignment
     */
    private function buildActionUrlForEmployee($company, string $projectId): string
    {
        if (!$company) {
            // Fallback to configured frontend URL
            $frontendUrl = config('app.frontend_url', 'https://constrix.com');
            return "{$frontendUrl}/ar/projects/{$projectId}";
        }

        // Get the first domain for the company
        $domain = $company->domains()->first();

        if ($domain && $domain->domain) {
            return "https://{$domain->domain}/ar/projects/{$projectId}";
        }

        // Fallback to configured frontend URL
        $frontendUrl = config('app.frontend_url', 'https://constrix.com');
        return "{$frontendUrl}/ar/projects/{$projectId}";
    }
}
