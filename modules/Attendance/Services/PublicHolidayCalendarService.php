<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\Support\PublicHolidayDates;
use Modules\Leave\PublicHoliday\Models\PublicHolidayDay;
use Modules\User\Models\User;

/**
 * Answers "which of these dates is an official public holiday for this employee".
 *
 * `public_holidays` is a central table keyed by `country_id`, shared by every tenant, and
 * the employee's country comes from the branch they work at. Read live on every surface
 * rather than materialised into attendance rows — see INV-21 for why the previous
 * pre-writing command was removed.
 *
 * Deliberately stateless: callers that need more than one employee (the report) hold their
 * own per-country memo, so nothing here survives between requests under Octane.
 */
class PublicHolidayCalendarService
{
    /**
     * The country whose public holidays apply to this employee: the country of the branch
     * they are posted to, falling back to their company's own country when the branch has
     * no address on file — otherwise a missing address would silently cancel every holiday.
     *
     * @param string|null $fallbackCountryId Last resort when neither branch nor company
     *        resolves. The reporting services pass `employment_contracts.country_id` here so
     *        moving them onto the branch country cannot zero out a figure that used to count.
     */
    public function countryIdForUser(?User $user, ?string $fallbackCountryId = null): ?string
    {
        if ($user === null) {
            return $fallbackCountryId !== null && $fallbackCountryId !== ''
                ? $fallbackCountryId
                : null;
        }

        foreach ([
            fn () => $user->userProfessionalData?->branch?->address?->country_id,
            fn () => $user->branch?->address?->country_id,
            fn () => $user->company?->country_id,
            fn () => $fallbackCountryId,
        ] as $candidate) {
            try {
                $countryId = $candidate();
            } catch (\Throwable) {
                // A relation that needs tenancy can throw outside an HTTP request. Holiday
                // resolution must never be the thing that breaks the caller.
                continue;
            }

            if ($countryId !== null && (string) $countryId !== '') {
                return (string) $countryId;
            }
        }

        return null;
    }

    public function forUser(?User $user, string $fromDate, string $toDate): PublicHolidayDates
    {
        return $this->forCountry($this->countryIdForUser($user), $fromDate, $toDate);
    }

    public function forCountry(?string $countryId, string $fromDate, string $toDate): PublicHolidayDates
    {
        if ($countryId === null || $countryId === '') {
            return PublicHolidayDates::none();
        }

        try {
            $rows = PublicHolidayDay::query()
                ->join('public_holidays', 'public_holiday_days.public_holiday_id', '=', 'public_holidays.id')
                ->where('public_holidays.country_id', $countryId)
                ->where('public_holidays.is_active', true)
                ->whereBetween('public_holiday_days.date', [$fromDate, $toDate])
                ->orderBy('public_holiday_days.date')
                ->get([
                    'public_holiday_days.date',
                    'public_holidays.name',
                    'public_holidays.name_ar',
                ]);
        } catch (\Throwable) {
            return PublicHolidayDates::none();
        }

        $names = [];
        foreach ($rows as $row) {
            $date = substr((string) $row->date, 0, 10);
            if ($date === '') {
                continue;
            }

            $name = trim((string) ($row->name_ar ?? '')) !== ''
                ? (string) $row->name_ar
                : (string) $row->name;

            // First holiday wins when two overlap on one date; the label is informational.
            $names[$date] ??= $name;
        }

        return PublicHolidayDates::fromMap($names);
    }
}
