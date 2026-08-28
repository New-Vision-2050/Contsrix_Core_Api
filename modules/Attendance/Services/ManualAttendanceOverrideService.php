<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Illuminate\Support\Collection;
use Modules\Attendance\Models\UserManualAttendanceOverride;
use Modules\Attendance\Support\ManualAttendanceOverrideSet;
use Modules\User\Models\User;

/**
 * Persists disjoint attendance-status ranges. PATCH adds a range; it does not
 * overwrite earlier days on `users` as the source of truth (INV-18).
 */
final class ManualAttendanceOverrideService
{
    public function currentFor(User $user): ManualAttendanceOverrideSet
    {
        return ManualAttendanceOverrideSet::fromUser($user);
    }

    /**
     * @param  Collection<int, string>  $userIds
     * @return Collection<string, Collection<int, UserManualAttendanceOverride>>
     */
    public function groupedForUsers(Collection $userIds): Collection
    {
        $ids = $userIds->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return UserManualAttendanceOverride::query()
            ->whereIn('user_id', $ids->all())
            ->get()
            ->groupBy(fn (UserManualAttendanceOverride $row): string => (string) $row->user_id);
    }

    public function apply(User $user, string $status, string $from, ?string $to): ManualAttendanceOverrideSet
    {
        $next = $this->currentFor($user)->withApplied($status, $from, $to);
        $this->replaceAll($user, $next);

        return $next;
    }

    public function replaceAll(User $user, ManualAttendanceOverrideSet $set): void
    {
        UserManualAttendanceOverride::query()
            ->where('user_id', $user->id)
            ->delete();

        $models = [];
        foreach ($set->ranges() as $range) {
            $models[] = UserManualAttendanceOverride::query()->create([
                'user_id' => $user->id,
                'company_id' => $user->company_id ?? tenant('id'),
                'status' => $range['status'],
                'starts_on' => $range['starts_on'],
                'ends_on' => $range['ends_on'],
            ]);
        }

        $user->setRelation('manualAttendanceOverrides', collect($models));
    }
}
