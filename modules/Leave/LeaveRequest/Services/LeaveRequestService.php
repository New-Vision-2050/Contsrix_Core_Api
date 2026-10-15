<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\LeaveRequest;
use Modules\Attendance\Support\ScheduledWorkDays;
use Modules\Leave\LeavePolicy\Models\LeavePolicy;
use Modules\Leave\LeaveRequest\DTO\CreateLeaveRequestDTO;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;

/**
 * Employee leave requests (mobile form).
 *
 * Creation rules (all enforced server-side per the assigned policy):
 * - the policy must be assigned to the user (or be the company default)
 * - attachment required when the leave type flags requires_attachment
 * - min_notice_days between today and start_date
 * - no overlap with existing pending/approved requests
 * - total_days counted per the policy day_calculation_type (work_day uses the
 *   employee's scheduled work days; calendar counts every day)
 * - sufficient yearly balance unless the policy allows exceeding it
 *
 * Approval goes through the procedure workflow; on final approval the request
 * becomes visible as إجازة in the calendar, attendance/today API and reports.
 */
class LeaveRequestService
{
    public function __construct(
        private readonly LeaveRequestWorkflowService $workflowService,
        private readonly UserLeavePolicyService $userLeavePolicyService,
    ) {}

    /**
     * Create a leave request for the authenticated employee and start its workflow.
     */
    public function create(User $user, CreateLeaveRequestDTO $dto): LeaveRequest
    {
        $companyId = (string) tenant('id');

        $policy = LeavePolicy::query()
            ->where('company_id', $companyId)
            ->findOrFail($dto->leave_policy_id);
        $policy->loadMissing('leaveType');

        abort_unless(
            $this->userLeavePolicyService->userHasPolicy((string) $user->id, (string) $policy->id),
            422,
            'This leave policy is not assigned to you.'
        );

        $startDate = Carbon::parse($dto->start_date)->startOfDay();
        $endDate = Carbon::parse($dto->end_date)->startOfDay();

        abort_if($endDate->lt($startDate), 422, 'end_date must be on or after start_date.');

        $this->assertAttachmentRequirement($policy, $dto);
        $this->assertMinNoticeDays($policy, $startDate);
        $this->assertNoOverlap((string) $user->id, $startDate, $endDate);

        $schedule = $this->scheduleForUser($user);
        $totalDays = (int) $this->userLeavePolicyService->countDaysInWindow(
            $startDate,
            $endDate,
            $startDate,
            $endDate,
            $policy,
            $schedule,
        );

        abort_if($totalDays < 1, 422, 'The selected range contains no countable leave days.');

        $this->assertSufficientBalance($user, $policy, $totalDays, (int) $startDate->year);

        return DB::transaction(function () use ($user, $dto, $policy, $companyId, $startDate, $endDate, $totalDays) {
            /** @var LeaveRequest $leaveRequest */
            $leaveRequest = LeaveRequest::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'user_id' => (string) $user->id,
                'company_id' => $companyId,
                'leave_type_id' => (string) $policy->leave_type_id,
                'leave_policy_id' => (string) $policy->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_days' => $totalDays,
                'reason' => $dto->reason,
                'status' => LeaveRequest::STATUS_PENDING,
                'requested_by' => (string) $user->id,
                'is_emergency' => $dto->is_emergency,
                'attachments' => $dto->attachments,
                'notes' => $dto->notes,
            ]);

            $process = $this->workflowService->startForLeaveRequest(
                $leaveRequest,
                $this->branchIdForUser($user),
            );

            // No configured workflow (or no resolvable action takers) → auto-approve.
            if ($process === null) {
                $leaveRequest->forceFill([
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'approved_at' => now(),
                ])->save();
            }

            return $leaveRequest->fresh(['leavePolicy.leaveType', 'processes.steps']);
        });
    }

    /**
     * Paginated list of the authenticated employee's own requests (mobile).
     */
    public function myRequests(string $userId, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));

        return LeaveRequest::query()
            ->where('user_id', $userId)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['leave_policy_id'] ?? null, fn ($q, $id) => $q->where('leave_policy_id', $id))
            ->when($filters['year'] ?? null, function ($q, $year) {
                $q->whereYear('start_date', '<=', (int) $year)
                    ->whereYear('end_date', '>=', (int) $year);
            })
            ->with(['leavePolicy.leaveType', 'processes.steps'])
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    /**
     * Paginated admin list of all company requests.
     */
    public function listForAdmin(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));

        return LeaveRequest::query()
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['leave_policy_id'] ?? null, fn ($q, $id) => $q->where('leave_policy_id', $id))
            ->when($filters['start_date'] ?? null, fn ($q, $date) => $q->whereDate('end_date', '>=', $date))
            ->when($filters['end_date'] ?? null, fn ($q, $date) => $q->whereDate('start_date', '<=', $date))
            ->with(['user', 'leavePolicy.leaveType', 'processes.steps'])
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    /**
     * Paginated company report (dashboard table). Same role as
     * admin/employee-tasks/report — every request, not only the caller's inbox.
     */
    public function reportList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min(100, $perPage));

        return LeaveRequest::query()
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['leave_policy_id'] ?? null, fn ($q, $id) => $q->where('leave_policy_id', $id))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('reason', 'like', '%' . $search . '%')
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->with([
                'user',
                'leavePolicy.leaveType',
                'processes.steps.procedureSettingStep',
                'processes.steps.assignedUser',
                'processes.steps.actionByUser',
                'processes.procedureSetting',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($filters['page'] ?? 1)));
    }

    /**
     * Full report detail: request fields + every workflow process and the
     * action taken on each step (who, when, approve/reject).
     */
    public function reportDetail(string $id): LeaveRequest
    {
        return LeaveRequest::query()
            ->with([
                'user',
                'requester',
                'approver',
                'rejector',
                'leavePolicy.leaveType',
                'processes.steps.procedureSettingStep',
                'processes.steps.assignedUser',
                'processes.steps.actionByUser',
                'processes.procedureSetting',
            ])
            ->findOrFail($id);
    }

    public function getById(string $id): LeaveRequest
    {
        return LeaveRequest::query()
            ->with(['user', 'leavePolicy.leaveType', 'processes.steps'])
            ->findOrFail($id);
    }

    /**
     * Approver inbox: pending requests whose CURRENT pending workflow step
     * authorizes the given user (assigned_user_id or authorized_user_ids JSON).
     * Same semantics as the admin employee-tasks inbox — used by the unified
     * admin inbox, its counts, and the dedicated leave inbox endpoints.
     */
    public function inboxForApprover(string $userId, array $filters = []): Collection
    {
        return LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->whereHas('processes', function ($q) use ($userId) {
                $q->where('status', ProcessStatus::InProgress)
                    ->whereHas('steps', function ($q) use ($userId) {
                        $q->where('status', ProcessStepStatus::Pending)
                            ->where(function ($q) use ($userId) {
                                $q->where('assigned_user_id', $userId)
                                    ->orWhereJsonContains('authorized_user_ids', $userId);
                            });
                    });
            })
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->with([
                'user',
                'leavePolicy.leaveType',
                'processes' => fn ($q) => $q->where('status', ProcessStatus::InProgress)
                    ->with(['steps.procedureSettingStep', 'steps.assignedUser']),
            ])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Approve the request on behalf of the current user (acts on their pending
     * workflow step). Final approval flips the status via model hooks.
     */
    public function approve(string $id): LeaveRequest
    {
        $leaveRequest = $this->getById($id);

        abort_unless($leaveRequest->isPending(), 422, 'Only pending leave requests can be approved.');

        $this->workflowService->approveForCurrentUser($id);

        return $leaveRequest->fresh(['leavePolicy.leaveType', 'processes.steps']);
    }

    /**
     * Reject the request on behalf of the current user.
     */
    public function reject(string $id, string $reason): LeaveRequest
    {
        $leaveRequest = $this->getById($id);

        abort_unless($leaveRequest->isPending(), 422, 'Only pending leave requests can be rejected.');

        // Stored up-front: the onProcessFailed hook fires while rejecting the step.
        $leaveRequest->forceFill(['rejection_reason' => $reason])->save();

        $this->workflowService->rejectForCurrentUser($id);

        return $leaveRequest->fresh(['leavePolicy.leaveType', 'processes.steps']);
    }

    /**
     * Cancel a pending request (by its owner or an admin). Open workflow
     * processes are failed so no stale approval steps remain.
     */
    public function cancel(string $id, User $actor): LeaveRequest
    {
        $leaveRequest = $this->getById($id);

        abort_unless($leaveRequest->isPending(), 422, 'Only pending leave requests can be cancelled.');
        abort_unless(
            (string) $leaveRequest->user_id === (string) $actor->id,
            403,
            'You can only cancel your own leave requests.'
        );

        return DB::transaction(function () use ($leaveRequest, $actor) {
            $this->workflowService->cancelOpenProcesses($leaveRequest, (string) $actor->id);

            $leaveRequest->forceFill(['status' => LeaveRequest::STATUS_CANCELLED])->save();

            return $leaveRequest->fresh(['leavePolicy.leaveType', 'processes.steps']);
        });
    }

    private function assertAttachmentRequirement(LeavePolicy $policy, CreateLeaveRequestDTO $dto): void
    {
        if (($policy->leaveType?->requires_attachment) && empty($dto->attachments)) {
            abort(422, 'This leave type requires an attachment.');
        }
    }

    private function assertMinNoticeDays(LeavePolicy $policy, Carbon $startDate): void
    {
        $minNoticeDays = (int) ($policy->min_notice_days ?? 0);

        if ($minNoticeDays < 1) {
            return;
        }

        $earliestStart = Carbon::today()->addDays($minNoticeDays);

        abort_if(
            $startDate->lt($earliestStart),
            422,
            "This leave policy requires at least {$minNoticeDays} day(s) notice before the start date."
        );
    }

    private function assertNoOverlap(string $userId, Carbon $startDate, Carbon $endDate): void
    {
        $overlaps = LeaveRequest::query()
            ->where('user_id', $userId)
            ->whereIn('status', [LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED])
            ->whereDate('start_date', '<=', $endDate->toDateString())
            ->whereDate('end_date', '>=', $startDate->toDateString())
            ->exists();

        abort_if($overlaps, 422, 'You already have a leave request overlapping the selected dates.');
    }

    private function assertSufficientBalance(User $user, LeavePolicy $policy, int $totalDays, int $year): void
    {
        if ($policy->allow_exceed_balance) {
            return;
        }

        $balance = $this->userLeavePolicyService->balanceFor($user, $policy, $year);

        abort_if(
            $totalDays > $balance['available_days'],
            422,
            "Insufficient leave balance: requested {$totalDays} day(s), available {$balance['available_days']} day(s) in {$year}."
        );
    }

    private function scheduleForUser(User $user): ScheduledWorkDays
    {
        $user->loadMissing(['userProfessionalData.attendanceConstraint', 'professionalData.attendanceConstraint']);

        return ScheduledWorkDays::fromConstraint(
            $user->userProfessionalData?->attendanceConstraint
                ?? $user->professionalData?->attendanceConstraint
        );
    }

    private function branchIdForUser(User $user): ?string
    {
        $user->loadMissing(['userProfessionalData', 'professionalData']);

        $branchId = $user->userProfessionalData?->branch_id
            ?? $user->professionalData?->branch_id;

        return $branchId !== null ? (string) $branchId : null;
    }
}
