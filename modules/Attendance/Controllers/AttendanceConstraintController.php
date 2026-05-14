<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Models\AttendanceConstraintViolation;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Repositories\AttendanceConstraintRepository;
use Modules\Attendance\Repositories\AttendanceConstraintViolationRepository;
use Modules\Attendance\Requests\CreateAttendanceConstraintRequest;
use Modules\Attendance\Requests\UpdateAttendanceConstraintRequest;
use Modules\Attendance\Requests\ResolveViolationRequest;
use Modules\Attendance\Requests\DismissViolationRequest;
use Modules\Attendance\Requests\FilterConstraintsRequest;
use Modules\Attendance\Requests\GetViolationsRequest;
use Modules\Attendance\Requests\GetStatisticsRequest;
use Modules\Attendance\Requests\ValidateAttendanceRequest;
use Modules\Attendance\Requests\BulkConstraintRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\AttendanceConstraintLocation;
use Modules\Attendance\Presenters\ConstraintListPresenter;
use Modules\Attendance\Presenters\ConstraintPresenter;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Ramsey\Uuid\Uuid;

class AttendanceConstraintController extends Controller
{
    protected AttendanceConstraintService $constraintService;
    protected AttendanceConstraintRepository $constraintRepository;
    protected AttendanceConstraintViolationRepository $violationRepository;

    public function __construct(
        AttendanceConstraintService $constraintService,
        AttendanceConstraintRepository $constraintRepository,
        AttendanceConstraintViolationRepository $violationRepository,
    ) {
        $this->constraintService = $constraintService;
        $this->constraintRepository = $constraintRepository;
        $this->violationRepository = $violationRepository;
    }

    /**
     * Display a listing of constraints with filtering and pagination.
     */
    public function index(FilterConstraintsRequest $request)//: JsonResponse
    {
        $filterDTO = $request->createFilterConstraintDTO(Auth::user()->company_id);
        $result = $this->constraintRepository->getConstraintList(
            $filterDTO->toArray(),
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 10)
        );


       $presentedData = collect($result['data'])->map(function ($constraint) {
            return (new ConstraintPresenter($constraint))->present();
        });

