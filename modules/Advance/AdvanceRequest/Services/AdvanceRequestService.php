<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Advance\AdvancePolicy\Models\AdvancePolicy;
use Modules\Advance\AdvanceRequest\DTO\CreateAdvanceRequestDTO;
use Modules\Advance\AdvanceRequest\Models\AdvanceRequest;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;

/**
 * Employee advance (سلفة) requests.
 *
 * Creation rules (all enforced server-side per the assigned policy):
 * - the policy must be assigned to the user
 * - amount within the policy max_amount
 * - installments within the policy max_installments
 * - attachment required when the policy flags requires_attachment
 * - concurrent (pending + approved) requests under the policy capped by
 *   max_active_advances
 *
 * Approval goes through the procedure workflow (type advance_request, form
 * createAdvanceRequest); final status is set by the model's workflow hooks.
 */
class AdvanceRequestService
{
    public function __construct(
        private readonly AdvanceRequestWorkflowService $workflowService,
        private readonly UserAdvancePolicyService $userAdvancePolicyService,
    ) {}

    /**
     * Create an advance request for the authenticated employee and start its workflow.
     */
    public function create(User $user, CreateAdvanceRequestDTO $dto): AdvanceRequest
    {
        $companyId = (string) tenant('id');

        $policy = AdvancePolicy::query()
            ->where('company_id', $companyId)
            ->findOrFail($dto->advance_policy_id);

        abort_unless(
            $this->userAdvancePolicyService->userHasPolicy((string) $user->id, (string) $policy->id),
            422,
            'This advance policy is not assigned to you.'
        );

        abort_if(
            $dto->amount > (float) $policy->max_amount,
            422,
            "Amount exceeds the policy maximum of {$policy->max_amount}."
        );

        abort_if(
            $dto->installments > (int) $policy->max_installments,
            422,
            "Installments exceed the policy maximum of {$policy->max_installments}."
        );

        if ($policy->requires_attachment && empty($dto->attachments)) {
            abort(422, 'This advance policy requires an attachment.');
        }

        $availability = $this->userAdvancePolicyService->availabilityFor((string) $user->id, $policy);

        abort_if(
            ! $availability['can_request'],
            422,
            "You already have {$availability['active_count']} active advance request(s) under this policy (maximum {$availability['max_active_advances']})."
        );

        return DB::transaction(function () use ($user, $dto, $policy, $companyId) {
            /** @var AdvanceRequest $advanceRequest */
            $advanceRequest = AdvanceRequest::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'user_id' => (string) $user->id,
                'company_id' => $companyId,
                'advance_policy_id' => (string) $policy->id,
                'amount' => $dto->amount,
                'installments' => $dto->installments,
                'reason' => $dto->reason,
                'status' => AdvanceRequest::STATUS_PENDING,
                'requested_by' => (string) $user->id,
                'attachments' => $dto->attachments,
                'notes' => $dto->notes,
            ]);

            $process = $this->workflowService->startForAdvanceRequest(
                $advanceRequest,
                $this->branchIdForUser($user),
            );

            // No configured workflow (or no resolvable action takers) → auto-approve.
            if ($process === null) {
                $advanceRequest->forceFill([
                    'status' => AdvanceRequest::STATUS_APPROVED,
                    'approved_at' => now(),
                ])->save();
            }

            return $advanceRequest->fresh(['advancePolicy', 'processes.steps']);
        });
    }

    /**
     * Paginated list of the authenticated employee's own requests.
     */
    public function myRequests(string $userId, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));

        return AdvanceRequest::query()
            ->where('user_id', $userId)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['advance_policy_id'] ?? null, fn ($q, $id) => $q->where('advance_policy_id', $id))
            ->with(['advancePolicy', 'processes.steps'])
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    /**
     * Paginated admin list of all company requests (sidebar: طلبات السلف).
     */
    public function listForAdmin(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));

        return AdvanceRequest::query()
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['advance_policy_id'] ?? null, fn ($q, $id) => $q->where('advance_policy_id', $id))
            ->with(['user', 'advancePolicy', 'processes.steps'])
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

        return AdvanceRequest::query()
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['advance_policy_id'] ?? null, fn ($q, $id) => $q->where('advance_policy_id', $id))
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
                'advancePolicy',
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
    public function reportDetail(string $id): AdvanceRequest
    {
        return AdvanceRequest::query()
            ->with([
                'user',
                'requester',
                'approver',
                'rejector',
                'advancePolicy',
                'processes.steps.procedureSettingStep',
                'processes.steps.assignedUser',
                'processes.steps.actionByUser',
                'processes.procedureSetting',
            ])
            ->findOrFail($id);
    }

    public function getById(string $id): AdvanceRequest
    {
        return AdvanceRequest::query()
            ->with(['user', 'advancePolicy', 'processes.steps'])
            ->findOrFail($id);
    }

    /**
     * Approver inbox: pending requests whose CURRENT pending workflow step
     * authorizes the given user (assigned_user_id or authorized_user_ids JSON).
     * Same semantics as the admin employee-tasks inbox — used by the unified
     * admin inbox, its counts, and the dedicated advance inbox endpoints.
     */
    public function inboxForApprover(string $userId, array $filters = []): Collection
    {
        return AdvanceRequest::query()
            ->where('status', AdvanceRequest::STATUS_PENDING)
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
                'advancePolicy',
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
    public function approve(string $id): AdvanceRequest
    {
        $advanceRequest = $this->getById($id);

        abort_unless($advanceRequest->isPending(), 422, 'Only pending advance requests can be approved.');

        $this->workflowService->approveForCurrentUser($id);

        return $advanceRequest->fresh(['advancePolicy', 'processes.steps']);
    }

    /**
     * Reject the request on behalf of the current user.
     */
    public function reject(string $id, string $reason): AdvanceRequest
    {
        $advanceRequest = $this->getById($id);

        abort_unless($advanceRequest->isPending(), 422, 'Only pending advance requests can be rejected.');

        // Stored up-front: the onProcessFailed hook fires while rejecting the step.
        $advanceRequest->forceFill(['rejection_reason' => $reason])->save();

        $this->workflowService->rejectForCurrentUser($id);

        return $advanceRequest->fresh(['advancePolicy', 'processes.steps']);
    }

    /**
     * Cancel a pending request (owner only). Open workflow processes are failed
     * so no stale approval steps remain.
     */
    public function cancel(string $id, User $actor): AdvanceRequest
    {
        $advanceRequest = $this->getById($id);

        abort_unless($advanceRequest->isPending(), 422, 'Only pending advance requests can be cancelled.');
        abort_unless(
            (string) $advanceRequest->user_id === (string) $actor->id,
            403,
            'You can only cancel your own advance requests.'
        );

        return DB::transaction(function () use ($advanceRequest, $actor) {
            $this->workflowService->cancelOpenProcesses($advanceRequest, (string) $actor->id);

            $advanceRequest->forceFill(['status' => AdvanceRequest::STATUS_CANCELLED])->save();

            return $advanceRequest->fresh(['advancePolicy', 'processes.steps']);
        });
    }

    private function branchIdForUser(User $user): ?string
    {
        $user->loadMissing(['userProfessionalData', 'professionalData']);

        $branchId = $user->userProfessionalData?->branch_id
            ?? $user->professionalData?->branch_id;

        return $branchId !== null ? (string) $branchId : null;
    }
}
