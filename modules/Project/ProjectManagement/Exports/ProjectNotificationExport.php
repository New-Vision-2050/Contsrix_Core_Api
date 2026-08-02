<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Exports;

use App\Exports\BaseExport;
use Carbon\CarbonImmutable;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectManagement\Presenters\ProjectNotificationPresenter;
use Modules\User\Models\User;

class ProjectNotificationExport extends BaseExport
{
    private const EMPTY_VALUE = '—';

    public function __construct(
        protected array $filters = [],
        protected ?string $sort = null,
    ) {}

    public function collection()
    {
        $query = ProjectNotification::filter($this->filters)
            ->with([
                'project.client',
                'project.ownerCompany',
                'project.ownerIndividual',
                'contractor',
                'contractorRepresentative',
                'updateSiteStatus',
                'endTaskStatus',
                'employeeTask.user',
                'employeeTask.sessions',
                'siteStatusUpdates' => fn ($q) => $q->latest('created_at')->limit(1),
                'notificationNotes' => fn ($q) => $q->with('user')->latest('created_at')->limit(1),
            ]);

        $this->applySorting($query);

        $notifications = $query->get();

        $allUserIds = $notifications
            ->pluck('assigned_user_ids')
            ->flatten()
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (! empty($allUserIds)) {
            $users = User::withoutGlobalScopes()
                ->whereIn('id', $allUserIds)
                ->get()
                ->keyBy('id');
            foreach ($notifications as $n) {
                $ids = $n->assigned_user_ids ?? [];
                $n->setPreloadedAssignedUsers(collect($ids)->map(fn ($id) => $users->get($id))->filter());
            }
        }

        return $notifications;
    }

    public function headings(): array
    {
        return [
            'رقم الإشعار',
            'حالة الإشعار',
            'نوع الإشعار',
            'حالة العمل',
            'الموقت',
            'تاريخ أخر تحديث بالموقع',
            'التاريخ',
            'اخر ملاحظة',
            'المقاول',
            'اسم الاستشاري',
            'المهندس',
            'الموقع',
        ];
    }

    public function map($row): array
    {
        $presented = (new ProjectNotificationPresenter($row))->toListArray();

        return [
            $this->displayValue($row->notification_number),
            $this->displayValue($presented['status_label'] ?? null),
            $this->displayValue($row->notification_type),
            $this->workStatus($row),
            $this->timerSnapshot($row),
            $this->displayValue($presented['last_site_update_date'] ?? null),
            $this->taskDateTime($row),
            $this->displayValue($presented['last_note']['note'] ?? null),
            $this->contractorName($row),
            $this->consultantName($row),
            $this->engineers($row),
            $this->location($row),
        ];
    }

    public function getFilterableColumns(): array
    {
        return [
            'notification_number',
            'status',
            'notification_type',
            'work_type',
            'contractor_name',
        ];
    }

    private function applySorting($query): void
    {
        if (! $this->sort) {
            $query->orderByDesc('created_at');

            return;
        }

        $direction = str_ends_with($this->sort, '_desc') ? 'desc' : 'asc';
        $column = str_replace(['_desc', '_asc'], '', $this->sort);
        $allowed = ['created_at', 'task_date', 'notification_number', 'status', 'severity'];

        if (in_array($column, $allowed, true)) {
            $query->orderBy($column, $direction);

            return;
        }

        $query->orderByDesc('created_at');
    }

    private function workStatus(ProjectNotification $notification): string
    {
        $task = $notification->relationLoaded('employeeTask') ? $notification->employeeTask : null;

        if (! $task || ! $task->status) {
            return self::EMPTY_VALUE;
        }

        return EmployeeTaskStatus::tryFrom((string) $task->status)?->label(app()->getLocale())
            ?? $this->displayValue($task->status);
    }

    private function timerSnapshot(ProjectNotification $notification): string
    {
        $task = $notification->relationLoaded('employeeTask') ? $notification->employeeTask : null;

        if (! $task || ! $task->time_from || ! in_array($task->status, EmployeeTaskStatus::activeStatuses(), true)) {
            return self::EMPTY_VALUE;
        }

        $timezone = $task->timezone ?: config('app.timezone') ?: 'UTC';
        $completedSessionMinutes = $task->relationLoaded('sessions')
            ? (int) $task->sessions->whereNotNull('end_time')->sum('duration_minutes')
            : 0;

        $activeSession = $task->relationLoaded('sessions')
            ? $task->sessions->first(fn ($session) => $session->end_time === null)
            : null;

        $activeSessionSeconds = 0;
        if ($activeSession) {
            $sessionStart = CarbonImmutable::parse($activeSession->start_time, $timezone);
            $activeSessionSeconds = max(0, (int) $sessionStart->diffInSeconds(CarbonImmutable::now($timezone)));
        }

        return $this->formatSeconds(($completedSessionMinutes * 60) + $activeSessionSeconds);
    }

    private function taskDateTime(ProjectNotification $notification): string
    {
        if (! $notification->task_date) {
            return self::EMPTY_VALUE;
        }

        $date = $notification->task_date->format('Y-m-d');
        $time = $notification->task_time?->format('H:i');

        return $time ? "{$date} {$time}" : $date;
    }

    private function contractorName(ProjectNotification $notification): string
    {
        return $this->displayValue($notification->contractor?->name ?? $notification->contractor_name);
    }

    private function consultantName(ProjectNotification $notification): string
    {
        $project = $notification->project;

        if (! $project) {
            return self::EMPTY_VALUE;
        }

        return $this->displayValue(
            $project->client?->name
            ?? $project->projectOwner?->name
            ?? null
        );
    }

    private function engineers(ProjectNotification $notification): string
    {
        $names = $notification->assigned_users
            ->pluck('name')
            ->filter()
            ->map(fn ($name) => $this->displayValue($name))
            ->filter(fn ($name) => $name !== self::EMPTY_VALUE)
            ->values()
            ->all();

        return empty($names) ? self::EMPTY_VALUE : implode(', ', $names);
    }

    private function location(ProjectNotification $notification): string
    {
        return $this->displayValue(
            $notification->full_address
            ?? $notification->district
            ?? $notification->project?->name
            ?? null
        );
    }

    private function displayValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return self::EMPTY_VALUE;
        }

        if (is_array($value)) {
            if ($value === []) {
                return self::EMPTY_VALUE;
            }

            foreach ([app()->getLocale(), 'ar', 'en'] as $key) {
                if (array_key_exists($key, $value)) {
                    return $this->displayValue($value[$key]);
                }
            }

            return $this->displayValue(reset($value));
        }

        return (string) $value;
    }

    private function formatSeconds(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $seconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
}
