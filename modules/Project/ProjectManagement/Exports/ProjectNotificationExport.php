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
        return ProjectNotification::filter($this->filters)
            ->with(['assignedUser', 'project'])
            ->get();
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
            'Contractor Technical Name',
            'Assigned Engineer',
            'Project',
            'Status',
            'Task Date',
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
            $row->contractor_technical_name,
            $row->assignedUser?->name,
            $row->project?->name,
            $row->status,
            $row->task_date?->format('Y-m-d'),
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