        // 2. Pass the formatted collection to the JSON helper.
        // The ->values()->all() ensures it's a clean, flat array.
        return Json::items(
            mainItems:          $presentedData->values()->all(),
            paginationSettings: $result['pagination'],
            message:            'Constraints retrieved successfully'
        );
    }


    public function list(FilterConstraintsRequest $request)//: JsonResponse
    {
        $filterDTO = $request->createFilterConstraintDTO(Auth::user()->company_id);
        $result = $this->constraintRepository->getConstraintList(
            $filterDTO->toArray(),
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 10)
        );


       $presentedData = collect($result['data'])->map(function ($constraint) {
            return (new ConstraintListPresenter($constraint))->present();
        });

        // 2. Pass the formatted collection to the JSON helper.
        // The ->values()->all() ensures it's a clean, flat array.
        return Json::items(
            mainItems:          $presentedData->values()->all(),
            paginationSettings: $result['pagination'],
            message:            'Constraints retrieved successfully'
        );
        return Json::items($result['data'], message: 'Constraints retrieved successfully');
    }


    /**
     * Store a newly created constraint.
     */
    public function store(CreateAttendanceConstraintRequest $request): JsonResponse
    {
        $constraintDTO = $request->createConstraintDTO(
            Uuid::fromString(Auth::user()->company_id),
            Auth::id()
        );

        $constraint = $this->constraintRepository->createConstraint($constraintDTO->toArray());
        $constraint->load(['creator']);

        return Json::item($constraint, message: 'Constraint created successfully');
    }

    /**
     * Display the specified attendance constraint.
     */
    public function show(string $id): JsonResponse
    {
        $constraint = $this->constraintRepository->getConstraint(Uuid::fromString($id));
        $constraint->load(['users', 'creator', 'updater']);
        $constraintPresenter =(new ConstraintPresenter($constraint))->getData();
        return Json::item($constraintPresenter, message: 'Constraint retrieved successfully');
    }

    /**
     * Update the specified constraint.
     */
    public function update(UpdateAttendanceConstraintRequest $request, string $id): JsonResponse
    {
        $updateDTO = $request->createUpdateConstraintDTO(Auth::id());

        $constraint = $this->constraintRepository->updateConstraint(
            Uuid::fromString($id),
            $updateDTO->toArray()
        );
        $constraint->load(['users', 'creator', 'updater']);

        return Json::item($constraint, message: 'Constraint updated successfully');
    }

    /**
     * Remove the specified attendance constraint.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->constraintRepository->deleteConstraint(Uuid::fromString($id));

        return Json::success('Attendance constraint deleted successfully');
    }

    /**
     * Get constraint types and their available constraint names.
     */
    public function getConstraintTypes(): JsonResponse
    {
        $types = AttendanceConstraint::getConstraintTypes();

        return Json::item($types);
    }

    /**
     * Get violations with filtering and pagination.
     */
    public function getViolations(GetViolationsRequest $request): JsonResponse
    {
        $filterDTO = $request->createFilterViolationDTO(Auth::user()->company_id);

        $result = $this->violationRepository->getViolationList(
            $filterDTO->toArray(),
            $filterDTO->getPage(),
            $filterDTO->getPerPage()
        );

        $meta = [
            'violation_types' => AttendanceConstraintViolation::getViolationTypes(),
            'severity_levels' => AttendanceConstraintViolation::getSeverityLevels(),
            'status_options' => AttendanceConstraintViolation::getStatusOptions()
        ];

        if ($result['pagination']) {
            return Json::items(
                                    $result['data'],
                extraItems:         $meta,
                paginationSettings: $result['pagination'],
                message:            'Violations retrieved successfully'
            );
        }

        return Json::items($result['data'], extraItems: $meta, message: 'Violations retrieved successfully');
    }

    /**
     * Resolve a constraint violation.
     */
    public function resolveViolation(ResolveViolationRequest $request, string $violationId): JsonResponse
    {
        $resolveDTO = $request->createResolveViolationDTO($violationId, Auth::id());

        $violation = $this->violationRepository->resolveViolation(
            Uuid::fromString($resolveDTO->getViolationId()),
            $resolveDTO->getResolvedBy(),
            $resolveDTO->getResolutionNotes()
        );

        return Json::item($violation, message: 'Violation resolved successfully');
    }

    /**
     * Dismiss a constraint violation.
     */
    public function dismissViolation(DismissViolationRequest $request, string $violationId): JsonResponse
    {
        $dismissDTO = $request->createDismissViolationDTO($violationId, Auth::id());

        $violation = $this->violationRepository->dismissViolation(
            Uuid::fromString($dismissDTO->getViolationId()),
            $dismissDTO->getDismissedBy(),
            $dismissDTO->getDismissalReason()
        );

        return Json::item($violation, message: 'Violation dismissed successfully');
    }

    /**
     * Get constraint validation for a specific user.
     */
    public function validateUserConstraints(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'ip_address' => 'nullable|ip',
            'device_info' => 'nullable|string'
        ]);

        $user = \Modules\User\Models\User::findOrFail($request->user_id);
        $constraints = $this->constraintService->getApplicableConstraints($user);

        // Create a mock attendance record for validation
        $mockAttendance = new \Modules\Attendance\Models\Attendance([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'clock_in_time' => now(),
        ]);

        $violations = $this->constraintService->validateAttendance($mockAttendance, $request->all());

        return Json::item([
            'applicable_constraints' => $constraints,
            'violations' => $violations,
            'can_clock_in' => empty($violations)
        ], message: 'Validation completed');
    }

    /**
     * Bulk update constraint status.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'constraint_ids' => 'required|array',
            'constraint_ids.*' => 'uuid|exists:attendance_constraints,id',
            'is_active' => 'required|boolean'
        ]);

        $updated = AttendanceConstraint::whereIn('id', $request->constraint_ids)
            ->where('company_id', Auth::user()->company_id)
            ->update([
                'is_active' => $request->is_active,
                'updated_by' => Auth::id()
            ]);

        if ($updated > 0) {
            $this->constraintService->bumpApplicableConstraintsCacheForCompany((string) Auth::user()->company_id);
        }

        return Json::success("Successfully updated {$updated} constraints");
    }

    /**
     * Get constraint statistics.
     */
    public function getStatistics(): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        $stats = [
            'total_constraints' => AttendanceConstraint::where('company_id', $companyId)->count(),
            'active_constraints' => AttendanceConstraint::where('company_id', $companyId)->active()->count(),
            'constraints_by_type' => AttendanceConstraint::where('company_id', $companyId)
                ->selectRaw('constraint_type, COUNT(*) as count')
                ->groupBy('constraint_type')
                ->pluck('count', 'constraint_type'),
            'total_violations' => AttendanceConstraintViolation::where('company_id', $companyId)->count(),
            'pending_violations' => AttendanceConstraintViolation::where('company_id', $companyId)->pending()->count(),
            'critical_violations' => AttendanceConstraintViolation::where('company_id', $companyId)->critical()->count(),
            'violations_by_type' => AttendanceConstraintViolation::where('company_id', $companyId)
                ->selectRaw('violation_type, COUNT(*) as count')
                ->groupBy('violation_type')
                ->pluck('count', 'violation_type'),
        ];

        return Json::item($stats, message: 'Statistics retrieved successfully');
    }

    /**
     * Validate attendance against constraints.
     */
    public function validate(ValidateAttendanceRequest $request): JsonResponse
    {
        $validateDTO = $request->createValidateAttendanceDTO();

        $violations = $this->constraintService->validateAttendanceData($validateDTO->toArray());

        return Json::item([
            'is_valid' => empty($violations),
            'violations' => $violations
        ], message: 'Validation completed');
    }

    /**
     * Get constraint violations with filtering and pagination.
     */
    public function violations(GetViolationsRequest $request): JsonResponse
    {
        $filterDTO = $request->createFilterViolationDTO(Auth::user()->company_id);

        $result = $this->violationRepository->getViolationList(
            $filterDTO->toArray(),
            $filterDTO->getPage(),
            $filterDTO->getPerPage()
        );

        if ($result['pagination']) {
            return Json::items(
                                    $result['data'],
                paginationSettings: $result['pagination'],
                message:            'Violations retrieved successfully'
            );
        }

        return Json::items($result['data'], message: 'Violations retrieved successfully');
    }

    public function userConstraint()//: JsonResponse
    {
        $user = Auth::user();

        $result = $this->constraintService->getTodaysWorkRulesForUser($user);

         return Json::item($result, message: 'Violations retrieved successfully');
    }

    /**
     * Get constraint statistics.
     */
    public function statistics(GetStatisticsRequest $request): JsonResponse
    {
        $filterDTO = $request->createFilterStatisticsDTO(Auth::user()->company_id);

        $constraintStats = $this->constraintRepository->getConstraintStatistics($filterDTO->toArray());
        $violationStats = $this->violationRepository->getViolationStatistics($filterDTO->toArray());

        $stats = [
            'constraints' => $constraintStats,
            'violations' => $violationStats,
            'summary' => $this->violationRepository->getViolationsSummary($filterDTO->toArray())
        ];

        return Json::item($stats, message: 'Statistics retrieved successfully');
    }

    /**
     * Bulk activate constraints.
     */
    public function bulkActivate(BulkConstraintRequest $request): JsonResponse
    {
        $bulkDTO = $request->createBulkConstraintIdsDTO();

        $updated = $this->constraintRepository->bulkActivate($bulkDTO->getConstraintIds());

        return Json::success("Successfully activated {$updated} constraints");
    }

    /**
     * Bulk deactivate constraints.
     */
    public function bulkDeactivate(BulkConstraintRequest $request): JsonResponse
    {
        $bulkDTO = $request->createBulkConstraintIdsDTO();

        $updated = $this->constraintRepository->bulkDeactivate($bulkDTO->getConstraintIds());

        return Json::success("Successfully deactivated {$updated} constraints");
    }

    /**
     * Bulk delete constraints.
     */
    public function bulkDelete(BulkConstraintRequest $request): JsonResponse
    {
        $bulkDTO = $request->createBulkConstraintIdsDTO();

        $deleted = $this->constraintRepository->bulkDelete($bulkDTO->getConstraintIds());

        return Json::success("Successfully deleted {$deleted} constraints");
    }

    /**
     * Get constraints for a specific branch.
     */
    public function getConstraintsByBranch(string $branchId): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        $constraints = $this->constraintService->getConstraintsForBranch($branchId, $companyId);

        return Json::items($constraints, message: 'Branch constraints retrieved successfully');
    }

    /**
     * Get inherited constraints for a branch.
     */
    public function getInheritedConstraints(string $branchId): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        $constraints = AttendanceConstraint::where('company_id', $companyId)
            ->where('inherit_from_parent', true)
            ->whereHas('branch', function ($query) use ($branchId) {
                // Get constraints from parent branches
                $query->where('id', '!=', $branchId);
            })
            ->with(['branch', 'user', 'creator'])
            ->get();

        return Json::items($constraints, message: 'Inherited constraints retrieved successfully');
    }

    /**
     * Bulk assign constraints to a branch.
     */
    public function bulkAssignToBranch(string $branchId, BulkConstraintRequest $request): JsonResponse
    {
        $bulkDTO = $request->createBulkConstraintIdsDTO();
        $updatedBy = Auth::id();

        $updated = $this->constraintRepository->bulkUpdateBranch(
            $bulkDTO->getConstraintIds(),
            $branchId,
            $updatedBy
        );

        return Json::success("Successfully assigned {$updated} constraints to branch");
    }

    /**
     * List all additional attendance constraints assigned to a user.
     */
    public function getUserAdditionalConstraints(Request $request, string $userId): JsonResponse
    {
        $user = \Modules\User\Models\User::findOrFail($userId);

        $constraints = $user->additionalAttendanceConstraints()
            ->where('company_id', Auth::user()->company_id)
            ->get();

        $presentedData = $constraints->map(fn($c) => (new ConstraintPresenter($c))->present())->values()->all();

        return Json::items(
            mainItems: $presentedData,
            message: 'User additional constraints retrieved successfully'
        );
    }

    /**
     * Assign one or more additional attendance constraints to a user.
     * Accepts: { "constraint_ids": ["uuid", ...] }
     */
    public function assignUserConstraints(Request $request, string $userId): JsonResponse
    {
        $user = \Modules\User\Models\User::findOrFail($userId);

        $constraintIds = $request->input('constraint_ids');

        // Handle null, non-array, or empty array
        if ($constraintIds === null || !is_array($constraintIds) || empty($constraintIds)) {
            // Clear all additional constraints
            $user->additionalAttendanceConstraints()->detach();
            $assignedCount = 0;
        } else {
            $validIds = AttendanceConstraint::whereIn('id', $constraintIds)
                ->pluck('id')
                ->values()
                ->toArray();

            $user->additionalAttendanceConstraints()->sync($validIds);
            $assignedCount = count($validIds);
        }

        $this->constraintService->bumpApplicableConstraintsCacheForCompany(
            (string) Auth::user()->company_id
        );

        return Json::item(
            ['assigned_count' => $assignedCount],
            message: 'Constraints assigned to user successfully'
        );
    }

    /**
     * Remove a specific additional attendance constraint from a user.
     */
    public function removeUserConstraint(Request $request, string $userId, string $constraintId): JsonResponse
    {
        $user = \Modules\User\Models\User::findOrFail($userId);

        $user->additionalAttendanceConstraints()->detach($constraintId);

        $this->constraintService->bumpApplicableConstraintsCacheForCompany(
            (string) Auth::user()->company_id
        );

        return Json::item([], 'Constraint removed from user successfully');
    }

    /**
     * Update only basic info: constraint_name, constraint_type, branch_ids.
     */
    public function updateBasicInfo(Request $request, string $constraintId): JsonResponse
    {
        $request->validate([
            'constraint_name' => ['sometimes', 'required', 'string', 'max:255'],
            'constraint_type' => ['sometimes', 'required', 'string', 'in:' . implode(',', array_keys(AttendanceConstraint::getConstraintArrayTypes()))],
            'branch_ids'      => ['sometimes', 'nullable', 'array'],
            'branch_ids.*'    => ['exists:management_hierarchies,id'],
        ]);

        $constraint = $this->constraintRepository->getConstraint(Uuid::fromString($constraintId));

        $data = ['updated_by' => Auth::id()];
        if ($request->has('constraint_name')) {
            $data['constraint_name'] = $request->input('constraint_name');
        }
        if ($request->has('constraint_type')) {
            $data['constraint_type'] = $request->input('constraint_type');
        }
        if ($request->has('branch_ids')) {
            $data['branch_ids'] = $request->input('branch_ids');
        }

        $constraint->update($data);
        $this->constraintService->bumpApplicableConstraintsCacheForCompany((string) Auth::user()->company_id);

        $constraint->load(['users', 'creator', 'updater']);
        $presented = (new ConstraintPresenter($constraint))->getData();

        return Json::item($presented, message: 'Constraint basic info updated successfully');
    }

    /**
     * Get all employees assigned to this attendance constraint.
     * Includes users assigned via user_professional_datas AND via attendance_constraint_user pivot.
     */
    public function getConstraintEmployees(Request $request, string $constraintId): JsonResponse
    {
        $constraint = $this->constraintRepository->getConstraint(Uuid::fromString($constraintId));
        $companyId = Auth::user()->company_id;

        $mainUsers = UserProfessionalData::where('attendance_constraint_id', $constraintId)
            ->where('company_id', $companyId)
            ->with(['user' => function ($q) {
                $q->withoutTenancy();
            }])
            ->get()
            ->pluck('user')
            ->filter()
            ->map(fn($u) => [
                'id'     => $u->id,
                'name'   => $u->name,
                'email'  => $u->email,
                'phone'  => $u->phone ?? null,
                'source' => 'main',
            ]);

        $pivotUsers = $constraint->users()
            ->get()
            ->map(fn($u) => [
                'id'     => $u->id,
                'name'   => $u->name,
                'email'  => $u->email,
                'phone'  => $u->phone ?? null,
                'source' => 'additional',
            ]);

        $allUsers = $mainUsers->merge($pivotUsers)->unique('id')->values()->all();

        return Json::items($allUsers, message: 'Constraint employees retrieved successfully');
    }

    /**
     * Assign an employee to the main attendance constraint (sets attendance_constraint_id on user_professional_datas).
     */
    public function assignEmployeeToConstraint(Request $request, string $constraintId): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $constraint = $this->constraintRepository->getConstraint(Uuid::fromString($constraintId));

        $professionalData = UserProfessionalData::where('user_id', $request->input('user_id'))
            ->where('company_id', Auth::user()->company_id)
            ->first();

        if (!$professionalData) {
            return Json::error('User professional data not found', 404);
        }

        $professionalData->update([
            'attendance_constraint_id' => $constraintId,
        ]);

        $this->constraintService->bumpApplicableConstraintsCacheForCompany((string) Auth::user()->company_id);

        return Json::item(
            ['user_id' => $request->input('user_id'), 'constraint_id' => $constraintId],
            message: 'Employee assigned to constraint successfully'
        );
    }

    /**
     * Create one or more additional locations for a constraint.
     */
    public function createLocations(Request $request, string $constraintId): JsonResponse
    {
        $request->validate([
            'locations'              => ['required', 'array', 'min:1'],
            'locations.*.name'       => ['nullable', 'string', 'max:255'],
            'locations.*.latitude'   => ['required', 'numeric', 'between:-90,90'],
            'locations.*.longitude'  => ['required', 'numeric', 'between:-180,180'],
            'locations.*.radius'     => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        $constraint = $this->constraintRepository->getConstraint(Uuid::fromString($constraintId));
        $companyId = Auth::user()->company_id;

        $created = [];
        foreach ($request->input('locations') as $loc) {
            $created[] = AttendanceConstraintLocation::create([
                'attendance_constraint_id' => $constraintId,
                'company_id'              => $companyId,
                'name'                    => $loc['name'] ?? null,
                'latitude'                => $loc['latitude'],
                'longitude'               => $loc['longitude'],
                'radius'                  => $loc['radius'],
                'created_by'              => Auth::id(),
            ]);
        }

        $this->constraintService->bumpApplicableConstraintsCacheForCompany((string) $companyId);

        return Json::item($created, message: 'Locations created successfully');
    }

    /**
     * Get all additional locations for a specific constraint.
     */
    public function getLocations(string $constraintId): JsonResponse
    {
        $constraint = $this->constraintRepository->getConstraint(Uuid::fromString($constraintId));

        $locations = AttendanceConstraintLocation::where('attendance_constraint_id', $constraintId)
            ->get()
            ->map(fn($loc) => [
                'id'        => $loc->id,
                'name'      => $loc->name,
                'latitude'  => $loc->latitude,
                'longitude' => $loc->longitude,
                'radius'    => $loc->radius,
                'created_at' => $loc->created_at?->format('Y-m-d H:i:s'),
            ])
            ->values()
            ->all();

        return Json::items($locations, message: 'Constraint locations retrieved successfully');
    }

    /**
     * Update a specific location by ID.
     */
    public function updateLocation(Request $request, string $locationId): JsonResponse
    {
        $request->validate([
            'name'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude'  => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'radius'    => ['sometimes', 'required', 'integer', 'min:1', 'max:10000'],
        ]);

        $location = AttendanceConstraintLocation::where('id', $locationId)
            ->where('company_id', Auth::user()->company_id)
            ->firstOrFail();

        $data = [];
        if ($request->has('name')) {
            $data['name'] = $request->input('name');
        }
        if ($request->has('latitude')) {
            $data['latitude'] = $request->input('latitude');
        }
        if ($request->has('longitude')) {
            $data['longitude'] = $request->input('longitude');
        }
        if ($request->has('radius')) {
            $data['radius'] = $request->input('radius');
        }

        $location->update($data);

        $this->constraintService->bumpApplicableConstraintsCacheForCompany((string) Auth::user()->company_id);

        return Json::item([
            'id'        => $location->id,
            'name'      => $location->name,
            'latitude'  => $location->latitude,
            'longitude' => $location->longitude,
            'radius'    => $location->radius,
        ], message: 'Location updated successfully');
    }

    /**
     * Delete a specific location by ID.
     */
    public function deleteLocation(string $locationId): JsonResponse
    {
        $location = AttendanceConstraintLocation::where('id', $locationId)
            ->where('company_id', Auth::user()->company_id)
            ->firstOrFail();

        $location->delete();

        $this->constraintService->bumpApplicableConstraintsCacheForCompany((string) Auth::user()->company_id);

        return Json::success('Location deleted successfully');
    }

    /**
     * Get all day shifts (weekly schedule periods) for a specific constraint.
     */
    public function getDayShifts(string $constraintId): JsonResponse
    {
        $constraint = $this->constraintRepository->getConstraint(Uuid::fromString($constraintId));

        $config = $constraint->constraint_config ?? [];
        $timeRules = $config['time_rules'] ?? [];
        $weeklySchedule = $timeRules['weekly_schedule'] ?? [];

        $orderedDays = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        $shifts = [];
        foreach ($orderedDays as $day) {
            $daySchedule = $weeklySchedule[$day] ?? ['enabled' => false, 'periods' => []];
            $shifts[] = [
                'day'     => $day,
                'enabled' => (bool) ($daySchedule['enabled'] ?? false),
                'periods' => $daySchedule['periods'] ?? [],
                'lateness_rules'      => $daySchedule['lateness_rules'] ?? null,
                'early_clock_in_rules' => $daySchedule['early_clock_in_rules'] ?? null,
            ];
        }

        return Json::item([
            'constraint_id'   => $constraint->id,
            'constraint_name' => $constraint->constraint_name,
            'max_over_time'   => $constraint->max_over_time,
            'shifts'          => $shifts,
        ], message: 'Day shifts retrieved successfully');
    }
}
