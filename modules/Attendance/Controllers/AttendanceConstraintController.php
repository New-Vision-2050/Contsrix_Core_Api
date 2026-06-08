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
use Modules\User\Models\User;
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
     * Get all employees assigned to this attendance constraint, paginated.
     * Includes users assigned via user_professional_datas AND via attendance_constraint_user pivot.
     */
    public function getConstraintEmployees(Request $request, string $constraintId): JsonResponse
    {
        $constraint = $this->constraintRepository->getConstraint(Uuid::fromString($constraintId));
        $companyId  = Auth::user()->company_id;

        $page    = max(1, (int) $request->input('page', 1));
        $perPage = max(1, (int) $request->input('per_page', 10));

        $mainUserIds = UserProfessionalData::where('attendance_constraint_id', $constraintId)
            ->where('company_id', $companyId)
            ->pluck('user_id')
            ->filter()
            ->map(fn($id) => (string) $id)
            ->unique()
            ->values()
            ->toArray();

        $pivotUserIds = $constraint->users()
            ->pluck('users.id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        $allUniqueIds = collect($mainUserIds)->merge($pivotUserIds)->unique()->values();

        $total    = $allUniqueIds->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $pagedIds = $allUniqueIds->forPage($page, $perPage)->values()->toArray();

        $mainSet = array_flip($mainUserIds);

        $users = User::withoutTenancy()
            ->whereIn('id', $pagedIds)
            ->get()
            ->map(fn($u) => [
                'id'     => $u->id,
                'name'   => $u->name,
                'email'  => $u->email,
                'phone'  => $u->phone ?? null,
                'source' => isset($mainSet[(string) $u->id]) ? 'main' : 'additional',
            ])
            ->values()
            ->all();

        $pagination = [
            'page'         => $page,
            'page_size'    => $perPage,
            'next_page'    => $lastPage > $page ? $page + 1 : $page,
            'last_page'    => $lastPage,
            'result_count' => $total,
        ];

        return Json::items(
            mainItems:          $users,
            paginationSettings: $pagination,
            message:            'Constraint employees retrieved successfully'
        );
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
     * Get all locations for a specific constraint (branch_locations JSON + additional table locations).
     */
    public function getLocations(string $constraintId): JsonResponse
    {
        $constraint = $this->constraintRepository->getConstraint(Uuid::fromString($constraintId));

        $branchLocations = collect($constraint->branch_locations ?? [])
            ->map(fn($loc, $index) => [
                'id'         => $loc['branch_id'] ?? ('branch_' . $index),
                'name'       => $loc['name'] ?? null,
                'latitude'   => isset($loc['latitude']) ? (float) $loc['latitude'] : null,
                'longitude'  => isset($loc['longitude']) ? (float) $loc['longitude'] : null,
                'radius'     => isset($loc['radius']) ? (int) $loc['radius'] : null,
                'source'     => 'branch',
                'created_at' => $constraint->created_at?->format('Y-m-d H:i:s'),
            ]);

        $additionalLocations = AttendanceConstraintLocation::where('attendance_constraint_id', $constraintId)
            ->get()
            ->map(fn($loc) => [
                'id'         => $loc->id,
                'name'       => $loc->name,
                'latitude'   => $loc->latitude,
                'longitude'  => $loc->longitude,
                'radius'     => $loc->radius,
                'source'     => 'additional',
                'created_at' => $loc->created_at?->format('Y-m-d H:i:s'),
            ]);

        $locations = $branchLocations->merge($additionalLocations)->values()->all();

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
     * Get shifts for a constraint in the same structure used by assignShifts,
     * so the frontend can render the current configuration without extra mapping.
     *
     * Response includes:
     *  - detected `mode` ("weekly" | "daily")
     *  - `days`    — enabled day names (weekly mode)
     *  - `periods` — shared periods    (weekly mode)
     *  - `schedule` — per-day map     (daily mode)
     *  - `raw_schedule` — full 7-day map always present (for reference)
     */
    public function getShifts(string $constraintId): JsonResponse
    {
        $constraint = $this->constraintRepository->getConstraint(Uuid::fromString($constraintId));

        $allDays        = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $config         = $constraint->constraint_config ?? [];
        $storedSchedule = $config['time_rules']['weekly_schedule'] ?? [];

        // Build a clean 7-day map from stored data
        $rawSchedule = [];
        foreach ($allDays as $day) {
            $dayData = $storedSchedule[$day] ?? [];
            $rawSchedule[$day] = [
                'enabled'              => (bool) ($dayData['enabled'] ?? false),
                'periods'              => $dayData['periods'] ?? [],
                'lateness_rules'       => $dayData['lateness_rules'] ?? null,
                'early_clock_in_rules' => $dayData['early_clock_in_rules'] ?? null,
            ];
        }

        // Detect mode:
        // Weekly  → all enabled days have exactly the same periods array
        // Daily   → at least two enabled days have different periods
        $enabledDays     = array_values(array_filter($allDays, fn($d) => $rawSchedule[$d]['enabled']));
        $detectedMode    = 'weekly';
        $sharedPeriods   = null;

        if (!empty($enabledDays)) {
            $sharedPeriods = $rawSchedule[$enabledDays[0]]['periods'];

            foreach ($enabledDays as $day) {
                // Compare by JSON serialisation — order-insensitive within each period object
                $normalize = fn(array $periods) => collect($periods)
                    ->map(fn($p) => [
                        'start_time'          => $p['start_time']          ?? $p['startTime'] ?? '',
                        'end_time'            => $p['end_time']            ?? $p['endTime'] ?? '',
                        'extends_to_next_day' => (bool) ($p['extends_to_next_day'] ?? false),
                    ])
                    ->sortBy('start_time')
                    ->values()
                    ->toArray();

                if (json_encode($normalize($rawSchedule[$day]['periods'])) !== json_encode($normalize($sharedPeriods))) {
                    $detectedMode  = 'daily';
                    $sharedPeriods = null;
                    break;
                }
            }
        }

        // Normalise period keys to snake_case for the response
        $normalisePeriods = fn(array $periods) => array_values(array_map(fn($p) => [
            'start_time'          => $p['start_time']          ?? $p['startTime'] ?? '',
            'end_time'            => $p['end_time']            ?? $p['endTime'] ?? '',
            'extends_to_next_day' => (bool) ($p['extends_to_next_day'] ?? false),
        ], $periods));

        $response = [
            'constraint_id'   => $constraint->id,
            'constraint_name' => $constraint->constraint_name,
            'max_over_time'   => $constraint->max_over_time,
            'mode'            => $detectedMode,
        ];

        if ($detectedMode === 'weekly') {
            $response['days']    = $enabledDays;
            $response['periods'] = $sharedPeriods !== null ? $normalisePeriods($sharedPeriods) : [];
        } else {
            $dailySchedule = [];
            foreach ($enabledDays as $day) {
                $dailySchedule[$day] = [
                    'periods' => $normalisePeriods($rawSchedule[$day]['periods']),
                ];
            }
            $response['schedule'] = $dailySchedule;
        }

        // Always include the full 7-day map for completeness
        $response['raw_schedule'] = collect($allDays)->mapWithKeys(fn($day) => [
            $day => [
                'enabled'              => $rawSchedule[$day]['enabled'],
                'periods'              => $normalisePeriods($rawSchedule[$day]['periods']),
                'lateness_rules'       => $rawSchedule[$day]['lateness_rules'],
                'early_clock_in_rules' => $rawSchedule[$day]['early_clock_in_rules'],
            ],
        ])->all();

        return Json::item($response, message: 'Shifts retrieved successfully');
    }

    /**
     * Assign shifts to a constraint's weekly schedule.
     *
     * Two modes:
     *  - "weekly": one set of periods applied to all checked days; unchecked days become holidays.
     *  - "daily":  each day carries its own periods; only days present in "schedule" are enabled.
     *
     * Defaults applied to every enabled day when no existing rules are present:
     *   lateness_rules      = { lateness_period: 30, lateness_unit: "minute" }
     *   early_clock_in_rules = { allowed_minutes_before: 30 }
     *
     * Only time_rules.weekly_schedule is replaced; the rest of constraint_config
     * (default_location, type_attendance, …) is preserved unchanged.
     */
    public function assignShifts(Request $request, string $constraintId): JsonResponse
    {
        $allDays = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $timeRegex = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/';

        $request->validate([
            'mode'    => ['required', 'string', 'in:weekly,daily'],
            // Weekly mode
            'days'    => ['required_if:mode,weekly', 'array'],
            'days.*'  => ['string', 'in:' . implode(',', $allDays)],
            'periods' => ['required_if:mode,weekly', 'array', 'min:1'],
            'periods.*.start_time'          => ['required_with:periods', 'regex:' . $timeRegex],
            'periods.*.end_time'            => ['required_with:periods', 'regex:' . $timeRegex],
            'periods.*.extends_to_next_day' => ['boolean'],
            // Daily mode
            'schedule'                               => ['required_if:mode,daily', 'array'],
            'schedule.*.periods'                     => ['array'],
            'schedule.*.periods.*.start_time'        => ['required_with:schedule.*.periods', 'regex:' . $timeRegex],
            'schedule.*.periods.*.end_time'          => ['required_with:schedule.*.periods', 'regex:' . $timeRegex],
            'schedule.*.periods.*.extends_to_next_day' => ['boolean'],
        ]);

        $constraint = $this->constraintRepository->getConstraint(Uuid::fromString($constraintId));

        $defaultLatenessRules = ['lateness_period' => 30, 'lateness_unit' => 'minute'];
        $defaultEarlyRules    = ['allowed_minutes_before' => 30];

        $existingConfig   = $constraint->constraint_config ?? [];
        $existingSchedule = $existingConfig['time_rules']['weekly_schedule'] ?? [];

        // Initialise all 7 days as disabled
        $weeklySchedule = [];
        foreach ($allDays as $day) {
            $weeklySchedule[$day] = [
                'enabled'             => false,
                'periods'             => [],
                'lateness_rules'      => $existingSchedule[$day]['lateness_rules']  ?? $defaultLatenessRules,
                'early_clock_in_rules' => $existingSchedule[$day]['early_clock_in_rules'] ?? $defaultEarlyRules,
            ];
        }

        $formatPeriods = fn(array $periods) => array_values(array_map(fn($p) => [
            'start_time'          => $p['start_time'],
            'end_time'            => $p['end_time'],
            'extends_to_next_day' => (bool) ($p['extends_to_next_day'] ?? false),
        ], $periods));

        if ($request->input('mode') === 'weekly') {
            $enabledDays      = array_map('strtolower', $request->input('days', []));
            $formattedPeriods = $formatPeriods($request->input('periods', []));

            foreach ($allDays as $day) {
                if (in_array($day, $enabledDays, true)) {
                    $weeklySchedule[$day]['enabled'] = true;
                    $weeklySchedule[$day]['periods'] = $formattedPeriods;
                }
            }
        } else {
            // daily mode — each day key carries its own periods array
            foreach ($request->input('schedule', []) as $day => $dayData) {
                $day = strtolower($day);
                if (!in_array($day, $allDays, true)) {
                    continue;
                }
                $formattedPeriods = $formatPeriods($dayData['periods'] ?? []);
                $weeklySchedule[$day]['enabled'] = !empty($formattedPeriods);
                $weeklySchedule[$day]['periods'] = $formattedPeriods;
            }
        }

        // Merge back into constraint_config — only replace weekly_schedule
        $config = $existingConfig;
        if (!isset($config['time_rules'])) {
            $config['time_rules'] = [];
        }
        $config['time_rules']['weekly_schedule'] = $weeklySchedule;

        $constraint->update([
            'constraint_config' => $config,
            'updated_by'        => Auth::id(),
        ]);

        $this->constraintService->bumpApplicableConstraintsCacheForCompany((string) Auth::user()->company_id);

        return Json::item([
            'constraint_id'   => $constraint->id,
            'mode'            => $request->input('mode'),
            'weekly_schedule' => $weeklySchedule,
        ], message: 'Shifts assigned successfully');
    }

    /**
     * Get constraint-level rules for a given constraint.
     */
    public function getRules(string $constraintId): JsonResponse
    {
        $constraint = $this->constraintRepository->getConstraint(Uuid::fromString($constraintId));

        $config   = $constraint->constraint_config ?? [];
        $allDays  = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $schedule = $config['time_rules']['weekly_schedule'] ?? [];

        // Extract per-day rules from the first enabled day (they are applied uniformly by updateRules).
        $latenessMinutes      = null;
        $earlyClockInMinutes  = null;
        $workingHours         = null;

        foreach ($allDays as $day) {
            $dayData = $schedule[$day] ?? [];
            if (!($dayData['enabled'] ?? false)) {
                continue;
            }

            if ($latenessMinutes === null && isset($dayData['lateness_rules']['lateness_period'])) {
                $latenessMinutes = (int) $dayData['lateness_rules']['lateness_period'];
            }

            if ($earlyClockInMinutes === null && isset($dayData['early_clock_in_rules']['allowed_minutes_before'])) {
                $earlyClockInMinutes = (int) $dayData['early_clock_in_rules']['allowed_minutes_before'];
            }

            if ($workingHours === null && isset($dayData['total_work_hours'])) {
                $workingHours = (float) $dayData['total_work_hours'];
            }

            if ($latenessMinutes !== null && $earlyClockInMinutes !== null && $workingHours !== null) {
                break;
            }
        }

        return Json::item([
            'constraint_id'          => $constraint->id,
            'max_over_time'          => $constraint->max_over_time,
            'out_zone_minutes'       => $constraint->out_zone_minutes,
            'max_working_hours'      => $constraint->max_working_hours,
            'out_zone_rules'         => $constraint->out_zone_rules,
            'lateness_minutes'       => $latenessMinutes,
            'early_clock_in_minutes' => $earlyClockInMinutes,
            'working_hours'          => $workingHours,
        ], message: 'Constraint rules retrieved successfully');
    }

    /**
     * Update constraint-level rules (lateness, early clock-in, max overtime,
     * working hours cap, out-of-zone hours and out-of-zone approval rules).
     *
     * lateness_minutes, early_clock_in_minutes, and working_hours are applied
     * uniformly to every day that already exists in the weekly_schedule.
     * Passing null for lateness_minutes / early_clock_in_minutes clears the rule.
     *
     * out_zone_rules is written to both the dedicated column and
     * constraint_config.time_rules.out_zone_rules so both access paths stay in sync.
     */
    public function updateRules(Request $request, string $constraintId): JsonResponse
    {
        $request->validate([
            'lateness_minutes'                            => ['sometimes', 'nullable', 'integer', 'min:0', 'max:480'],
            'early_clock_in_minutes'                      => ['sometimes', 'nullable', 'integer', 'min:0', 'max:480'],
            'max_over_time'                               => ['sometimes', 'nullable', 'integer', 'min:0'],
            'working_hours'                               => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:24'],
            'out_zone_minutes'                             => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_working_hours'                            => ['sometimes', 'nullable', 'integer', 'min:1', 'max:24'],
            'out_zone_rules'                              => ['sometimes', 'nullable', 'array'],
            'out_zone_rules.requires_approval'            => ['sometimes', 'boolean'],
            'out_zone_rules.approval_threshold_minutes'   => ['sometimes', 'integer', 'min:0'],
            'out_zone_rules.unit'                         => ['sometimes', 'string', 'in:minute,hour,day'],
        ]);

        $constraint = $this->constraintRepository->getConstraint(Uuid::fromString($constraintId));
        $allDays    = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $updates    = ['updated_by' => Auth::id()];

        if ($request->has('max_over_time')) {
            $updates['max_over_time'] = $request->input('max_over_time');
        }

        if ($request->has('out_zone_minutes')) {
            $updates['out_zone_minutes'] = $request->input('out_zone_minutes');
        }

        if ($request->has('max_working_hours')) {
            $updates['max_working_hours'] = $request->input('max_working_hours');
        }

        $hasConfigUpdate = $request->hasAny([
            'lateness_minutes',
            'early_clock_in_minutes',
            'working_hours',
            'out_zone_rules',
            'out_zone_minutes',
        ]);

        if ($hasConfigUpdate) {
            $config = $constraint->constraint_config ?? [];

            foreach ($allDays as $day) {
                if ($request->has('lateness_minutes')) {
                    $min = $request->input('lateness_minutes');
                    $config['time_rules']['weekly_schedule'][$day]['lateness_rules'] = $min !== null
                        ? ['lateness_period' => (int) $min, 'lateness_unit' => 'minute']
                        : null;
                }

                if ($request->has('early_clock_in_minutes')) {
                    $min = $request->input('early_clock_in_minutes');
                    $config['time_rules']['weekly_schedule'][$day]['early_clock_in_rules'] = $min !== null
                        ? ['allowed_minutes_before' => (int) $min]
                        : null;
                }

                if ($request->has('working_hours')) {
                    $hours = $request->input('working_hours');
                    if ($hours !== null) {
                        $config['time_rules']['weekly_schedule'][$day]['total_work_hours'] = (float) $hours;
                    } else {
                        unset($config['time_rules']['weekly_schedule'][$day]['total_work_hours']);
                    }
                }
            }

            if ($request->has('out_zone_rules') || $request->has('out_zone_minutes')) {
                $outZoneRules = $request->input('out_zone_rules')
                    ?? $config['time_rules']['out_zone_rules']
                    ?? [];

                if ($request->has('out_zone_minutes') && $request->input('out_zone_minutes') !== null) {
                    $outZoneRules['duration_minutes'] = (int) $request->input('out_zone_minutes');
                }

                $config['time_rules']['out_zone_rules'] = $outZoneRules;
                $updates['out_zone_rules'] = $outZoneRules;
            }

            $updates['constraint_config'] = $config;
        }

        $constraint->update($updates);
        $this->constraintService->bumpApplicableConstraintsCacheForCompany((string) Auth::user()->company_id);

        $fresh = $constraint->fresh();

        return Json::item([
            'constraint_id'          => $fresh->id,
            'max_over_time'          => $fresh->max_over_time,
            'out_zone_minutes'        => $fresh->out_zone_minutes,
            'max_working_hours'       => $fresh->max_working_hours,
            'out_zone_rules'         => $fresh->out_zone_rules,
            'lateness_minutes'       => $request->has('lateness_minutes')       ? $request->input('lateness_minutes')       : null,
            'early_clock_in_minutes' => $request->has('early_clock_in_minutes') ? $request->input('early_clock_in_minutes') : null,
            'working_hours'          => $request->has('working_hours')          ? $request->input('working_hours')          : null,
        ], message: 'Constraint rules updated successfully');
    }

    /**
     * Return the main constraint (and any additional constraints) for a given employee,
     * including all locations from both the branch_locations JSON column and the
     * attendance_constraint_locations table.
     *
     * GET /attendance/constraints/employees/{userId}/constraint-locations
     */
    public function getEmployeeConstraintLocations(string $userId): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        $professionalData = UserProfessionalData::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->first();

        if (!$professionalData || !$professionalData->attendance_constraint_id) {
            return Json::item([
                'main_constraint'       => null,
                'additional_constraints' => [],
            ], message: 'No constraint assigned to this employee');
        }

        $mainConstraint = AttendanceConstraint::withoutTenancy()
            ->with('additionalLocations')
            ->where('id', $professionalData->attendance_constraint_id)
            ->first();

        $additionalConstraints = User::withoutTenancy()
            ->where('id', $userId)
            ->first()
            ?->additionalAttendanceConstraints()
            ->with('additionalLocations')
            ->get() ?? collect();

        return Json::item([
            'main_constraint'        => $mainConstraint
                ? $this->formatConstraintWithLocations($mainConstraint)
                : null,
            'additional_constraints' => $additionalConstraints
                ->map(fn($c) => $this->formatConstraintWithLocations($c))
                ->values()
                ->all(),
        ], message: 'Employee constraint locations retrieved successfully');
    }

    /**
     * Swap one or more constraints for a given employee.
     * Each entry in `replacements` must supply `old_constraint_id` and `new_constraint_id`.
     * The method auto-detects whether the old constraint is the employee's main constraint
     * (user_professional_datas.attendance_constraint_id) or an additional constraint
     * (attendance_constraint_user pivot) and updates accordingly.
     *
     * PUT /attendance/constraints/employees/{userId}/assign-constraint
     * Body: { "replacements": [{ "old_constraint_id": "uuid", "new_constraint_id": "uuid" }] }
     */
    public function updateEmployeeConstraint(Request $request, string $userId): JsonResponse
    {
        $request->validate([
            'replacements'                       => ['required', 'array', 'min:1'],
            'replacements.*.old_constraint_id'   => ['required', 'uuid'],
            'replacements.*.new_constraint_id'   => ['required', 'uuid', 'exists:attendance_constraints,id'],
        ]);

        $companyId = Auth::user()->company_id;

        $professionalData = UserProfessionalData::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->first();

        if (!$professionalData) {
            return Json::error('User professional data not found', 404);
        }

        $results = [];

        foreach ($request->input('replacements') as $replacement) {
            $oldId = $replacement['old_constraint_id'];
            $newId = $replacement['new_constraint_id'];

            if ((string) $professionalData->attendance_constraint_id === $oldId) {
                $professionalData->update(['attendance_constraint_id' => $newId]);

                $results[] = ['old_constraint_id' => $oldId, 'new_constraint_id' => $newId, 'type' => 'main'];
                continue;
            }

            $pivotExists = DB::table('attendance_constraint_user')
                ->where('user_id', $userId)
                ->where('attendance_constraint_id', $oldId)
                ->exists();

            if ($pivotExists) {
                DB::table('attendance_constraint_user')
                    ->where('user_id', $userId)
                    ->where('attendance_constraint_id', $oldId)
                    ->update(['attendance_constraint_id' => $newId]);

                $results[] = ['old_constraint_id' => $oldId, 'new_constraint_id' => $newId, 'type' => 'additional'];
                continue;
            }

            $results[] = ['old_constraint_id' => $oldId, 'new_constraint_id' => $newId, 'type' => 'not_found'];
        }

        $this->constraintService->bumpApplicableConstraintsCacheForCompany((string) $companyId);

        return Json::item(['replacements' => $results], message: 'Employee constraints updated successfully');
    }

    /**
     * Format a constraint model with its full location data for API responses.
     */
    private function formatConstraintWithLocations(AttendanceConstraint $constraint): array
    {
        $branchLocations = collect($constraint->branch_locations ?? [])
            ->map(fn($loc, $branchId) => array_merge(['branch_id' => $branchId], $loc))
            ->values()
            ->all();

        $additionalLocations = ($constraint->relationLoaded('additionalLocations')
            ? $constraint->additionalLocations
            : $constraint->additionalLocations()->get()
        )->map(fn($loc) => [
            'id'        => $loc->id,
            'name'      => $loc->name,
            'latitude'  => $loc->latitude,
            'longitude' => $loc->longitude,
            'radius'    => $loc->radius,
        ])->values()->all();

        return [
            'id'                   => $constraint->id,
            'constraint_name'      => $constraint->constraint_name,
            'constraint_type'      => $constraint->constraint_type,
            'is_active'            => $constraint->is_active,
            'branch_locations'     => $branchLocations,
            'additional_locations' => $additionalLocations,
        ];
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
