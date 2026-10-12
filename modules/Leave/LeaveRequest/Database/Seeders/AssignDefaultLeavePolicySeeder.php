<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Leave\LeaveRequest\Services\UserLeavePolicyService;

/**
 * Guarantees every company has a usable default leave policy and assigns it to
 * every user:
 *
 * 1. Ensures an "Annual Leave" leave type exists (deduct-from-balance, paid).
 * 2. Ensures the 21-day "Annual Year" leave policy exists and is linked to it
 *    (LeavePolicySeeder may have created it with a null leave_type_id).
 * 3. Assigns the policy to every user of the company as their default policy.
 *
 * Balances renew every calendar year automatically (they are computed live per
 * year), so no yearly re-seed is needed. Safe to re-run: everything is
 * first-or-create / insert-ignore style.
 */
class AssignDefaultLeavePolicySeeder extends Seeder
{
    private const LEAVE_TYPE_NAME = 'Annual Leave';

    public function run(): void
    {
        if (tenancy()->initialized) {
            $companies = [tenant()->getTenantKey()];
        } else {
            $companies = Company::query()->pluck('id')->all();
        }

        if (empty($companies)) {
            $this->command?->info('No companies found. Nothing to seed.');

            return;
        }

        foreach ($companies as $companyId) {
            $companyId = (string) $companyId;

            $leaveTypeId = $this->ensureDefaultLeaveType($companyId);
            $policyId = $this->ensureDefaultLeavePolicy($companyId, $leaveTypeId);

            if ($policyId === null) {
                continue;
            }

            $assigned = $this->assignToAllUsers($companyId, $policyId);

            $this->command?->info(sprintf(
                'Default leave policy (%d days) assigned to %d user(s) for company %s.',
                UserLeavePolicyService::DEFAULT_POLICY_DAYS,
                $assigned,
                $companyId,
            ));
        }
    }

    /**
     * First-or-create the "Annual Leave" leave type. Raw queries bypass the
     * tenant global scope so the seeder also works outside tenancy context.
     */
    private function ensureDefaultLeaveType(string $companyId): ?string
    {
        $existingId = DB::table('leave_types')
            ->where('company_id', $companyId)
            ->where('name', self::LEAVE_TYPE_NAME)
            ->value('id');

        if ($existingId !== null) {
            return (string) $existingId;
        }

        // Prefer any existing deduct-from-balance type over creating a duplicate.
        $deductibleId = DB::table('leave_types')
            ->where('company_id', $companyId)
            ->where('is_deduct_from_balance', true)
            ->value('id');

        if ($deductibleId !== null) {
            return (string) $deductibleId;
        }

        $id = (string) Str::uuid();
        DB::table('leave_types')->insert([
            'id' => $id,
            'company_id' => $companyId,
            'name' => self::LEAVE_TYPE_NAME,
            'is_payed' => true,
            'is_deduct_from_balance' => true,
            'requires_attachment' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    /**
     * First-or-create the 21-day "Annual Year" policy and repair a missing
     * leave_type link (LeavePolicySeeder tolerates a null leave_type_id).
     */
    private function ensureDefaultLeavePolicy(string $companyId, ?string $leaveTypeId): ?string
    {
        $existing = DB::table('leave_policies')
            ->where('company_id', $companyId)
            ->where('name', UserLeavePolicyService::DEFAULT_POLICY_NAME)
            ->first();

        if ($existing !== null) {
            if ($existing->leave_type_id === null && $leaveTypeId !== null) {
                DB::table('leave_policies')
                    ->where('id', $existing->id)
                    ->update(['leave_type_id' => $leaveTypeId, 'updated_at' => now()]);
            }

            return (string) $existing->id;
        }

        // Fall back to any 21-day policy already defined for the company.
        $fallback = DB::table('leave_policies')
            ->where('company_id', $companyId)
            ->where('total_days', UserLeavePolicyService::DEFAULT_POLICY_DAYS)
            ->first();

        if ($fallback !== null) {
            return (string) $fallback->id;
        }

        $id = (string) Str::uuid();
        DB::table('leave_policies')->insert([
            'id' => $id,
            'company_id' => $companyId,
            'name' => UserLeavePolicyService::DEFAULT_POLICY_NAME,
            'leave_type_id' => $leaveTypeId,
            'total_days' => UserLeavePolicyService::DEFAULT_POLICY_DAYS,
            'max_balance_days' => UserLeavePolicyService::DEFAULT_POLICY_DAYS,
            'min_notice_days' => 0,
            'day_calculation_type' => 'calendar',
            'entitlement_type' => 'upfront',
            'entitlement_start_from' => 'hire_date',
            'is_rollover_allowed' => true,
            'allow_exceed_balance' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    /**
     * Assign the default policy to every user of the company (skip existing).
     */
    private function assignToAllUsers(string $companyId, string $policyId): int
    {
        $userIds = DB::table('users')
            ->where('company_id', $companyId)
            ->pluck('id');

        $assigned = 0;
        foreach ($userIds as $userId) {
            $exists = DB::table('user_leave_policies')
                ->where('user_id', $userId)
                ->where('leave_policy_id', $policyId)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('user_leave_policies')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => (string) $userId,
                'leave_policy_id' => $policyId,
                'company_id' => $companyId,
                'is_default' => true,
                'assigned_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $assigned++;
        }

        return $assigned;
    }
}
