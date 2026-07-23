<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementRepetition;
use Modules\Project\ProjectManagement\Models\ProjectRequirement;
use Modules\Project\ProjectManagement\Models\ProjectRequirementSubmission;
use Modules\Project\ProjectManagement\Presenters\ProjectRequirementSubmissionPresenter;
use Modules\RoleAndPermission\Enums\Permission;

class ProjectRequirementUploadStatusService
{
    public function attach(iterable $requirements, ?string $companyId = null): void
    {
        $collection = collect($requirements);

        if ($collection->isEmpty()) {
            return;
        }

        $companyId ??= (string) tenant('id');
        $submissions = ProjectRequirementSubmission::query()
            ->with('media')
            ->whereIn('project_requirement_id', $collection->pluck('id')->filter()->values()->all())
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('project_requirement_id');

        foreach ($collection as $requirement) {
            if (! $requirement instanceof ProjectRequirement) {
                continue;
            }

            $requirement->setAttribute(
                'upload_status',
                $this->statusFor(
                    $requirement,
                    $companyId,
                    $submissions->get($requirement->id, collect())
                )
            );
        }
    }

    public function statusFor(
        ProjectRequirement $requirement,
        ?string $companyId = null,
        ?Collection $submissions = null,
        ?CarbonInterface $at = null
    ): array {
        $companyId ??= (string) tenant('id');
        $now = $this->now($at);
        $period = $this->periodFor($requirement, $now);
        $submissions ??= ProjectRequirementSubmission::query()
            ->with('media')
            ->where('project_requirement_id', $requirement->id)
            ->orderByDesc('created_at')
            ->get();

        $latestSubmission = $submissions->first();
        $currentSubmission = $period['period_key'] === null
            ? null
            : $this->currentSubmissionForPeriod($submissions, $period);

        $canUpload = true;
        $disabledReason = null;
        $nextAvailableAt = null;

        if ($period['period_key'] === null) {
            $canUpload = false;
            $disabledReason = 'invalid_repetition';
        } elseif (
            (string) $requirement->company_id === $companyId
            && ! Auth::user()?->can(Permission::PROJECT_REQUIREMENT_UPDATE())
        ) {
            $canUpload = false;
            $disabledReason = 'missing_permission';
        } elseif (! $this->isUploaderAllowed($requirement, $companyId)) {
            $canUpload = false;
            $disabledReason = 'not_assigned';
        } elseif ($currentSubmission !== null) {
            $canUpload = false;
            $disabledReason = 'already_submitted';
            $nextAvailableAt = $this->nextAvailableAfterSubmission($requirement, $period, $now);
        } elseif (! $this->matchesRepeatDay($requirement, $now)) {
            $canUpload = false;
            $disabledReason = 'outside_repeat_days';
            $nextAvailableAt = $this->nextRepeatDayStart($requirement, $now->addDay()->startOfDay());
        }

        return [
            'can_upload' => $canUpload,
            'disabled_reason' => $disabledReason,
            'current_period_key' => $period['period_key'],
            'period_starts_at' => $period['period_starts_at']?->toIso8601String(),
            'period_ends_at' => $period['period_ends_at']?->toIso8601String(),
            'next_available_at' => $nextAvailableAt?->toIso8601String(),
            'latest_submission' => $latestSubmission instanceof ProjectRequirementSubmission
                ? (new ProjectRequirementSubmissionPresenter($latestSubmission))->getData()
                : null,
        ];
    }

    /**
     * @return array{period_key: ?string, period_starts_at: ?CarbonImmutable, period_ends_at: ?CarbonImmutable}
     */
    public function periodFor(ProjectRequirement $requirement, ?CarbonInterface $at = null): array
    {
        $now = $this->now($at);

        return match ($requirement->repetition) {
            ProjectRequirementRepetition::Once->value => [
                'period_key' => 'once',
                'period_starts_at' => null,
                'period_ends_at' => null,
            ],
            ProjectRequirementRepetition::Daily->value => [
                'period_key' => 'daily:'.$now->toDateString(),
                'period_starts_at' => $now->startOfDay(),
                'period_ends_at' => $now->endOfDay(),
            ],
            ProjectRequirementRepetition::Weekly->value => [
                'period_key' => 'weekly:'.$now->format('o-\WW'),
                'period_starts_at' => $now->startOfWeek(CarbonInterface::MONDAY),
                'period_ends_at' => $now->endOfWeek(CarbonInterface::SUNDAY),
            ],
            ProjectRequirementRepetition::Monthly->value => [
                'period_key' => 'monthly:'.$now->format('Y-m'),
                'period_starts_at' => $now->startOfMonth(),
                'period_ends_at' => $now->endOfMonth(),
            ],
            default => [
                'period_key' => null,
                'period_starts_at' => null,
                'period_ends_at' => null,
            ],
        };
    }

