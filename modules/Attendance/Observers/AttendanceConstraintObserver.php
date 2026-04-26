<?php

declare(strict_types=1);

namespace Modules\Attendance\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Services\AttendanceConstraintService;

/**
 * Listens to {@see AttendanceConstraint} lifecycle to invalidate caches after
 * {{baseUrl}}/attendance/constraints/{constraint_id}} (or any Eloquent write on this model).
 * Mass {@see \Illuminate\Database\Query\Builder::update} calls bypass observers — use repository / controller hooks.
 */
final class AttendanceConstraintObserver
{
    public function __construct(
        private readonly AttendanceConstraintService $constraintService,
    ) {
    }

    public function saved(AttendanceConstraint $constraint): void
    {
        $this->invalidate($constraint);
    }

    public function deleted(AttendanceConstraint $constraint): void
    {
        $this->invalidate($constraint);
    }

    public function restored(AttendanceConstraint $constraint): void
    {
        $this->invalidate($constraint);
    }

    private function invalidate(AttendanceConstraint $constraint): void
    {
        $this->forgetThisConstraintIdCaches($constraint->id);

        // Applicable rules are resolved per user; we bump a company version so all user remember() keys miss
        if ($constraint->company_id) {
            $this->constraintService->bumpApplicableConstraintsCacheForCompany(
                (string) $constraint->company_id
            );
        }
    }

    private function forgetThisConstraintIdCaches(string $constraintId): void
    {
        Cache::forget(AttendanceConstraintService::singleConstraintCacheKey($constraintId));
    }
}
