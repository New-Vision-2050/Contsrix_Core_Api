<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Modules\Attendance\Models\LeaveRequest;
use Modules\Attendance\Support\ScheduledWorkDays;
use Modules\Leave\LeavePolicy\Models\LeavePolicy;
use Modules\Leave\LeaveRequest\Models\UserLeavePolicy;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;

/**
 * Assigns leave policies to users and computes live per-year balances.
 *
 * Balances are computed on the fly from approved/pending leave requests inside the
 * current calendar-year window — so every 1st of January the balance "renews"
 * automatically (no yearly reset job, no drift between a stored counter and reality).
 */
class UserLeavePolicyService
{
    public const DEFAULT_POLICY_NAME = 'Annual Year';
    public const DEFAULT_POLICY_DAYS = 21;

    /**
     * Replace the user's leave-policy set with the given list (sync semantics).
     *
     * @param list<string> $leavePolicyIds
     * @return \Illuminate\Database\Eloquent\Collection<int, UserLeavePolicy>
     */
    public function syncForUser(string $userId, array $leavePolicyIds, ?string $assignedBy = null): \Illuminate\Database\Eloquent\Collection
    {
        $leavePolicyIds = array_values(array_unique(array_filter($leavePolicyIds)));
        $companyId = (string) tenant('id');

        // Validate all policies belong to this tenant.
        $validIds = LeavePolicy::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $leavePolicyIds)
            ->pluck('id')
            ->all();

        UserLeavePolicy::query()
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->whereNotIn('leave_policy_id', $validIds)
            ->delete();