    /**
     * @param  Collection<int, ProjectRequirementSubmission>  $submissions
     * @param  array{period_key: ?string, period_starts_at: ?CarbonImmutable, period_ends_at: ?CarbonImmutable}  $period
     */
    private function currentSubmissionForPeriod(Collection $submissions, array $period): ?ProjectRequirementSubmission
    {
        if ($period['period_key'] === 'once') {
            $submission = $submissions->first();

            return $submission instanceof ProjectRequirementSubmission ? $submission : null;
        }

        $startsAt = $period['period_starts_at'];
        $endsAt = $period['period_ends_at'];

        if ($startsAt === null || $endsAt === null) {
            return null;
        }

        $submission = $submissions->first(function (ProjectRequirementSubmission $submission) use ($startsAt, $endsAt): bool {
            if ($submission->created_at === null) {
                return false;
            }

            $createdAt = CarbonImmutable::instance($submission->created_at)
                ->setTimezone(config('app.timezone', 'UTC'));

            return $createdAt->greaterThanOrEqualTo($startsAt)
                && $createdAt->lessThanOrEqualTo($endsAt);
        });

        return $submission instanceof ProjectRequirementSubmission ? $submission : null;
    }

    private function isUploaderAllowed(ProjectRequirement $requirement, string $companyId): bool
    {
        if ((string) $requirement->company_id === $companyId) {
            return true;
        }

        $requirement->loadMissing('receiverCompanies');

        return $requirement->receiverCompanies
            ->pluck('id')
            ->contains($companyId);
    }

    private function matchesRepeatDay(ProjectRequirement $requirement, CarbonImmutable $now): bool
    {
        if (! in_array($requirement->repetition, [
            ProjectRequirementRepetition::Daily->value,
            ProjectRequirementRepetition::Weekly->value,
        ], true)) {
            return true;
        }

        $allowedDays = $this->allowedWeekdays($requirement->repeat_days);

        return $allowedDays === [] || in_array($now->dayOfWeek, $allowedDays, true);
    }

    private function nextAvailableAfterSubmission(
        ProjectRequirement $requirement,
        array $period,
        CarbonImmutable $now
    ): ?CarbonImmutable {
        return match ($requirement->repetition) {
            ProjectRequirementRepetition::Once->value => null,
            ProjectRequirementRepetition::Daily->value => $this->allowedWeekdays($requirement->repeat_days) === []
                ? $now->addDay()->startOfDay()
                : $this->nextRepeatDayStart($requirement, $now->addDay()->startOfDay()),
            ProjectRequirementRepetition::Weekly->value => $this->allowedWeekdays($requirement->repeat_days) === []
                ? $period['period_starts_at']?->addWeek()
                : $this->nextRepeatDayStart($requirement, $period['period_starts_at']?->addWeek()->startOfDay()),
            ProjectRequirementRepetition::Monthly->value => $period['period_starts_at']?->addMonth(),
            default => null,
        };
    }

    private function nextRepeatDayStart(ProjectRequirement $requirement, ?CarbonImmutable $start): ?CarbonImmutable
    {
        if ($start === null) {
            return null;
        }

        $allowedDays = $this->allowedWeekdays($requirement->repeat_days);

        if ($allowedDays === []) {
            return $start->startOfDay();
        }

        $candidate = $start->startOfDay();
        foreach (range(0, 370) as $_) {
            if (in_array($candidate->dayOfWeek, $allowedDays, true)) {
                return $candidate;
            }

            $candidate = $candidate->addDay();
        }

        return null;
    }

    /**
     * @param  mixed  $repeatDays
     * @return list<int> Carbon day-of-week values, Sunday = 0.
     */
    private function allowedWeekdays(mixed $repeatDays): array
    {
        if (! is_array($repeatDays) || $repeatDays === []) {
            return [];
        }

        $map = [
            'sunday' => CarbonInterface::SUNDAY,
            'sun' => CarbonInterface::SUNDAY,
            'monday' => CarbonInterface::MONDAY,
            'mon' => CarbonInterface::MONDAY,
            'tuesday' => CarbonInterface::TUESDAY,
            'tue' => CarbonInterface::TUESDAY,
            'wednesday' => CarbonInterface::WEDNESDAY,
            'wed' => CarbonInterface::WEDNESDAY,
            'thursday' => CarbonInterface::THURSDAY,
            'thu' => CarbonInterface::THURSDAY,
            'friday' => CarbonInterface::FRIDAY,
            'fri' => CarbonInterface::FRIDAY,
            'saturday' => CarbonInterface::SATURDAY,
            'sat' => CarbonInterface::SATURDAY,
        ];

        $days = [];
        foreach ($repeatDays as $day) {
            if (is_int($day) || (is_string($day) && ctype_digit($day))) {
                $number = (int) $day;
                if ($number >= 0 && $number <= 6) {
                    $days[] = $number;
                }
                if ($number >= 1 && $number <= 7) {
                    $days[] = $number === 7 ? CarbonInterface::SUNDAY : $number;
                }
                continue;
            }

            if (! is_string($day)) {
                continue;
            }

            $normalized = strtolower(trim($day));
            if (array_key_exists($normalized, $map)) {
                $days[] = $map[$normalized];
            }
        }

        return array_values(array_unique($days));
    }

    private function now(?CarbonInterface $at = null): CarbonImmutable
    {
        $timezone = config('app.timezone', 'UTC');

        if ($at === null) {
            return CarbonImmutable::now($timezone);
        }

        return CarbonImmutable::instance($at)->setTimezone($timezone);
    }
}
