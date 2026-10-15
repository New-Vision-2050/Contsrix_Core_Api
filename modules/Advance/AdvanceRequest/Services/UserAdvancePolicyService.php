<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\Services;

use Modules\Advance\AdvancePolicy\Models\AdvancePolicy;
use Modules\Advance\AdvanceRequest\Models\AdvanceRequest;
use Modules\Advance\AdvanceRequest\Models\UserAdvancePolicy;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;

/**
 * Assigns advance policies to users and computes live availability.
 *
 * Availability is computed on the fly from pending/approved advance requests:
 * a policy allows up to `max_active_advances` concurrent requests (pending +
 * approved), and each request is capped by `max_amount`. There is no yearly
 * reset — an approved advance counts against the cap until it is repaid
 * (repayment tracking is handled outside this module).
 */
class UserAdvancePolicyService
{
    /**
     * Replace the user's advance-policy set with the given list (sync semantics).
     *
     * @param list<string> $advancePolicyIds
     * @return \Illuminate\Database\Eloquent\Collection<int, UserAdvancePolicy>
     */
    public function syncForUser(string $userId, array $advancePolicyIds, ?string $assignedBy = null): \Illuminate\Database\Eloquent\Collection
    {
        $advancePolicyIds = array_values(array_unique(array_filter($advancePolicyIds)));
        $companyId = (string) tenant('id');

        $validIds = AdvancePolicy::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $advancePolicyIds)
            ->pluck('id')
            ->all();

        UserAdvancePolicy::query()
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->whereNotIn('advance_policy_id', $validIds)
            ->delete();

        foreach ($validIds as $policyId) {
            UserAdvancePolicy::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'advance_policy_id' => $policyId,
                ],
                [
                    'id' => Uuid::uuid4()->toString(),
                    'company_id' => $companyId,
                    'assigned_by' => $assignedBy,
                ]
            );
        }

        return UserAdvancePolicy::query()
            ->with(['advancePolicy'])
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->get();
    }

    /**
     * The user's assigned policies with live availability.
     *
     * @return list<array{assignment: UserAdvancePolicy, policy: AdvancePolicy, availability: array}>
     */
    public function listWithAvailabilityForUser(User $user): array
    {
        $assignments = UserAdvancePolicy::query()
            ->with(['advancePolicy'])
            ->where('user_id', (string) $user->id)
            ->get()
            ->filter(fn (UserAdvancePolicy $assignment) => $assignment->advancePolicy !== null)
            ->values();

        $result = [];
        foreach ($assignments as $assignment) {
            $result[] = [
                'assignment' => $assignment,
                'policy' => $assignment->advancePolicy,
                'availability' => $this->availabilityFor((string) $user->id, $assignment->advancePolicy),
            ];
        }

        return $result;
    }

    /**
     * Live availability of one policy for one user.
     *
     * @return array{active_count: int, pending_amount: float, active_amount: float, remaining_slots: int, can_request: bool, max_amount: float, max_active_advances: int}
     */
    public function availabilityFor(string $userId, AdvancePolicy $policy): array
    {
        $requests = AdvanceRequest::query()
            ->where('user_id', $userId)
            ->where('advance_policy_id', (string) $policy->id)
            ->whereIn('status', [AdvanceRequest::STATUS_PENDING, AdvanceRequest::STATUS_APPROVED]);

        $activeCount = (int) (clone $requests)->count();
        $pendingAmount = (float) (clone $requests)->where('status', AdvanceRequest::STATUS_PENDING)->sum('amount');
        $activeAmount = (float) (clone $requests)->where('status', AdvanceRequest::STATUS_APPROVED)->sum('amount');

        $maxActive = (int) ($policy->max_active_advances ?? 1);
        $remainingSlots = max(0, $maxActive - $activeCount);

        return [
            'active_count' => $activeCount,
            'pending_amount' => $pendingAmount,
            'active_amount' => $activeAmount,
            'remaining_slots' => $remainingSlots,
            'can_request' => $remainingSlots > 0,
            'max_amount' => (float) $policy->max_amount,
            'max_active_advances' => $maxActive,
        ];
    }

    /**
     * Whether the user has this policy assigned.
     */
    public function userHasPolicy(string $userId, string $advancePolicyId): bool
    {
        return UserAdvancePolicy::query()
            ->where('user_id', $userId)
            ->where('advance_policy_id', $advancePolicyId)
            ->exists();
    }
}
