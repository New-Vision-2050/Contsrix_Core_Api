<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Models\AttendanceConstraintViolation;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Requests\CreateAttendanceConstraintRequest;
use Modules\Attendance\Requests\UpdateAttendanceConstraintRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AttendanceConstraintController extends Controller
{
    protected AttendanceConstraintService $constraintService;

    public function __construct(AttendanceConstraintService $constraintService)
    {
        $this->constraintService = $constraintService;
    }

    /**
     * Display a listing of attendance constraints.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AttendanceConstraint::with(['user', 'creator', 'updater'])
            ->where('company_id', Auth::user()->company_id);

        // Apply filters
        if ($request->has('constraint_type')) {
            $query->byType($request->constraint_type);
        }

        if ($request->has('constraint_name')) {
            $query->byName($request->constraint_name);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('user_id')) {
            $query->forUser($request->user_id);
        }

        $constraints = $query->byPriority()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $constraints,
            'meta' => [
                'constraint_types' => AttendanceConstraint::getConstraintTypes(),
            ]
        ]);
    }

    /**
     * Store a newly created attendance constraint.
     */
    public function store(CreateAttendanceConstraintRequest $request): JsonResponse
    {
        $constraint = AttendanceConstraint::create([
            'company_id' => Auth::user()->company_id,
            'user_id' => $request->user_id,
            'department_id' => $request->department_id,
            'constraint_type' => $request->constraint_type,
            'constraint_name' => $request->constraint_name,
            'constraint_config' => $request->constraint_config,
            'is_active' => $request->is_active ?? true,
            'priority' => $request->priority ?? 1,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'created_by' => Auth::id(),
            'notes' => $request->notes,
        ]);

        $constraint->load(['user', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Attendance constraint created successfully',
            'data' => $constraint
        ], 201);
    }

    /**
     * Display the specified attendance constraint.
     */
    public function show(AttendanceConstraint $constraint): JsonResponse
    {
        $constraint->load(['user', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'data' => $constraint
        ]);
    }

    /**
     * Update the specified attendance constraint.
     */
    public function update(UpdateAttendanceConstraintRequest $request, AttendanceConstraint $constraint): JsonResponse
    {
        $constraint->update([
            'user_id' => $request->user_id,
            'department_id' => $request->department_id,
            'constraint_type' => $request->constraint_type,
            'constraint_name' => $request->constraint_name,
            'constraint_config' => $request->constraint_config,
            'is_active' => $request->is_active,
            'priority' => $request->priority,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'updated_by' => Auth::id(),
            'notes' => $request->notes,
        ]);

        $constraint->load(['user', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'Attendance constraint updated successfully',
            'data' => $constraint
        ]);
    }

    /**
     * Remove the specified attendance constraint.
     */
    public function destroy(AttendanceConstraint $constraint): JsonResponse
    {
        $constraint->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance constraint deleted successfully'
        ]);
    }

    /**
     * Get constraint types and their available names.
     */
    public function getConstraintTypes(): JsonResponse
    {
        $types = AttendanceConstraint::getConstraintTypes();
        $typeDetails = [];

        foreach ($types as $typeKey => $typeName) {
            $typeDetails[$typeKey] = [
                'name' => $typeName,
                'constraints' => AttendanceConstraint::getConstraintNamesByType($typeKey)
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $typeDetails
        ]);
    }

    /**
     * Get violations for attendance constraints.
     */
    public function getViolations(Request $request): JsonResponse
    {
        $query = AttendanceConstraintViolation::with(['user', 'attendance', 'constraint', 'resolver'])
            ->where('company_id', Auth::user()->company_id);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('severity_level')) {
            $query->bySeverity($request->severity_level);
        }

        if ($request->has('violation_type')) {
            $query->byType($request->violation_type);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('unresolved') && $request->boolean('unresolved')) {
            $query->unresolved();
        }

        if ($request->has('critical') && $request->boolean('critical')) {
            $query->critical();
        }

        $violations = $query->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $violations,
            'meta' => [
                'violation_types' => AttendanceConstraintViolation::getViolationTypes(),
                'severity_levels' => AttendanceConstraintViolation::getSeverityLevels(),
                'statuses' => AttendanceConstraintViolation::getStatuses(),
            ]
        ]);
    }

    /**
     * Resolve a constraint violation.
     */
    public function resolveViolation(Request $request, AttendanceConstraintViolation $violation): JsonResponse
    {
        $request->validate([
            'resolution_notes' => 'nullable|string|max:1000'
        ]);

        $violation->resolve(Auth::id(), $request->resolution_notes);

        return response()->json([
            'success' => true,
            'message' => 'Violation resolved successfully',
            'data' => $violation->fresh(['resolver'])
        ]);
    }

    /**
     * Dismiss a constraint violation.
     */
    public function dismissViolation(Request $request, AttendanceConstraintViolation $violation): JsonResponse
    {
        $request->validate([
            'resolution_notes' => 'nullable|string|max:1000'
        ]);

        $violation->dismiss(Auth::id(), $request->resolution_notes);

        return response()->json([
            'success' => true,
            'message' => 'Violation dismissed successfully',
            'data' => $violation->fresh(['resolver'])
        ]);
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

        return response()->json([
            'success' => true,
            'data' => [
                'applicable_constraints' => $constraints,
                'violations' => $violations,
                'can_clock_in' => empty($violations)
            ]
        ]);
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

        return response()->json([
            'success' => true,
            'message' => "Successfully updated {$updated} constraints",
            'data' => ['updated_count' => $updated]
        ]);
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

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