        foreach ($validIds as $policyId) {
            UserLeavePolicy::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'leave_policy_id' => $policyId,
                ],
                [
                    'id' => Uuid::uuid4()->toString(),
                    'company_id' => $companyId,
                    'assigned_by' => $assignedBy,
                ]
            );
        }

        return UserLeavePolicy::query()
            ->with(['leavePolicy.leaveType'])
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->get();
    }

    /**
     * The user's assigned policies with live balance for the given (or current) year.
     *
     * @return list<array{assignment: UserLeavePolicy, policy: LeavePolicy, balance: array}>
     */
    public function listWithBalancesForUser(User $user, ?int $year = null): array
    {
        $year = $year ?? (int) now()->year;

        $assignments = UserLeavePolicy::query()
            ->with(['leavePolicy.leaveType'])
            ->where('user_id', (string) $user->id)
            ->get()
            ->filter(fn (UserLeavePolicy $assignment) => $assignment->leavePolicy !== null)
            ->values();

        $schedule = $this->scheduleForUser($user);
        $usage = $this->usageForUser((string) $user->id, $year, $schedule);

        $result = [];
        foreach ($assignments as $assignment) {
            $policy = $assignment->leavePolicy;
            $used = (float) ($usage[$policy->id]['approved'] ?? 0);
            $pending = (float) ($usage[$policy->id]['pending'] ?? 0);

            $result[] = [
                'assignment' => $assignment,
                'policy' => $policy,
                'balance' => $this->buildBalance($policy, $used, $pending, $year),
            ];
        }

        return $result;
    }

    /**
     * Live balance of one policy for one user in a year.
     *
     * @return array{entitled_days: float, used_days: float, pending_days: float, available_days: float, year: int, is_deduct_from_balance: bool}
     */
    public function balanceFor(User $user, LeavePolicy $policy, ?int $year = null): array
    {
        $year = $year ?? (int) now()->year;
        $schedule = $this->scheduleForUser($user);
        $usage = $this->usageForPolicies((string) $user->id, [$policy->id], $year, $schedule);

        return $this->buildBalance(
            $policy,
            (float) ($usage[$policy->id]['approved'] ?? 0),
            (float) ($usage[$policy->id]['pending'] ?? 0),
            $year,
        );
    }

    /**
     * Whether the user has this policy assigned.
     */
    public function userHasPolicy(string $userId, string $leavePolicyId): bool
    {
        return UserLeavePolicy::query()
            ->where('user_id', $userId)
            ->where('leave_policy_id', $leavePolicyId)
            ->exists();
    }

    /**
     * The company default leave policy — the seeded 21-day "Annual Year" policy.
     */
    public function resolveDefaultPolicy(string $companyId): ?LeavePolicy
    {
        return LeavePolicy::query()
            ->where('company_id', $companyId)
            ->where('name', self::DEFAULT_POLICY_NAME)
            ->first()
            ?? LeavePolicy::query()
                ->where('company_id', $companyId)
                ->where('total_days', self::DEFAULT_POLICY_DAYS)
                ->first();
    }

    /**
     * Assign the company default policy to the user (idempotent).
     */
    public function assignDefaultPolicyToUser(string $userId, string $companyId, ?string $assignedBy = null): ?UserLeavePolicy
    {
        $policy = $this->resolveDefaultPolicy($companyId);

        if ($policy === null) {
            return null;
        }

        return UserLeavePolicy::query()->firstOrCreate(
            [
                'user_id' => $userId,
                'leave_policy_id' => $policy->id,
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'company_id' => $companyId,
                'is_default' => true,
                'assigned_by' => $assignedBy,
            ]
        );
    }

    /**
     * Count leave days of a request intersecting a window, honouring the policy's
     * day_calculation_type (calendar days vs scheduled work days).
     */
    public function countDaysInWindow(
        Carbon $start,
        Carbon $end,
        Carbon $windowStart,
        Carbon $windowEnd,
        LeavePolicy $policy,
        ?ScheduledWorkDays $schedule = null,
    ): float {
        $from = $start->copy()->startOfDay()->max($windowStart->copy()->startOfDay());
        $to = $end->copy()->startOfDay()->min($windowEnd->copy()->startOfDay());

        if ($to->lt($from)) {
            return 0.0;
        }

        if ($policy->day_calculation_type === 'work_day' && $schedule !== null) {
            $days = 0;
            foreach (CarbonPeriod::create($from, $to) as $day) {
                if ($schedule->isWorkDay($day)) {
                    $days++;
                }
            }

            return (float) $days;
        }

        return (float) ($from->diffInDays($to) + 1);
    }

    /**
     * @return array{entitled_days: float, used_days: float, pending_days: float, available_days: float, year: int, is_deduct_from_balance: bool}
     */
    private function buildBalance(LeavePolicy $policy, float $used, float $pending, int $year): array
    {
        $isDeductFromBalance = (bool) ($policy->leaveType?->is_deduct_from_balance);

        $entitled = $isDeductFromBalance
            ? $this->entitledDaysForYear($policy, $year)
            : (float) ($policy->max_days ?? 0);

        return [
            'entitled_days' => $entitled,
            'used_days' => $used,
            'pending_days' => $pending,
            'available_days' => max(0.0, $entitled - $used - $pending),
            'year' => $year,
            'is_deduct_from_balance' => $isDeductFromBalance,
        ];
    }

    /**
     * Yearly entitlement for balance policies.
     *
     * - upfront: the full total_days at once (renews every January).
     * - monthly: accrues total_days/12 per elapsed month of the year.
     * max_balance_days caps the entitlement when set.
     */
    private function entitledDaysForYear(LeavePolicy $policy, int $year): float
    {
        $totalDays = (float) ($policy->total_days ?? 0);

        if ($policy->entitlement_type === 'monthly') {
            $currentYear = (int) now()->year;
            $elapsedMonths = match (true) {
                $year < $currentYear => 12,
                $year > $currentYear => 0,
                default => (int) now()->month,
            };
            $entitled = min($totalDays, round($totalDays / 12 * $elapsedMonths, 2));
        } else {
            $entitled = $totalDays;
        }

        if ($policy->max_balance_days !== null) {
            $entitled = min($entitled, (float) $policy->max_balance_days);
        }

        return $entitled;
    }

    /**
     * Approved/pending day usage per policy for the user within the year window.
     *
     * @return array<string, array{approved: float, pending: float}>
     */
    private function usageForUser(string $userId, int $year, ScheduledWorkDays $schedule): array
    {
        $policyIds = UserLeavePolicy::query()
            ->where('user_id', $userId)
            ->pluck('leave_policy_id')
            ->all();

        return $this->usageForPolicies($userId, $policyIds, $year, $schedule);
    }

    /**
     * @param list<string> $policyIds
     * @return array<string, array{approved: float, pending: float}>
     */
    private function usageForPolicies(string $userId, array $policyIds, int $year, ScheduledWorkDays $schedule): array
    {
        if ($policyIds === []) {
            return [];
        }

        $windowStart = Carbon::create($year, 1, 1)->startOfDay();
        $windowEnd = Carbon::create($year, 12, 31)->startOfDay();

        $requests = LeaveRequest::query()
            ->where('user_id', $userId)
            ->whereIn('leave_policy_id', $policyIds)
            ->whereIn('status', [LeaveRequest::STATUS_APPROVED, LeaveRequest::STATUS_PENDING])
            ->whereDate('start_date', '<=', $windowEnd->toDateString())
            ->whereDate('end_date', '>=', $windowStart->toDateString())
            ->with('leavePolicy')
            ->get(['id', 'leave_policy_id', 'start_date', 'end_date', 'status']);

        $usage = [];
        foreach ($requests as $request) {
            $policy = $request->leavePolicy;
            if ($policy === null) {
                continue;
            }

            $days = $this->countDaysInWindow(
                Carbon::parse($request->start_date),
                Carbon::parse($request->end_date),
                $windowStart,
                $windowEnd,
                $policy,
                $schedule,
            );

            $bucket = $request->status === LeaveRequest::STATUS_APPROVED ? 'approved' : 'pending';
            $usage[$request->leave_policy_id][$bucket] = ($usage[$request->leave_policy_id][$bucket] ?? 0) + $days;
        }

        return $usage;
    }

    private function scheduleForUser(User $user): ScheduledWorkDays
    {
        $user->loadMissing(['userProfessionalData.attendanceConstraint', 'professionalData.attendanceConstraint']);

        $constraint = $user->userProfessionalData?->attendanceConstraint
            ?? $user->professionalData?->attendanceConstraint;

        return ScheduledWorkDays::fromConstraint($constraint);
    }
}
