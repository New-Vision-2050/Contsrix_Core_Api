<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Exports;

use App\Exports\BaseExport;
use Illuminate\Support\Collection;
use Modules\Project\ProjectManagement\Models\ProjectNotification;

class ProjectNotificationExport extends BaseExport
{
    public function __construct(
        protected array $filters = [],
    ) {}

    public function collection()
    {
        $query = ProjectNotification::filter($this->filters)
            ->with(['project', 'contractorRepresentative']);

        $statusFilter = $this->filters['status'] ?? null;
        if (! is_string($statusFilter) || ! str_contains($statusFilter, 'draft')) {
            $query->where('project_notifications.status', '!=', 'draft');
        }

        $notifications = $query->get();

        $allUserIds = $notifications->pluck('assigned_user_ids')->flatten()->unique()->values()->all();
        if (! empty($allUserIds)) {
            $users = \Modules\User\Models\User::withoutGlobalScopes()
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
            'Notification Number',
            'Notification Type',
            'Severity',
            'Work Type',
            'Contractor Name',
            'Contractor Number',
            'Feeder Number',
            'Contractor Representative',
            'Assigned Engineer',
            'Project',
            'Status',
            'Task Date',
            'Confirmation Receive Date',
            'Distance (m)',
            'Created At',
        ];
    }

    public function map($row): array
    {
        return [
            $row->notification_number,
            $row->notification_type,
            $row->severity,
            $row->work_type,
            $row->contractor_name,
            $row->contractor_number,
            $row->feeder_number,
            $row->contractorRepresentative?->name,
            $row->assigned_user?->name,
            $row->project?->name,
            $row->status,
            $row->task_date?->format('Y-m-d'),
            $row->confirmation_receive_date?->format('Y-m-d H:i:s'),
            $row->selected_distance_meters,
            $row->created_at?->format('Y-m-d H:i:s'),
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
}
